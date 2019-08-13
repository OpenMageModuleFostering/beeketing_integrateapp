<?php

/**
 * User: ducanh
 * Date: 25/12/2015
 * Time: 10:33
 */
class Beeketing_IntegrateApp_Model_Observer_Customer
{
    /**
     * Update customer to Beeketing after customer change
     * @param Varien_Event_Observer $observer
     */
    public function customerAfterSave($observer)
    {
        // Get customer form event object
        $customer = $observer->getDataObject();

        Mage::helper('beeketing_integrateapp/customer')->saveToBeeketingApi($customer);
    }

    /**
     * Delete customer on Beeketing after delete customer on site
     * @param Varien_Event_Observer $observer
     */
    public function customerDeleteAfter($observer)
    {
        // Get customer form event object
        $customer = $observer->getDataObject();

        // Run delete on Beeketing
        Mage::helper('beeketing_integrateapp/core')->sendRequest(
            'customers/' . $customer->getId() . '.json',
            [],
            Zend_Http_Client::DELETE,
            ['X-Beeketing-Source' => BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK]
        );
    }
}