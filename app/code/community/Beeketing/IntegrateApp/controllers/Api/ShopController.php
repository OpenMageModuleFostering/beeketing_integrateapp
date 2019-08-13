<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 10:53
 */
class Beeketing_IntegrateApp_Api_ShopController extends Mage_Core_Controller_Front_Action
{
    /**
     * Shop action
     */
    public function shopAction()
    {
        // Gets the current store's details
        $store = Mage::app()->getStore();

        $data = Mage::helper('beeketing_integrateapp')->getFormattedStore($store);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $data,
        ]));
    }

    /**
     * App shared
     */
    public function appSharedAction()
    {
        $query = $this->getRequest()->getParams();

        if (!isset($query['offer_id']) || !isset($query['post_id'])) {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Id not found',
            ]));

            return;
        }

        $coreHelper = Mage::helper('beeketing_integrateapp/core');

        $beeketingMagento = $coreHelper->getBeeketingMagentoCore();

        $beeketingConfig = $beeketingMagento->getBeeketingConfig();

        // Init client and add param
        try {
            $client = new Zend_Http_Client();
            $client->setUri($beeketingConfig->getPath() . '/cboost/offers/shared?offer_id=' . $query['offer_id'] . '&post_id=' . $query['post_id']);
            $client->setMethod(Zend_Http_Client::POST);
            $client->setHeaders('X-Beeketing-Key', $coreHelper->getApiKey());
            $client->setHeaders('Content-Type', 'application/json');
            $client->setHeaders('X-Beeketing-Source', 'webhook');

            $apiResponse = $client->request();

        } catch (Exception $e) {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));

            return;
        }

        $this->getResponse()->setBody($apiResponse->getBody());
    }
}