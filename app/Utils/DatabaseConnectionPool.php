<?php

namespace App\Utils;

use PDO;

class DatabaseConnectionPool
{
    private static array $connections = [];
    private static array $inUse = [];
    private static int $maxConnections = 10;
    private static array $config;

    public static function initialize(array $config): void
    {
        self::$config = $config;
        self::$maxConnections = $_ENV['DB_MAX_CONNECTIONS'] ?? 10;
    }

    /**
     * Get a database connection from the pool
     * @return PDO Active database connection
     * @throws \RuntimeException If no connections available
     */
    public static function getConnection(): PDO
    {
        // First try to find an unused connection
        foreach (self::$connections as $key => $connection) {
            if (!isset(self::$inUse[$key]) || !self::$inUse[$key]) {
                self::$inUse[$key] = true;
                return $connection;
            }
        }

        // If no unused connections, create new one if under limit
        if (count(self::$connections) < self::$maxConnections) {
            $connection = self::createConnection();
            $key = count(self::$connections);
            self::$connections[$key] = $connection;
            self::$inUse[$key] = true;
            return $connection;
        }

        // If at connection limit, wait for one to become available
        for ($i = 0; $i < 10; $i++) {
            usleep(100000); // Wait 100ms
            foreach (self::$connections as $key => $connection) {
                if (!self::$inUse[$key]) {
                    self::$inUse[$key] = true;
                    return $connection;
                }
            }
        }

        throw new \RuntimeException('No database connections available');
    }

    /**
     * Release a connection back to the pool
     * @param PDO $connection The connection to release
     */
    public static function releaseConnection(PDO $connection): void
    {
        foreach (self::$connections as $key => $poolConnection) {
            if ($poolConnection === $connection) {
                self::$inUse[$key] = false;
                break;
            }
        }
    }

    /**
     * Create a new database connection
     * @return PDO New database connection
     */
    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=utf8mb4",
            self::$config['host'],
            self::$config['database']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            // Add connection pooling options
            PDO::ATTR_PERSISTENT => true
        ];

        return new PDO(
            $dsn,
            self::$config['username'],
            self::$config['password'],
            $options
        );
    }

    /**
     * Close all connections in the pool
     */
    public static function closeAll(): void
    {
        foreach (self::$connections as $key => $connection) {
            if (isset(self::$inUse[$key]) && self::$inUse[$key]) {
                continue; // Skip connections still in use
            }
            $connection = null;
            unset(self::$connections[$key]);
            unset(self::$inUse[$key]);
        }
    }
}