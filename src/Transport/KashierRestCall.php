<?php

namespace Asciisd\Kashier\Transport;

use Asciisd\Kashier\Core\KashierHttpConfig;
use Asciisd\Kashier\Core\KashierHttpConnection;
use Asciisd\Kashier\Core\KashierLoggingManager;
use Asciisd\Kashier\Rest\ApiContext;

/**
 * Class KashierRestCall
 *
 * @package Asciisd\Kashier\Transport
 */
class KashierRestCall
{
    /**
     * Kashier Logger
     *
     * @var KashierLoggingManager logger interface
     */
    private $logger;

    /**
     * API Context
     *
     * @var ApiContext
     */
    private $apiContext;


    /**
     * Default Constructor
     *
     * @param ApiContext $apiContext
     */
    public function __construct(ApiContext $apiContext)
    {
        $this->apiContext = $apiContext;
        $this->logger = KashierLoggingManager::getInstance(__CLASS__);
    }

    /**
     * @param array $handlers Array of handlers
     * @param string $path Resource path relative to base service endpoint
     * @param string $method HTTP method - one of GET, POST, PUT, DELETE, PATCH etc
     * @param string $data Request payload
     * @param array $headers HTTP headers
     * @return mixed
     * @throws \Asciisd\Kashier\Exception\KashierConfigurationException
     * @throws \Asciisd\Kashier\Exception\KashierConnectionException
     */
    public function execute($path, $method, $data = '', $handlers = [], $headers = [])
    {
        $config = $this->apiContext->getConfig();
        $httpConfig = new KashierHttpConfig(null, $method, $config);
        $headers = $headers ?: array();
        $httpConfig->setHeaders(
            $headers +
            array(
                'Content-Type' => 'application/json'
            )
        );

        /** @var \Asciisd\Kashier\Handler\IKashierHandler $handler */
        foreach ($handlers as $handler) {
            if (!is_object($handler)) {
                $fullHandler = "\\" . $handler;
                $handler = new $fullHandler($this->apiContext);
            }
            $handler->handle($httpConfig, $data, array('path' => $path, 'apiContext' => $this->apiContext));
        }
        $connection = new KashierHttpConnection($httpConfig);
        return $connection->execute($data);
    }
}
