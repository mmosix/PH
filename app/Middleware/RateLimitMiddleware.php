<?php

namespace App\Middleware;

use Predis\Client;

class RateLimitMiddleware implements MiddlewareInterface
{
    private const DEFAULT_LIMIT = 60; // Requests per minute
    private const DEFAULT_WINDOW = 60; // Window size in seconds
    private Client $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Process a request through middleware
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return array Response data
     * @throws \RuntimeException If rate limit exceeded
     */
    public function process(array $request, callable $next): array
    {
        $key = $this->getRequestKey($request);
        $limit = $this->getRouteLimit($request['route'] ?? '');
        
        // Check rate limit
        if ($this->isRateLimited($key, $limit)) {
            throw new \RuntimeException('Rate limit exceeded. Please try again later.');
        }
        
        // Increment request count
        $this->incrementCounter($key);
        
        return $next($request);
    }

    /**
     * Check if request is rate limited
     * @param string $key Cache key
     * @param int $limit Request limit
     * @return bool Whether request is limited
     */
    private function isRateLimited(string $key, int $limit): bool
    {
        $count = (int)$this->redis->get($key);
        return $count >= $limit;
    }

    /**
     * Increment request counter
     * @param string $key Cache key
     */
    private function incrementCounter(string $key): void
    {
        // Increment counter and set expiry if not exists
        $this->redis->multi()
            ->incr($key)
            ->expire($key, self::DEFAULT_WINDOW)
            ->exec();
    }

    /**
     * Get route-specific rate limit
     * @param string $route Route name
     * @return int Rate limit per minute
     */
    private function getRouteLimit(string $route): int
    {
        // Route-specific limits can be configured here
        $limits = [
            'auth.login' => 5,        // 5 login attempts per minute
            'auth.register' => 3,     // 3 registration attempts per minute
            'files.upload' => 10,     // 10 file uploads per minute
            'blockchain.deploy' => 5,  // 5 contract deployments per minute
        ];

        return $limits[$route] ?? self::DEFAULT_LIMIT;
    }

    /**
     * Get unique request key for rate limiting
     * @param array $request Request data
     * @return string Cache key
     */
    private function getRequestKey(array $request): string
    {
        $ip = $request['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $route = $request['route'] ?? 'default';
        $userId = $request['user_id'] ?? 'anonymous';

        return "rate_limit:{$ip}:{$route}:{$userId}";
    }
}