<?php

namespace App\Services;

use Web3\Contract;
use Web3\Utils;
use App\Exceptions\BlockchainException;
use App\Utils\Logger;

class ContractRetryManager
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 5; // seconds
    
    private Logger $logger;
    private GasEstimator $gasEstimator;

    public function __construct(GasEstimator $gasEstimator)
    {
        $this->logger = Logger::getInstance();
        $this->gasEstimator = $gasEstimator;
    }

    public function executeWithRetry(Contract $contract, string $method, array $params, array $args = []): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                // Update gas price on retry
                if ($attempt > 0) {
                    $params['gasPrice'] = $this->gasEstimator->getGasPrice();
                    sleep(self::RETRY_DELAY);
                }

                $receipt = null;
                $contract->send($method, $params, $args, function($err, $resp) use (&$receipt) {
                    if ($err) {
                        throw new \Exception($err->getMessage());
                    }
                    $receipt = $resp;
                });

                if (!$receipt || !$receipt['status']) {
                    throw new \Exception('Transaction failed');
                }

                if ($attempt > 0) {
                    $this->logger->info("Transaction succeeded after {$attempt} retries", [
                        'method' => $method,
                        'transaction' => $receipt['transactionHash']
                    ]);
                }

                return $receipt;
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt < self::MAX_RETRIES) {
                    $this->logger->warning("Transaction failed, retrying...", [
                        'attempt' => $attempt,
                        'method' => $method,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        throw BlockchainException::transactionFailed(
            $method,
            "Transaction failed after {$attempt} attempts: " . $lastError->getMessage()
        );
    }
}