<?php
require __DIR__.'/../config/web3.php';

class BlockchainService {
    private $web3;
    private $contractABI;
    private $contractAddress;

    public function __construct() {
        $this->web3 = include __DIR__.'/../config/web3.php';
        $this->contractABI = json_decode(file_get_contents(__DIR__.'/../contracts/ConstructionContractABI.json'), true);
        $this->contractAddress = getenv('CONTRACT_ADDRESS');
    }

    public function deployContract($contractorAddress, $budget) {
        // Implementation for deploying a new contract
    }

    public function checkTransactionStatus($txHash) {
        $this->web3->eth->getTransactionReceipt($txHash, function($err, $receipt) {
            if ($err) throw $err;
            return $receipt->status === '0x1';
        });
    }

    public function getContractBalance($contractAddress) {
        $this->web3->eth->getBalance($contractAddress, function($err, $balance) {
            if ($err) throw $err;
            return $balance;
        });
    }
}