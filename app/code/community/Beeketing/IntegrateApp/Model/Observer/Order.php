<?php

/**
 * User: ducanh
 * Date: 25/12/2015
 * Time: 10:28
 */
class Beeketing_IntegrateApp_Model_Observer_Order
{
    /**
     * Update order to Beeketing after order change
     * @param Varien_Event_Observer $observer
     */
    public function orderAfterSave($observer)
    {
        // Get order form event object
        $order = $observer->getDataObject();

        // Delete cart token
        Mage::helper('beeketing_integrateapp/core')->deleteCartToken();

        // Add to session
        Mage::getSingleton('core/session')->setBKOrderSuccess(true);

        Mage::helper('beeketing_integrateapp/order')->saveToBeeketingApi($order);
    }

    /**
     * Add additional options
     * @param Varien_Event_Observer $observer
     */
    public function salesConvertQuoteItemToOrderItem($observer)
    {
        $quoteItem = $observer->getItem();
        if ($additionalOptions = $quoteItem->getOptionByCode('additional_options')) {
            $orderItem = $observer->getOrderItem();
            $options = $orderItem->getProductOptions();
            $options['additional_options'] = unserialize($additionalOptions->getValue());
            $orderItem->setProductOptions($options);
        }
    }
}