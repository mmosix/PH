<?php

namespace App\Contracts;

use Web3\Contract;
use Web3\Utils;
use App\Exceptions\BlockchainException;

class EscrowContract extends Contract
{
    protected array $requiredMethods = [
        'release',
        'withdraw',
        'terminate'
    ];

    protected array $events = [
        'FundsReleased',
        'FundsWithdrawn',
        'ContractTerminated'
    ];

    public function validateState(): void
    {
        try {
            // Check balance
            $balance = null;
            $this->eth->getBalance($this->getAddress(), function($err, $resp) use (&$balance) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $balance = $resp;
            });

            if (!$balance || $balance === '0x0') {
                throw new \Exception('Contract has no funds');
            }

            // Check contract status
            $status = null;
            $this->call('getStatus', [], function($err, $resp) use (&$status) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $status = $resp[0];
            });

            // Active = 1, Released = 2, Terminated = 3
            if ($status !== '1') {
                throw new \Exception('Contract is not in active state');
            }
        } catch (\Exception $e) {
            throw BlockchainException::transactionFailed('validation', $e->getMessage());
        }
    }

    public function getContractor(): string
    {
        $contractor = null;
        $this->call('contractor', [], function($err, $resp) use (&$contractor) {
            if ($err) {
                throw BlockchainException::transactionFailed('call', $err->getMessage());
            }
            $contractor = $resp[0];
        });
        return $contractor;
    }

    public function getBudget(): float
    {
        $budget = null;
        $this->call('budget', [], function($err, $resp) use (&$budget) {
            if ($err) {
                throw BlockchainException::transactionFailed('call', $err->getMessage());
            }
            $budget = Utils::fromWei($resp[0], 'ether');
        });
        return floatval($budget);
    }
}