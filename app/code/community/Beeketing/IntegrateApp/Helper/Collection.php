<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 10:00
 */
class Beeketing_IntegrateApp_Helper_Collection extends Mage_Core_Helper_Abstract
{
    /**
     * Get data of collection for Beeketing Api
     * @param Mage_Catalog_Model_Category $collection
     * @return mixed
     */
    public function getFormattedCollection($collection)
    {
        if ($collection instanceof Mage_Catalog_Model_Category) {
            $collectionId = $collection->getId();
        } else {
            $collectionId = $collection;
        }
        // Load collection magento
        // This is function get all attribute of magento
        $collection = Mage::getModel('catalog/category')->load($collectionId);

        if (!$collection || !$collection->getId()) {
            return false;
        }

        $data = [
            'ref_id' => (int)$collection->getId(),
            'title' => $collection->getName(),
            'handle' => $collection->getUrlPath(),
            'collection_type' => 1,
            'image_url' => $collection->getImageUrl()
        ];

        return $data;
    }

    /**
     * Get data of collect for Beeketing Api
     * @param integer $collectionId
     * @param integer $productId
     * @param integer $position
     * @return mixed
     */
    public function getFormattedCollect($collectionId, $productId, $position)
    {
        $data = [
            'ref_id' => $this->generateCollectRefId($collectionId, $productId),
            'product_ref_id' => $productId,
            'collection_ref_id' => $collectionId,
            'position' => $position
        ];
        return $data;
    }

    /**
     * Generate collect id by using cantor pairing function
     * @param integer $productId
     * @param integer $collectionId
     * @return float
     */
    public function generateCollectRefId($productId, $collectionId)
    {
        return (($productId + $collectionId) * ($productId + $collectionId + 1)) / 2 + $collectionId;
    }

    /**
     * Add collection to Beeketing Api
     * @param Mage_Catalog_Model_Category $collection
     * @param string $requestType
     */
    public function saveToBeeketingApi($collection, $requestType = BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK)
    {
        // Load category magento
        if (is_int($collection) || is_string($collection)) {
            $collectionId = $collection;
        } else {
            $collectionId = $collection->getId();
        }
        $collection = Mage::getModel('catalog/category')->load($collectionId);

        // Get product data
        $collectionData = $this->getFormattedCollection($collection);

        // Push collection to Beeketing platform
        Mage::helper('beeketing_integrateapp/core')->sendRequest('collections/create_update', $collectionData,
            Zend_Http_Client::POST, ['X-Beeketing-Source' => $requestType]);

    }
}