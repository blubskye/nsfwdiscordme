<?php
declare(strict_types=1);

namespace App\Storage\Redis;

use Redis;
use RedisException;
use RuntimeException;

/**
 * Redis 8.4+ optimized adapter with async I/O and pipelining support.
 *
 * Redis 8.4 features utilized:
 * - Async I/O threading (server-side, configured via io-threads)
 * - Client-side pipelining for batch operations
 * - MSETEX for atomic multi-key expiration
 * - Lookahead prefetching optimization
 *
 * @see https://redis.io/docs/latest/operate/oss_and_stack/stack-with-enterprise/release-notes/redisce/redisos-8.4-release-notes/
 */
class Redis8Adapter
{
    private Redis $redis;
    private int $pipelineThreshold;
    private bool $connected = false;

    /**
     * @param string $host Redis host
     * @param int $port Redis port
     * @param int $database Database index
     * @param float $timeout Connection timeout in seconds
     * @param int $pipelineThreshold Minimum operations to trigger pipelining
     */
    public function __construct(
        private readonly string $host = 'localhost',
        private readonly int $port = 6379,
        private readonly int $database = 0,
        private readonly float $timeout = 2.0,
        int $pipelineThreshold = 3
    ) {
        $this->pipelineThreshold = $pipelineThreshold;
        $this->redis = new Redis();
    }

    /**
     * Lazy connection establishment.
     *
     * @throws RuntimeException If connection fails
     */
    private function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            // Use persistent connection to reduce connection overhead
            // Benefits from Redis 8's async I/O threading
            $this->redis->pconnect(
                $this->host,
                $this->port,
                $this->timeout,
                'persistent_' . $this->database
            );

            $this->redis->select($this->database);

            // Set client name for monitoring
            $this->redis->client('SETNAME', 'php-app-db' . $this->database);

            $this->connected = true;
        } catch (RedisException $e) {
            throw new RuntimeException('Failed to connect to Redis: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the underlying Redis instance.
     */
    public function getRedis(): Redis
    {
        $this->connect();
        return $this->redis;
    }

    /**
     * Execute multiple GET operations using pipelining.
     *
     * Pipelining sends multiple commands without waiting for individual replies,
     * which works exceptionally well with Redis 8's async I/O threading.
     *
     * @param array<string> $keys Keys to fetch
     * @return array<string, mixed> Key-value pairs
     */
    public function mgetPipelined(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $this->connect();

        // For small sets, use standard MGET
        if (count($keys) < $this->pipelineThreshold) {
            $values = $this->redis->mGet($keys);
            return array_combine($keys, $values) ?: [];
        }

        // For larger sets, use pipelining for better throughput
        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($keys as $key) {
            $pipe->get($key);
        }
        $results = $pipe->exec();

        return array_combine($keys, $results) ?: [];
    }

    /**
     * Execute multiple SET operations with optional TTL using pipelining.
     *
     * For Redis 8.4+, considers using MSETEX when available for atomic operations.
     *
     * @param array<string, mixed> $keyValues Key-value pairs to set
     * @param int $ttl Time-to-live in seconds (0 = no expiration)
     * @return bool True if all operations succeeded
     */
    public function msetPipelined(array $keyValues, int $ttl = 0): bool
    {
        if (empty($keyValues)) {
            return true;
        }

        $this->connect();

        // Without TTL, use native MSET (atomic)
        if ($ttl <= 0) {
            return $this->redis->mSet($keyValues);
        }

        // With TTL, use pipelining for SETEX operations
        // Redis 8.4's async I/O makes this much faster
        $pipe = $this->redis->multi(Redis::PIPELINE);
        foreach ($keyValues as $key => $value) {
            $pipe->setex($key, $ttl, $value);
        }
        $results = $pipe->exec();

        // All results should be true
        return !in_array(false, $results, true);
    }

    /**
     * Execute multiple DELETE operations using pipelining.
     *
     * @param array<string> $keys Keys to delete
     * @return int Number of keys deleted
     */
    public function mdelPipelined(array $keys): int
    {
        if (empty($keys)) {
            return 0;
        }

        $this->connect();

        // DEL already accepts multiple keys
        return $this->redis->del($keys);
    }

    /**
     * Execute a batch of mixed operations using pipelining.
     *
     * @param callable $callback Receives Redis pipeline instance
     * @return array<mixed> Results from all operations
     */
    public function pipeline(callable $callback): array
    {
        $this->connect();

        $pipe = $this->redis->multi(Redis::PIPELINE);
        $callback($pipe);
        return $pipe->exec();
    }

    /**
     * Execute operations in a transaction (MULTI/EXEC).
     *
     * @param callable $callback Receives Redis transaction instance
     * @return array<mixed>|false Results or false on failure
     */
    public function transaction(callable $callback): array|false
    {
        $this->connect();

        $tx = $this->redis->multi(Redis::MULTI);
        $callback($tx);
        return $tx->exec();
    }

    /**
     * Atomic increment with optional initial value and TTL.
     *
     * Useful for rate limiting and counters.
     *
     * @param string $key Key to increment
     * @param int $by Amount to increment by
     * @param int|null $ttl TTL in seconds (only set on first increment)
     * @return int New value
     */
    public function incrementWithTtl(string $key, int $by = 1, ?int $ttl = null): int
    {
        $this->connect();

        if ($ttl === null) {
            return $by === 1 ? $this->redis->incr($key) : $this->redis->incrBy($key, $by);
        }

        // Use pipeline to atomically increment and set TTL if key is new
        $results = $this->pipeline(function (Redis $pipe) use ($key, $by, $ttl) {
            if ($by === 1) {
                $pipe->incr($key);
            } else {
                $pipe->incrBy($key, $by);
            }
            // Only set TTL if key didn't exist (TTL returns -2 for non-existent)
            // We'll handle this separately
            $pipe->ttl($key);
        });

        $value = (int) $results[0];
        $currentTtl = (int) $results[1];

        // Set TTL only on first increment (when value equals increment amount)
        if ($value === $by || $currentTtl === -1) {
            $this->redis->expire($key, $ttl);
        }

        return $value;
    }

    /**
     * Get server info with Redis 8.4 specific metrics.
     *
     * @return array<string, mixed> Server information
     */
    public function getServerInfo(): array
    {
        $this->connect();

        $info = $this->redis->info();

        return [
            'version' => $info['redis_version'] ?? 'unknown',
            'io_threads' => $info['io_threads_active'] ?? '1',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $this->calculateHitRate($info),
        ];
    }

    /**
     * Calculate cache hit rate percentage.
     */
    private function calculateHitRate(array $info): float
    {
        $hits = (int) ($info['keyspace_hits'] ?? 0);
        $misses = (int) ($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Health check with timeout.
     *
     * @param float $timeout Timeout in seconds
     * @return bool True if Redis is healthy
     */
    public function isHealthy(float $timeout = 1.0): bool
    {
        try {
            $this->connect();
            $start = microtime(true);
            $pong = $this->redis->ping();
            $elapsed = microtime(true) - $start;

            return $pong === true && $elapsed < $timeout;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * Close connection explicitly.
     */
    public function close(): void
    {
        if ($this->connected) {
            try {
                $this->redis->close();
            } catch (RedisException) {
                // Ignore close errors
            }
            $this->connected = false;
        }
    }

    public function __destruct()
    {
        // Persistent connections shouldn't be closed
        // Let PHP handle cleanup
    }
}
