<?php

namespace App\Events;

use Web3\Contract;
use Web3\Utils;
use App\Utils\Logger;

class ContractEventHandler
{
    private Logger $logger;
    private array $eventSubscriptions = [];

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function subscribe(Contract $contract, string $eventName, callable $callback): void
    {
        $subscription = [
            'contract' => $contract,
            'callback' => $callback
        ];
        
        $this->eventSubscriptions[$eventName][] = $subscription;
        
        $contract->events->$eventName(function($err, $event) use ($eventName, $callback) {
            if ($err) {
                $this->logger->error("Event error: {$eventName}", ['error' => $err->getMessage()]);
                return;
            }
            
            $callback($event);
        });
    }

    public function unsubscribe(string $eventName): void
    {
        unset($this->eventSubscriptions[$eventName]);
    }
}