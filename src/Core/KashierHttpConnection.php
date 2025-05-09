<?php

namespace Asciisd\Kashier\Core;

use Asciisd\Kashier\Exception\KashierConfigurationException;
use Asciisd\Kashier\Exception\KashierConnectionException;

/**
 * A wrapper class based on the curl extension.
 * Requires the PHP curl module to be enabled.
 * See for full requirements the PHP manual: http://php.net/curl
 */
class KashierHttpConnection
{
    /**
     * @var KashierHttpConfig
     */
    private KashierHttpConfig $httpConfig;

    /**
     * LoggingManager
     *
     * @var KashierLoggingManager
     */
    private KashierLoggingManager $logger;

    /**
     * @var array
     */
    private array $responseHeaders = array();

    /**
     * @var bool
     */
    private bool $skippedHttpStatusLine = false;

    /**
     * Default Constructor
     *
     * @param KashierHttpConfig $httpConfig
     * @throws KashierConfigurationException
     */
    public function __construct(KashierHttpConfig $httpConfig)
    {
        if (! function_exists('curl_init')) {
            throw new KashierConfigurationException('Curl module is not available on this system');
        }
        $this->httpConfig = $httpConfig;
        $this->logger = KashierLoggingManager::getInstance(__CLASS__);
    }

    /**
     * Executes an HTTP request
     *
     * @param string $data query string OR POST content as a string
     * @return mixed
     * @throws KashierConnectionException
     */
    public function execute(string $data)
    {
        //Initialize the logger
        $this->logger->info($this->httpConfig->getMethod().' '.$this->httpConfig->getUrl());

        //Initialize Curl Options
        $ch = curl_init($this->httpConfig->getUrl());
        $options = $this->httpConfig->getCurlOptions();
        if (empty($options[CURLOPT_HTTPHEADER])) {
            unset($options[CURLOPT_HTTPHEADER]);
        }
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $this->httpConfig->getUrl());
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 99999);


        //Determine Curl Options based on Method
        switch ($this->httpConfig->getMethod()) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
        }

        //Default Option if Method not of given types in switch case
        if ($this->httpConfig->getMethod() != null) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->httpConfig->getMethod());
        }

        $this->responseHeaders = array();
        $this->skippedHttpStatusLine = false;
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'parseResponseHeaders'));

        //Execute Curl Request
        $result = curl_exec($ch);
        //Retrieve Response Status
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //Throw Exception if Retries and Certificates doenst work
        if (curl_errno($ch)) {
            $ex = new KashierConnectionException(
                $this->httpConfig->getUrl(),
                curl_error($ch),
                curl_errno($ch)
            );
            curl_close($ch);
            throw $ex;
        }

        // Get Request and Response Headers
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $this->logger->debug("Request Headers \t: ".str_replace("\r\n", ', ', $requestHeaders));
        $this->logger->debug(($data && $data != '' ? "Request Data\t\t: ".$data : 'No Request Payload')."\n".str_repeat('-', 128)."\n");
        $this->logger->info("Response Status \t: ".$httpStatus);
        $this->logger->debug("Response Headers\t: ".$this->implodeArray($this->responseHeaders));

        //Close the curl request
        curl_close($ch);

        $response = json_decode($result, true);

        //More Exceptions based on HttpStatus Code
        if ($httpStatus < 200 || $httpStatus >= 300) {

            $errorMessage = '';
            if (isset($response['error']['explanation']) && ! empty($response['error']['explanation'])) {
                $errorMessage = $response['error']['explanation'];
            }

            $ex = new KashierConnectionException(
                $this->httpConfig->getUrl(),
                $errorMessage,
                $httpStatus
            );

            $ex->setData($result);
            $this->logger->error("Got Http response code $httpStatus when accessing {$this->httpConfig->getUrl()}. ".$result);
            $this->logger->debug("\n\n".str_repeat('=', 128)."\n");
            throw $ex;
        }

        $this->logger->debug(($result && $result != '' ? "Response Data \t: ".$result : 'No Response Body')."\n\n".str_repeat('=', 128)."\n");

        //Return result object
        return $result;
    }

    /**
     * Gets all Http Headers
     *
     * @return array
     */
    private function getHttpHeaders(): array
    {
        $ret = array();
        foreach ($this->httpConfig->getHeaders() as $k => $v) {
            $ret[] = "$k: $v";
        }
        return $ret;
    }

    /**
     * Implodes a key/value array for printing.
     *
     * @param array $arr
     * @return string
     */
    protected function implodeArray(array $arr): string
    {
        $retStr = '';
        foreach ($arr as $key => $value) {
            $retStr .= $key.': '.$value.', ';
        }
        rtrim($retStr, ', ');
        return $retStr;
    }

    /**
     * Parses the response headers for debugging.
     *
     * @param $ch
     * @param string $data
     * @return int
     */
    protected function parseResponseHeaders($ch, string $data): int
    {
        if (! $this->skippedHttpStatusLine) {
            $this->skippedHttpStatusLine = true;
            return strlen($data);
        }

        $trimmedData = trim($data);
        if ($trimmedData === '') {
            return strlen($data);
        }

        // Added condition to ignore extra header which dont have colon ( : )
        if (strpos($trimmedData, ':') == false) {
            return strlen($data);
        }

        list($key, $value) = explode(':', $trimmedData, 2);

        $key = trim($key);
        $value = trim($value);

        // This will skip over the HTTP Status Line and any other lines
        // that don't look like header lines with values
        if ($key != '' && $value != '') {
            // This is actually a very basic way of looking at response headers
            // and may miss a few repeated headers with different (appended)
            // values but this should work for debugging purposes.
            $this->responseHeaders[$key] = $value;
        }

        return strlen($data);
    }
}
