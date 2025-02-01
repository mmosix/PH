<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $auth;
    private array $publicRoutes;

    public function __construct(AuthService $auth, array $publicRoutes = [])
    {
        $this->auth = $auth;
        $this->publicRoutes = array_merge([
            'auth.login',
            'auth.register',
            'auth.verify',
            'auth.password.reset',
            'health'
        ], $publicRoutes);
    }

    /**
     * Process request through middleware
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return array Response data
     * @throws \RuntimeException If authentication required
     */
    public function process(array $request, callable $next): array
    {
        $route = $request['route'] ?? '';
        
        // Skip authentication for public routes
        if (in_array($route, $this->publicRoutes, true)) {
            return $next($request);
        }

        // Check authentication
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            throw new \RuntimeException('Authentication required');
        }

        // Add user ID to request
        $request['user_id'] = $this->auth->getCurrentUserId();
        
        // Refresh session
        $this->auth->refreshSession();
        
        return $next($request);
    }
}