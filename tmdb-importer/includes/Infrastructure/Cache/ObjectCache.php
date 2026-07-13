<?php
namespace TMDBImporter\Infrastructure\Cache;

class ObjectCache
{
    private string $group = 'tmdb_importer';

    public function get(string $key, string $group = ''): mixed
    {
        $group = $group ?: $this->group;
        return wp_cache_get($key, $group);
    }

    public function set(string $key, mixed $value, int $ttl = 0, string $group = ''): bool
    {
        $group = $group ?: $this->group;
        return wp_cache_set($key, $value, $group, $ttl);
    }

    public function delete(string $key, string $group = ''): bool
    {
        $group = $group ?: $this->group;
        return wp_cache_delete($key, $group);
    }

    public function flush(string $group = ''): bool
    {
        $group = $group ?: $this->group;
        return wp_cache_flush_group($group);
    }

    public function getMultiple(array $keys, string $group = ''): array
    {
        $group = $group ?: $this->group;
        $result = [];
        foreach ($keys as $key) {
            $value = wp_cache_get($key, $group);
            if ($value !== false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function setMultiple(array $data, int $ttl = 0, string $group = ''): void
    {
        $group = $group ?: $this->group;
        foreach ($data as $key => $value) {
            wp_cache_set($key, $value, $group, $ttl);
        }
    }

    public function increment(string $key, int $offset = 1, string $group = ''): int|false
    {
        $group = $group ?: $this->group;
        return wp_cache_incr($key, $offset, $group);
    }

    public function decrement(string $key, int $offset = 1, string $group = ''): int|false
    {
        $group = $group ?: $this->group;
        return wp_cache_decr($key, $offset, $group);
    }

    public function replace(string $key, mixed $value, int $ttl = 0, string $group = ''): bool
    {
        $group = $group ?: $this->group;
        return wp_cache_replace($key, $value, $group, $ttl);
    }

    public function add(string $key, mixed $value, int $ttl = 0, string $group = ''): bool
    {
        $group = $group ?: $this->group;
        return wp_cache_add($key, $value, $group, $ttl);
    }
}