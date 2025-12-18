<?php
declare(strict_types=1);

namespace App\Storage\Snowflake;

use Redis;
use RedisException;
use RuntimeException;

/**
 * Redis-backed Snowflake ID generator.
 *
 * Optimized for Redis 8.4+ with reduced database selection overhead.
 */
class RedisGenerator implements SnowflakeGeneratorInterface
{
    private bool $selected = false;

    public function __construct(
        private readonly Redis $redis,
        private readonly int $database = 0
    ) {
    }

    /**
     * Ensure database is selected (lazy, once per instance).
     */
    private function ensureSelected(): void
    {
        if (!$this->selected) {
            $this->redis->select($this->database);
            $this->selected = true;
        }
    }

    /**
     * Generate a unique Snowflake ID.
     *
     * @throws RuntimeException If ID exceeds PHP_INT_MAX
     */
    public function generate(): int
    {
        $this->ensureSelected();

        try {
            $sequence = $this->redis->incr('snowflake');
        } catch (RedisException $e) {
            throw new RuntimeException('Failed to generate snowflake ID: ' . $e->getMessage(), 0, $e);
        }

        $time = (int) (microtime(true) * 1000);
        $machine = (int) (getenv('SNOWFLAKE_MACHINE_ID') ?: 0);

        // Bit layout: 41 bits time | 13 bits machine | 9 bits sequence
        // Using bitwise operations is faster than string manipulation
        $id = (($time & 0x1FFFFFFFFFF) << 22) // 41 bits for timestamp
            | (($machine & 0x1FFF) << 9)       // 13 bits for machine ID
            | ($sequence & 0x1FF);              // 9 bits for sequence

        if ($id < 0) {
            throw new RuntimeException('The bits of integer is larger than PHP_INT_MAX');
        }

        return $id;
    }

    /**
     * Generate multiple IDs in a single Redis round-trip.
     *
     * Uses pipelining for better performance with Redis 8's async I/O.
     *
     * @param int $count Number of IDs to generate
     * @return array<int> Generated IDs
     */
    public function generateBatch(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        if ($count === 1) {
            return [$this->generate()];
        }

        $this->ensureSelected();

        // Use pipeline to increment multiple times efficiently
        $pipe = $this->redis->multi(Redis::PIPELINE);
        for ($i = 0; $i < $count; $i++) {
            $pipe->incr('snowflake');
        }
        $sequences = $pipe->exec();

        $time = (int) (microtime(true) * 1000);
        $machine = (int) (getenv('SNOWFLAKE_MACHINE_ID') ?: 0);

        $ids = [];
        foreach ($sequences as $sequence) {
            $id = (($time & 0x1FFFFFFFFFF) << 22)
                | (($machine & 0x1FFF) << 9)
                | ($sequence & 0x1FF);

            if ($id < 0) {
                throw new RuntimeException('The bits of integer is larger than PHP_INT_MAX');
            }

            $ids[] = $id;
            // Increment time by 1ms for each ID to ensure uniqueness
            $time++;
        }

        return $ids;
    }
}
