<?php

namespace App\Middleware;

use App\Utils\Logger;

class MiddlewareManager
{
    private array $middleware = [];
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add middleware to the stack
     * @param MiddlewareInterface $middleware Middleware instance
     * @param int $priority Priority (lower runs first)
     */
    public function add(MiddlewareInterface $middleware, int $priority = 10): void
    {
        $this->middleware[$priority][] = $middleware;
        ksort($this->middleware); // Keep middleware sorted by priority
    }

    /**
     * Process request through middleware stack
     * @param array $request Request data
     * @param callable $handler Final request handler
     * @return array Response data
     */
    public function process(array $request, callable $handler): array
    {
        $chain = $this->createChain($handler);
        
        try {
            $this->logger->debug('Starting middleware chain', [
                'route' => $request['route'] ?? 'unknown',
                'middleware_count' => array_sum(array_map('count', $this->middleware))
            ]);
            
            return $chain($request);
        } catch (\Throwable $e) {
            $this->logger->error('Middleware chain error', [
                'error' => $e->getMessage(),
                'route' => $request['route'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Create middleware chain
     * @param callable $handler Final request handler
     * @return callable Chain function
     */
    private function createChain(callable $handler): callable
    {
        $chain = $handler;
        
        // Build chain from inside out
        foreach ($this->middleware as $priorityGroup) {
            foreach (array_reverse($priorityGroup) as $middleware) {
                $chain = function ($request) use ($middleware, $chain) {
                    return $middleware->process($request, $chain);
                };
            }
        }
        
        return $chain;
    }
}