<?php

namespace App\Services;

use Web3\Contract;
use Web3\Utils;
use App\Exceptions\BlockchainException;

class TransactionBatch
{
    private array $transactions = [];
    private GasEstimator $gasEstimator;

    public function __construct(GasEstimator $gasEstimator)
    {
        $this->gasEstimator = $gasEstimator;
    }

    public function add(Contract $contract, string $method, array $params = [], array $args = []): void
    {
        $transaction = [
            'contract' => $contract,
            'method' => $method,
            'params' => $params,
            'args' => $args
        ];

        $this->transactions[] = $transaction;
    }

    public function execute(): array
    {
        $receipts = [];
        $nonce = null;

        foreach ($this->transactions as $transaction) {
            $contract = $transaction['contract'];
            $method = $transaction['method'];
            
            // Get current nonce if not set
            if ($nonce === null) {
                $contract->eth->getTransactionCount(
                    $_ENV['ETHEREUM_ADMIN_ADDRESS'],
                    'pending',
                    function($err, $count) use (&$nonce) {
                        if ($err) {
                            throw new \Exception($err->getMessage());
                        }
                        $nonce = $count;
                    }
                );
            }

            // Prepare transaction
            $txData = [
                'from' => $_ENV['ETHEREUM_ADMIN_ADDRESS'],
                'nonce' => Utils::toHex($nonce),
                'data' => $contract->getData($method, $transaction['args'])
            ];

            // Estimate gas
            $gasLimit = $this->gasEstimator->estimateGas($txData);
            $gasPrice = $this->gasEstimator->getGasPrice();

            $params = array_merge($txData, [
                'gas' => $gasLimit,
                'gasPrice' => $gasPrice
            ], $transaction['params']);

            // Send transaction
            $receipt = null;
            $contract->send($method, $params, $transaction['args'], function($err, $resp) use (&$receipt) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $receipt = $resp;
            });

            if (!$receipt || !$receipt['status']) {
                throw BlockchainException::transactionFailed($method, 'Batch transaction failed');
            }

            $receipts[] = $receipt;
            $nonce++;
        }

        return $receipts;
    }
}