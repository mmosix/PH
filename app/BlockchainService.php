<?php

namespace App;

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use App\Exceptions\BlockchainException;
use App\Utils\Logger;
use App\Contracts\ContractFactory;
use App\Services\GasEstimator;
use App\Events\ContractEventHandler;

class BlockchainService {
    private Web3 $web3;
    private Contract $contract;
    private static Logger $logger;
    private string $contractAddress;
    private array $contractABI;
    private ContractFactory $contractFactory;
    private GasEstimator $gasEstimator;
    private ContractEventHandler $eventHandler;

    public static function initialize(): void
    {
        self::$logger = Logger::getInstance();
    }

    /**
     * Constructor
     * @param string $nodeUrl Ethereum node URL
     * @param string $contractAddress Smart contract address
     * @param array $contractABI Contract ABI
     * @throws BlockchainException If contract initialization fails
     */
    public function __construct(string $nodeUrl, ?string $contractAddress = null, ?array $contractABI = null)
    {
        try {
            $this->web3 = new Web3($nodeUrl);
            $this->contractFactory = new ContractFactory();
            $this->gasEstimator = new GasEstimator($this->web3);
            $this->eventHandler = new ContractEventHandler();
            
            if ($contractAddress && $contractABI) {
                $this->contractAddress = $contractAddress;
                $this->contractABI = $contractABI;
                $this->contract = new Contract($nodeUrl, $contractABI);
                $this->contract->at($contractAddress);
            }
        } catch (\Exception $e) {
            throw BlockchainException::contractDeploymentFailed($e->getMessage());
        }
    }

    /**
     * Deploy a new escrow contract
     * @param string $contractorAddress Contractor's Ethereum address
     * @param float $budget Project budget in ETH
     * @return string Deployed contract address
     * @throws BlockchainException If deployment fails
     */
    public function deployContract(string $type, string $contractorAddress, float $budget): string
    {
        try {
            // Validate ethereum address
            if (!Utils::isAddress($contractorAddress)) {
                throw BlockchainException::invalidAddress($contractorAddress);
            }

            // Convert budget to Wei
            $budgetWei = Utils::toWei((string)$budget, 'ether');
            
            // Create new contract instance
            $this->contract = $this->contractFactory->createContract($type, $this->web3->provider->endpoint);
            
            // Prepare transaction parameters
            $transaction = [
                'from' => $_ENV['ETHEREUM_ADMIN_ADDRESS'],
                'value' => Utils::toHex($budgetWei),
                'data' => $this->contract->getData('constructor', [$contractorAddress])
            ];
            
            // Estimate gas for deployment
            $gasLimit = $this->gasEstimator->estimateGas($transaction);
            $gasPrice = $this->gasEstimator->getGasPrice();
            
            $params = array_merge($transaction, [
                'gas' => $gasLimit,
                'gasPrice' => $gasPrice
            ]);

            $receipt = null;
            $this->contract->deploy($params, [$contractorAddress], function($err, $deployedContract) use (&$receipt) {
                if ($err) {
                    throw BlockchainException::contractDeploymentFailed($err->getMessage());
                }
                $receipt = $deployedContract;
            });

            if (!$receipt) {
                throw BlockchainException::contractDeploymentFailed('Contract deployment timed out');
            }

            self::$logger->info('Contract deployed', [
                'address' => $receipt->contractAddress,
                'contractor' => $contractorAddress,
                'budget' => $budget
            ]);

            return $receipt->contractAddress;
        } catch (\Exception $e) {
            if (!$e instanceof BlockchainException) {
                $e = BlockchainException::contractDeploymentFailed($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Check transaction status
     * @param string $txHash Transaction hash
     * @return array Transaction receipt
     * @throws BlockchainException If transaction lookup fails
     */
    public function checkTransactionStatus(string $txHash): array
    {
        try {
            $receipt = null;
            $this->web3->eth->getTransactionReceipt($txHash, function($err, $resp) use (&$receipt) {
                if ($err) {
                    throw BlockchainException::transactionFailed($txHash, $err->getMessage());
                }
                $receipt = $resp;
            });

            if (!$receipt) {
                throw BlockchainException::transactionFailed($txHash, 'Transaction not found');
            }

            self::$logger->debug('Transaction status checked', [
                'hash' => $txHash,
                'status' => $receipt->status
            ]);

            return (array)$receipt;
        } catch (\Exception $e) {
            if (!$e instanceof BlockchainException) {
                $e = BlockchainException::transactionFailed($txHash, $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Get contract balance
     * @param string $contractAddress Contract address
     * @return float Balance in ETH
     * @throws BlockchainException If balance lookup fails
     */
    public function getContractBalance(string $contractAddress): float
    {
        try {
            if (!Utils::isAddress($contractAddress)) {
                throw BlockchainException::invalidAddress($contractAddress);
            }

            $balance = null;
            $this->web3->eth->getBalance($contractAddress, function($err, $resp) use (&$balance) {
                if ($err) {
                    throw new \Exception($err->getMessage());
                }
                $balance = $resp;
            });

            if ($balance === null) {
                throw new \Exception('Failed to fetch balance');
            }

            // Convert from Wei to ETH
            $balanceEth = floatval(Utils::fromWei($balance, 'ether'));

            self::$logger->debug('Contract balance checked', [
                'address' => $contractAddress,
                'balance' => $balanceEth
            ]);

            return $balanceEth;
        } catch (\Exception $e) {
            if (!$e instanceof BlockchainException) {
                $e = new BlockchainException("Failed to get contract balance: " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Release funds to contractor
     * @param string $contractAddress Contract address
     * @throws BlockchainException If release fails
     */
    private ContractRetryManager $retryManager;
    private ContractValidator $validator;
    
    public function releaseFunds(string $contractAddress): void
    {
        try {
            if (!Utils::isAddress($contractAddress)) {
                throw BlockchainException::invalidAddress($contractAddress);
            }
            
            if (!$this->retryManager) {
                $this->retryManager = new ContractRetryManager($this->gasEstimator);
                $this->validator = new ContractValidator();
            }

            // Initialize and validate contract
            $this->contract = $this->contractFactory->createContract('escrow', $this->web3->provider->endpoint, $contractAddress);
            $this->validator->validateContract($this->contract, $contractAddress);

            // Prepare transaction data
            $transaction = [
                'from' => $_ENV['ETHEREUM_ADMIN_ADDRESS'],
                'to' => $contractAddress,
                'data' => $this->contract->getData('release')
            ];

            // Estimate gas and get optimal price
            $gasLimit = $this->gasEstimator->estimateGas($transaction);
            $gasPrice = $this->gasEstimator->getGasPrice();

            $params = array_merge($transaction, [
                'gas' => $gasLimit,
                'gasPrice' => $gasPrice
            ]);

            // Execute with retry mechanism
            $receipt = $this->retryManager->executeWithRetry($this->contract, 'release', $params);

            if (!$receipt || !$receipt['status']) {
                throw BlockchainException::transactionFailed('release', 'Transaction failed');
            }

            // Subscribe to FundsReleased event
            $this->eventHandler->subscribe($this->contract, 'FundsReleased', function($event) use ($contractAddress) {
                self::$logger->info('Funds release event received', [
                    'contract' => $contractAddress,
                    'amount' => Utils::fromWei($event['amount'], 'ether'),
                    'recipient' => $event['recipient']
                ]);
            });

            self::$logger->info('Funds release initiated', [
                'contract' => $contractAddress,
                'transaction' => $receipt['transactionHash']
            ]);
        } catch (\Exception $e) {
            if (!$e instanceof BlockchainException) {
                $e = new BlockchainException("Failed to release funds: " . $e->getMessage());
            }
            throw $e;
        }
    }
}