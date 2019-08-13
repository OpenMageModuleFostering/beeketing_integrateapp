<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 12:05
 */
class Beeketing_IntegrateApp_Model_Observer_Product
{
    /**
     * Update product to Beeketing after product change
     * @param Varien_Event_Observer $observer
     */
    public function productAfterSave($observer)
    {
        // Get product form event object
        $product = $observer->getDataObject();

        $categoryIds = $product->getCategoryIds();
        foreach($categoryIds as $categoryId) {
            // Update collection data to Beeketing Platform
            Mage::helper('beeketing_integrateapp/collection')->saveToBeeketingApi($categoryId);
        }
        Mage::helper('beeketing_integrateapp/product')->saveToBeeketingApi($product);
    }

    /**
     * Delete product on Beeketing after delete product on site
     * @param Varien_Event_Observer $observer
     */
    public function productDeleteAfter($observer)
    {
        // Get product form event object
        $product = $observer->getDataObject();

        // Run delete on Beeketing
        Mage::helper('beeketing_integrateapp/core')->sendRequest(
            'products/' . $product->getId() . '.json',
            [],
            Zend_Http_Client::DELETE,
            ['X-Beeketing-Source' => BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK]
        );
    }

    /**
     * Add custom option to product
     * @param Varien_Event_Observer $observer
     */
    public function catalogProductLoadAfter($observer)
    {
        // set the additional options on the product
        $action = Mage::app()->getFrontController()->getAction();

        $attributes = $action->getRequest()->getParam('attributes');
        $product = $observer->getProduct();

        if (isset($attributes['bk_option'])) {
            $options = $attributes['bk_option'];
            $this->addAdditionalOption($observer, $options);
        } elseif (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $attribute = is_string($attribute) ? json_decode($attribute, true) : $attribute;
                if (isset($attribute['bk_option']['productId']) && $attribute['bk_option']['productId'] == $product->getId()) {
                    $options = $attribute['bk_option'];
                    $this->addAdditionalOption($observer, $options);
                    break;
                }
            }
        }
    }

    /**
     * Add additional option
     *
     * @param $observer
     * @param $options
     */
    protected function addAdditionalOption($observer, $options)
    {
        $product = $observer->getProduct();

        // add to the additional options array
        $additionalOptions = array();
        if ($additionalOption = $product->getCustomOption('additional_options')) {
            $additionalOptions = (array) unserialize($additionalOption->getValue());
        }

        $additionalOptions[] = array(
            'label' => 'BK-Type',
            'value' => $options['type'],
        );

        $observer->getProduct()
            ->addCustomOption('additional_options', serialize($additionalOptions));
    }

    /**
     * Product inventory save after
     * @param Varien_Event_Observer $observer
     */
    public function productInventorySaveAfter(Varien_Event_Observer $observer)
    {
        // Get product form event object
        $product = $observer->getDataObject()->getProduct();
        Mage::helper('beeketing_integrateapp/product')->saveToBeeketingApi($product);
    }
}