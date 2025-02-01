<?php

namespace App\Services;

use Web3\Contract;
use Web3\Utils;
use App\Exceptions\BlockchainException;

class ContractValidator
{
    private array $validatedContracts = [];
    
    public function validateContract(Contract $contract, string $address): void
    {
        // Skip if already validated
        if (isset($this->validatedContracts[$address])) {
            return;
        }

        try {
            // Verify contract code exists
            $code = null;
            $contract->eth->getCode($address, function($err, $resp) use (&$code) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $code = $resp;
            });

            if (!$code || $code === '0x') {
                throw BlockchainException::contractNotFound("No contract code found at address: $address");
            }

            // Verify required methods exist
            $methods = ['release', 'withdraw', 'terminate'];
            foreach ($methods as $method) {
                if (!method_exists($contract->methods, $method)) {
                    throw BlockchainException::contractNotFound("Required method '$method' not found in contract");
                }
            }

            // Cache successful validation
            $this->validatedContracts[$address] = true;
        } catch (\Exception $e) {
            throw BlockchainException::contractNotFound($e->getMessage());
        }
    }

    public function validateParameters(array $params, array $requirements): void
    {
        foreach ($requirements as $param => $type) {
            if (!isset($params[$param])) {
                throw BlockchainException::transactionFailed('validation', "Missing required parameter: $param");
            }

            switch ($type) {
                case 'address':
                    if (!Utils::isAddress($params[$param])) {
                        throw BlockchainException::invalidAddress($params[$param]);
                    }
                    break;
                case 'uint':
                    if (!is_numeric($params[$param]) || $params[$param] < 0) {
                        throw BlockchainException::transactionFailed('validation', "Invalid uint value for $param");
                    }
                    break;
                case 'bool':
                    if (!is_bool($params[$param])) {
                        throw BlockchainException::transactionFailed('validation', "Invalid boolean value for $param");
                    }
                    break;
            }
        }
    }
}