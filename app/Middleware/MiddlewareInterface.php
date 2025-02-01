<?php

namespace App\Middleware;

interface MiddlewareInterface
{
    /**
     * Process a request through middleware
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return array Response data
     */
    public function process(array $request, callable $next): array;
}