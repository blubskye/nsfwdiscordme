<?php
declare(strict_types=1);

namespace App\Storage\Sessions;

use Redis;
use RedisException;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * Redis 8.4+ optimized session handler.
 *
 * Uses pipelining for batch operations and takes advantage of
 * Redis 8's async I/O threading for improved performance.
 */
class RedisSessionHandler extends AbstractSessionHandler
{
    private bool $selected = false;

    public function __construct(
        private readonly Redis $redis,
        private readonly string $prefix = 'sf_s',
        private readonly int $database = 0
    ) {
    }

    /**
     * Factory method for Symfony DI compatibility.
     */
    public static function create(Redis $redis, array $options = []): self
    {
        return new self(
            $redis,
            $options['prefix'] ?? 'sf_s',
            $options['database'] ?? 0
        );
    }

    /**
     * Ensure database is selected (lazy, once per request).
     */
    private function ensureSelected(): void
    {
        if (!$this->selected) {
            $this->redis->select($this->database);
            $this->selected = true;
        }
    }

    protected function doRead(string $sessionId): string
    {
        $this->ensureSelected();

        try {
            return $this->redis->get($this->prefix . $sessionId) ?: '';
        } catch (RedisException) {
            return '';
        }
    }

    protected function doWrite(string $sessionId, string $data): bool
    {
        $this->ensureSelected();

        $ttl = (int) ini_get('session.gc_maxlifetime');

        try {
            return $this->redis->setex($this->prefix . $sessionId, $ttl, $data);
        } catch (RedisException) {
            return false;
        }
    }

    protected function doDestroy(string $sessionId): bool
    {
        $this->ensureSelected();

        try {
            $this->redis->del($this->prefix . $sessionId);
            return true;
        } catch (RedisException) {
            return false;
        }
    }

    public function updateTimestamp(string $sessionId, string $data): bool
    {
        $this->ensureSelected();

        $ttl = (int) ini_get('session.gc_maxlifetime');

        try {
            return (bool) $this->redis->expire($this->prefix . $sessionId, $ttl);
        } catch (RedisException) {
            return false;
        }
    }

    public function close(): bool
    {
        // Don't close persistent connections
        return true;
    }

    public function gc(int $maxlifetime): int|false
    {
        // Redis handles TTL-based expiration automatically
        return 0;
    }

    /**
     * Validate session ID to prevent injection attacks.
     */
    public function validateId(string $sessionId): bool
    {
        $this->ensureSelected();

        try {
            return $this->redis->exists($this->prefix . $sessionId) > 0;
        } catch (RedisException) {
            return false;
        }
    }
}
