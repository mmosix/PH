<?php

namespace App\Exceptions;

class BlockchainException extends BaseException
{
    public static function contractDeploymentFailed(string $message, array $context = []): self
    {
        return new self("Contract deployment failed: {$message}", $context);
    }

    public static function transactionFailed(string $txHash, string $message, array $context = []): self
    {
        return new self(
            "Transaction failed: {$message}",
            array_merge(['transaction_hash' => $txHash], $context)
        );
    }

    public static function invalidAddress(string $address): self
    {
        return new self("Invalid Ethereum address: {$address}");
    }

    public static function contractNotFound(string $address): self
    {
        return new self("Smart contract not found at address: {$address}");
    }

    public static function insufficientFunds(string $address, float $required, float $available): self
    {
        return new self(
            "Insufficient funds for address: {$address}",
            ['required' => $required, 'available' => $available]
        );
    }
}