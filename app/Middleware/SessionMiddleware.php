<?php

namespace App\Middleware;

use App\Services\AuthService;

class SessionMiddleware implements MiddlewareInterface
{
    private AuthService $auth;
    private array $config;

    public function __construct(AuthService $auth, array $config = [])
    {
        $this->auth = $auth;
        $this->config = array_merge([
            'regenerate_probability' => 0.01, // 1% chance to regenerate session
            'regenerate_age' => 300 // Regenerate if session older than 5 minutes
        ], $config);
    }

    /**
     * Process request through middleware
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return array Response data
     */
    public function process(array $request, callable $next): array
    {
        if ($this->auth->isAuthenticated()) {
            $this->manageSession();
        }

        return $next($request);
    }

    /**
     * Manage session security
     */
    private function manageSession(): void
    {
        $sessionAge = time() - ($_SESSION['created_at'] ?? 0);

        // Regenerate session ID periodically or randomly
        if ($sessionAge > $this->config['regenerate_age'] || mt_rand(1, 100) <= $this->config['regenerate_probability'] * 100) {
            $this->regenerateSession();
        }
    }

    /**
     * Regenerate session ID securely
     */
    private function regenerateSession(): void
    {
        $oldSessionData = $_SESSION;
        
        session_regenerate_id(true);
        
        $_SESSION = $oldSessionData;
        $_SESSION['created_at'] = time();
    }
}