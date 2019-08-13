<?php

/**
 * User: ducanh
 * Date: 25/12/2015
 * Time: 10:17
 */
class Beeketing_IntegrateApp_Model_Observer_Collection
{
    /**
     * Update collection to Beeketing after collection change
     * @param Varien_Event_Observer $observer
     */
    public function collectionAfterSave($observer)
    {
        // Get collection form event object
        $collection = $observer->getDataObject();

        // Update collection data to Beeketing Platform
        Mage::helper('beeketing_integrateapp/collection')->saveToBeeketingApi($collection);
    }
}