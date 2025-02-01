<?php

namespace App\Services;

use Web3\Web3;
use Web3\Utils;
use GuzzleHttp\Client;
use App\Exceptions\BlockchainException;

class GasEstimator
{
    private Web3 $web3;
    private Client $httpClient;
    private const GAS_STATION_API = 'https://ethgasstation.info/api/ethgasAPI.json';

    public function __construct(Web3 $web3)
    {
        $this->web3 = $web3;
        $this->httpClient = new Client();
    }

    public function estimateGas(array $transaction): string
    {
        $gasLimit = null;
        $this->web3->eth->estimateGas($transaction, function($err, $gas) use (&$gasLimit) {
            if ($err) {
                throw BlockchainException::contractDeploymentFailed($err->getMessage());
            }
            $gasLimit = $gas;
        });

        // Add 20% buffer for safety
        return Utils::toHex(intval($gasLimit * 1.2));
    }

    public function getGasPrice(): string
    {
        try {
            $response = $this->httpClient->get(self::GAS_STATION_API);
            $data = json_decode($response->getBody(), true);
            
            // Convert price to Wei (price is in Gwei * 10)
            $gasPriceGwei = $data['fast'] / 10;
            return Utils::toHex(Utils::toWei((string)$gasPriceGwei, 'gwei'));
        } catch (\Exception $e) {
            // Fallback to node's gas price estimation
            $gasPrice = null;
            $this->web3->eth->gasPrice(function($err, $price) use (&$gasPrice) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $gasPrice = $price;
            });
            return Utils::toHex($gasPrice);
        }
    }
}