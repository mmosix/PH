<?php

namespace App\Middleware;

use Predis\Client;
use App\Utils\Logger;

class CacheMiddleware implements MiddlewareInterface
{
    private Client $redis;
    private Logger $logger;
    private const TTL = 3600; // Default cache TTL in seconds

    /**
     * Routes and their cache TTL in seconds
     */
    private const CACHE_RULES = [
        'projects.list' => 300,      // Cache project list for 5 minutes
        'projects.get' => 300,       // Cache single project for 5 minutes
        'files.list' => 300,         // Cache file list for 5 minutes
        'reports.status' => 900,     // Cache status report for 15 minutes
        'reports.financial' => 900,   // Cache financial report for 15 minutes
    ];

    public function __construct(Client $redis, Logger $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    /**
     * Process a request through middleware
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return array Response data
     */
    public function process(array $request, callable $next): array
    {
        $route = $request['route'] ?? '';
        
        // Skip cache for non-GET requests or non-cacheable routes
        if (!$this->isCacheable($request)) {
            return $next($request);
        }

        $cacheKey = $this->getCacheKey($request);
        
        // Try to get from cache
        if ($cached = $this->getFromCache($cacheKey)) {
            $this->logger->debug('Cache hit', ['route' => $route, 'key' => $cacheKey]);
            return $cached;
        }

        // Get fresh data
        $response = $next($request);
        
        // Cache the response
        $this->cache($cacheKey, $response, $route);
        
        return $response;
    }

    /**
     * Check if request is cacheable
     * @param array $request Request data
     * @return bool Whether request is cacheable
     */
    private function isCacheable(array $request): bool
    {
        $method = $request['method'] ?? 'GET';
        $route = $request['route'] ?? '';

        return $method === 'GET' && isset(self::CACHE_RULES[$route]);
    }

    /**
     * Get data from cache
     * @param string $key Cache key
     * @return array|null Cached data or null
     */
    private function getFromCache(string $key): ?array
    {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Cache data
     * @param string $key Cache key
     * @param array $data Data to cache
     * @param string $route Route name for TTL lookup
     */
    private function cache(string $key, array $data, string $route): void
    {
        $ttl = self::CACHE_RULES[$route] ?? self::TTL;
        $this->redis->setex($key, $ttl, json_encode($data));
        
        $this->logger->debug('Cached response', [
            'route' => $route,
            'key' => $key,
            'ttl' => $ttl
        ]);
    }

    /**
     * Generate cache key for request
     * @param array $request Request data
     * @return string Cache key
     */
    private function getCacheKey(array $request): string
    {
        $route = $request['route'] ?? 'default';
        $params = $request['params'] ?? [];
        $userId = $request['user_id'] ?? 'anonymous';

        // Sort parameters for consistent keys
        ksort($params);
        $paramString = md5(json_encode($params));

        return "cache:{$route}:{$userId}:{$paramString}";
    }

    /**
     * Clear cache for a route
     * @param string $route Route name
     * @param array $params Route parameters
     */
    public function clearCache(string $route, array $params = []): void
    {
        $pattern = "cache:{$route}:*";
        $keys = $this->redis->keys($pattern);
        
        if (!empty($keys)) {
            $this->redis->del($keys);
            $this->logger->info('Cache cleared', [
                'route' => $route,
                'keys_count' => count($keys)
            ]);
        }
    }
}