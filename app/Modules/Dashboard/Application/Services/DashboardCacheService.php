<?php

namespace App\Modules\Dashboard\Application\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class DashboardCacheService
{
    private const TAG = 'dashboard';

    private const TTL_SECONDS = 60;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function remember(string $key, callable $callback, int $ttl = self::TTL_SECONDS): mixed
    {
        $store = $this->cache->getStore();

        if (method_exists($store, 'tags')) {
            return $this->cache->tags([self::TAG])->remember($key, $ttl, $callback);
        }

        return $this->cache->remember(self::TAG.':'.$key, $ttl, $callback);
    }
}
