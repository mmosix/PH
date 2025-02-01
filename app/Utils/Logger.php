<?php

namespace App\Utils;

class Logger
{
    private const LOG_DIR = 'logs';
    private const LOG_FILE = 'app.log';
    private static ?Logger $instance = null;
    private string $logPath;

    private function __construct()
    {
        $this->logPath = dirname(__DIR__, 2) . '/' . self::LOG_DIR . '/' . self::LOG_FILE;
        $this->ensureLogDirectoryExists();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function ensureLogDirectoryExists(): void
    {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            $this->log('DEBUG', $message, $context);
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextJson}" . PHP_EOL;
        
        file_put_contents($this->logPath, $logMessage, FILE_APPEND);
    }
}