<?php

class Beeketing_IntegrateApp_Helper_Core extends Mage_Core_Helper_Abstract
{
    const BEEKETING_CART_TOKEN = 'beeketing-cart-token';

    /**
     * @var Beeketing_BeeketingMagento $beeketingCore
     */
    protected $beeketingCore;

    public function getApiKey()
    {
        return Mage::getStoreConfig('bee_options/general/api_key');
    }

    public function getBeeketingMagentoCore()
    {
        // If already have core, return core.
        if ($this->beeketingCore) {
            return $this->beeketingCore;
        }

        $apiKey = $this->getApiKey();

        return $this->beeketingCore = new Beeketing_BeeketingMagento($apiKey);
    }

    /**
     * Get cart token
     *
     * @return mixed
     */
    public function getCartToken()
    {
        if (!isset($_COOKIE[self::BEEKETING_CART_TOKEN])) {
            $apiKey = $this->getApiKey();
            $cartToken = uniqid($apiKey, true);
            setcookie(self::BEEKETING_CART_TOKEN, $cartToken, time() + 31536000, '/');
        }

        return $_COOKIE[self::BEEKETING_CART_TOKEN];
    }

    /**
     * Delete cart token
     */
    public function deleteCartToken()
    {
        Mage::getModel('core/cookie')->delete(self::BEEKETING_CART_TOKEN);
    }

    /**
     * Set last added item
     *
     * @param $itemDetails
     */
    public function setLastAddedItem($itemDetails)
    {
        $domain = $this->getHttpHost();
        // Add cookie to site
        setcookie('beeketing-last-added-items', json_encode($itemDetails), time() + 3600, '/', $domain, false, false);
    }

    /**
     * Retrieve HTTP HOST
     *
     * @param bool $trimPort
     * @return string
     */
    public function getHttpHost($trimPort = true)
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return false;
        }
        $host = $_SERVER['HTTP_HOST'];
        if ($trimPort) {
            $hostParts = explode(':', $_SERVER['HTTP_HOST']);
            $host =  $hostParts[0];
        }

        if (strpos($host, ',') !== false || strpos($host, ';') !== false) {
            $response = new Zend_Controller_Response_Http();
            $response->setHttpResponseCode(400)->sendHeaders();
            exit();
        }

        return $host;
    }

    /**
     * Beeketing Api
     * @param string $path
     * @param mixed $data
     * @param string $method
     * @param mixed $headers
     * @return Zend_Http_Response
     */
    public function sendRequest($path, $data = [], $method = Zend_Http_Client::GET, $headers = [])
    {
        $beeketingMagento = $this->getBeeketingMagentoCore();

        $beeketingConfig = $beeketingMagento->getBeeketingConfig();
        // Init client and add param
        $client = new Zend_Http_Client();
        $client->setUri($beeketingConfig->getPath() . '/rest-api/v1/' . $path);
        $client->setMethod($method);
        $client->setHeaders('X-Beeketing-Key', $this->getApiKey());
        $client->setHeaders('Content-Type', 'application/json');

        // Add header for request
        if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $value) {
                $client->setHeaders($key, $value);
            }
        }

        // Add data for request
        if (is_array($data) && count($data)) {
            $json = json_encode($data);
            $client->setRawData($json, 'application/json');
        }

        // Return Zend_Http_Response
        return $client->request();
    }

}