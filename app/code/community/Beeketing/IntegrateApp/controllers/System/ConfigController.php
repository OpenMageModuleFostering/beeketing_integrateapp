<?php

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'System' . DS . 'ConfigController.php';

class Beeketing_IntegrateApp_System_ConfigController extends Mage_Adminhtml_System_ConfigController
{
    /**
     * Hooks save bee options
     * @throws Mage_Core_Exception
     */
    public function _saveBeeOptions()
    {
        // Get api key
        $apiKey = $_POST['groups']['general']['fields']['api_key']['value'];

        /**
         * @var Beeketing_BeeketingMagento $coreContainer
         */
        $coreContainer = Mage::helper('beeketing_integrateapp/core')->getBeeketingMagentoCore();

        // Check
        $verified = $coreContainer->getBeeketingConfig()->isApiKeyExists($apiKey);

        if (!$verified) {
            throw new Mage_Core_Exception('Oops! we can\'t connect to Beeketing\'s service with your Access Key, please make sure your access key is corrected.');
        }

        // Set api key
        $coreContainer->getBeeketingConfig()->setApiKey($apiKey);

        /* @var $session Mage_Adminhtml_Model_Session */
        $session = Mage::getSingleton('adminhtml/session');

        // Gets the current store's details
        $store = Mage::app()->getStore();

        $data = Mage::helper('beeketing_integrateapp')->getFormattedStore($store);

        // Push shop data to Beeketing platform
        Mage::helper('beeketing_integrateapp/core')->sendRequest('shops', $data,
            Zend_Http_Client::PUT, ['X-Beeketing-Source' => BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK]);

        $session->addSuccess('Awesome! your access key is saved and verified. Next step, select an app to use on your store below.');
    }
}