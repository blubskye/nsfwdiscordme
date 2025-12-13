<?php
namespace App\Storage\Doctrine;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use JsonException;
use Redis;

/**
 * Class RedisCacheHandler
 *
 * Security: Uses JSON serialization instead of PHP serialize() to prevent
 * object injection attacks (CWE-502). JSON is safe because it cannot
 * instantiate arbitrary PHP objects during deserialization.
 */
class RedisCacheHandler extends CacheProvider
{
    private Redis $redis;
    private string $prefix;
    protected int $database = 0;

    public function __construct(Redis $redis, array $options = [])
    {
        $this->redis    = $redis;
        $this->prefix   = $options['prefix'] ?? 'doctrine_';
        $this->database = $options['database'] ?? 0;
    }

    /**
     * Safely encode data for storage using JSON.
     * Wraps data in a structure to preserve type information.
     */
    private function safeEncode(mixed $data): string
    {
        return json_encode([
            '_type' => gettype($data),
            '_data' => $data,
        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Safely decode data from storage using JSON.
     * Returns false if data is corrupted or invalid.
     */
    private function safeDecode(string $data): mixed
    {
        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded) || !isset($decoded['_type'], $decoded['_data'])) {
                // Invalid structure - may be legacy serialized data
                return $this->decodeLegacy($data);
            }

            return $decoded['_data'];
        } catch (JsonException) {
            // Try legacy format for backwards compatibility
            return $this->decodeLegacy($data);
        }
    }

    /**
     * Decode legacy serialized data with strict class whitelist.
     * Only allows scalar types and arrays - no object instantiation.
     */
    private function decodeLegacy(string $data): mixed
    {
        // Security: Only allow scalar types and arrays, no objects
        $result = @unserialize($data, ['allowed_classes' => false]);

        if ($result === false && $data !== 'b:0;') {
            return false;
        }

        return $result;
    }

    protected function doFetch($id): mixed
    {
        $this->redis->select($this->database);
        $result = $this->redis->get($this->prefix . $id);
        if ($result === false) {
            return false;
        }

        return $this->safeDecode($result);
    }

    protected function doFetchMultiple(array $keys): array
    {
        $this->redis->select($this->database);

        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefix . $key;
        }
        $fetchedItems = array_combine($keys, $this->redis->mget($prefixedKeys));

        $foundItems = [];
        foreach ($fetchedItems as $key => $value) {
            if ($value === false && !$this->redis->exists($this->prefix . $key)) {
                continue;
            }
            $foundItems[$key] = $this->safeDecode($value);
        }

        return $foundItems;
    }

    protected function doContains($id): bool
    {
        $this->redis->select($this->database);
        $exists = $this->redis->exists($this->prefix . $id);
        if (is_bool($exists)) {
            return $exists;
        }

        return $exists > 0;
    }

    protected function doSave($id, $data, $lifeTime = 0): bool
    {
        $encoded = $this->safeEncode($data);
        $this->redis->select($this->database);
        if ($lifeTime > 0) {
            return $this->redis->setex($this->prefix . $id, $lifeTime, $encoded);
        }

        return $this->redis->set($this->prefix . $id, $encoded);
    }

    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0): bool
    {
        $this->redis->select($this->database);
        foreach ($keysAndValues as $key => &$value) {
            $value = $this->safeEncode($value);
        }

        if ($lifetime) {
            $success = true;

            foreach ($keysAndValues as $key => $value) {
                if ($this->redis->setex($this->prefix . $key, $lifetime, $value)) {
                    continue;
                }
                $success = false;
            }

            return $success;
        }

        $prefixed = [];
        foreach ($keysAndValues as $key => $value) {
            $prefixed[$this->prefix . $key] = $value;
        }

        return (bool) $this->redis->mset($prefixed);
    }

    protected function doDelete($id): bool
    {
        $this->redis->select($this->database);

        return $this->redis->del($this->prefix . $id) >= 0;
    }

    protected function doFlush(): bool
    {
        $this->redis->select($this->database);

        return $this->redis->flushDB();
    }

    protected function doGetStats(): array
    {
        $this->redis->select($this->database);
        $info = $this->redis->info();

        return [
            Cache::STATS_HITS              => $info['keyspace_hits'],
            Cache::STATS_MISSES            => $info['keyspace_misses'],
            Cache::STATS_UPTIME            => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false,
        ];
    }
}
