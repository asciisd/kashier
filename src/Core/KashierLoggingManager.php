<?php

namespace Asciisd\Kashier\Core;

use Asciisd\Kashier\Log\KashierDefaultLogFactory;
use Asciisd\Kashier\Log\KashierLogFactory;
use Psr\Log\LoggerInterface;

/**
 * Simple Logging Manager.
 * This does an error_log for now
 * Potential frameworks to use are PEAR logger, log4php from Apache
 */
class KashierLoggingManager
{
    /**
     * @var array of logging manager instances with class name as key
     */
    private static array $instances = array();

    /**
     * The logger to be used for all messages
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Logger Name
     *
     * @var string
     */
    private string $loggerName;

    /**
     * Returns the singleton object
     *
     * @param string $loggerName
     * @return $this
     */
    public static function getInstance(string $loggerName = __CLASS__): static
    {
        if (array_key_exists($loggerName, self::$instances)) {
            return self::$instances[$loggerName];
        }
        $instance = new self($loggerName);
        self::$instances[$loggerName] = $instance;
        return $instance;
    }

    /**
     * Default Constructor
     *
     * @param string $loggerName Generally represents the class name.
     */
    private function __construct(string $loggerName)
    {
        $config = KashierConfigManager::getInstance()->getConfigHashmap();
        // Checks if custom factory defined, and is it an implementation of @KashierLogFactory
        $factory = array_key_exists('log.AdapterFactory', $config) && in_array(KashierLogFactory::class, class_implements($config['log.AdapterFactory'], true), true) ? $config['log.AdapterFactory'] : KashierDefaultLogFactory::class;
        /** @var KashierLogFactory $factoryInstance */
        $factoryInstance = new $factory();
        $this->logger = $factoryInstance->getLogger($loggerName);
        $this->loggerName = $loggerName;
    }

    /**
     * Log Error
     *
     * @param string $message
     */
    public function error(string $message): void
    {
        $this->logger->error($message);
    }

    /**
     * Log Warning
     *
     * @param string $message
     */
    public function warning(string $message): void
    {
        $this->logger->warning($message);
    }

    /**
     * Log Info
     *
     * @param string $message
     */
    public function info(string $message): void
    {
        $this->logger->info($message);
    }

    /**
     * Log Fine
     *
     * @param string $message
     */
    public function fine(string $message): void
    {
        $this->info($message);
    }

    /**
     * Log Debug
     *
     * @param string $message
     */
    public function debug(string $message): void
    {
        $config = KashierConfigManager::getInstance()->getConfigHashmap();
        // Disable debug in live mode.
        if (array_key_exists('mode', $config) && $config['mode'] !== 'live') {
            $this->logger->debug($message);
        }
    }
}
