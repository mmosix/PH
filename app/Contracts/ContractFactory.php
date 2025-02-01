<?php

namespace App\Contracts;

use Web3\Contract;
use App\Exceptions\BlockchainException;

class ContractFactory
{
    private array $contractTypes = [];
    private array $abiCache = [];

    public function registerContractType(string $type, string $abiPath): void
    {
        if (!file_exists($abiPath)) {
            throw BlockchainException::contractNotFound("ABI file not found for type: $type");
        }
        $this->contractTypes[$type] = $abiPath;
    }

    public function createContract(string $type, string $nodeUrl, ?string $address = null): Contract
    {
        if (!isset($this->contractTypes[$type])) {
            throw BlockchainException::contractNotFound("Contract type not registered: $type");
        }

        if (!isset($this->abiCache[$type])) {
            $this->abiCache[$type] = json_decode(file_get_contents($this->contractTypes[$type]), true);
        }

        $contract = new Contract($nodeUrl, $this->abiCache[$type]);
        
        if ($address) {
            $contract->at($address);
        }

        return $contract;
    }
}