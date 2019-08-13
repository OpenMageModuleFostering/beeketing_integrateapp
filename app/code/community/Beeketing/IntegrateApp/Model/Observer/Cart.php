<?php

/**
 * User: ducanh
 * Date: 25/12/2015
 * Time: 11:04
 */
class Beeketing_IntegrateApp_Model_Observer_Cart
{

    /**
     * Apply discount after adding item to cart
     * @param Varien_Event_Observer $observer
     */
    public function checkoutCartProductAddAfter($observer)
    {
        $action = Mage::app()->getFrontController()->getAction();

        /* @var $item Mage_Sales_Model_Quote_Item */
        $quoteItem = $observer->getQuoteItem();

        if ($quoteItem->getParentItem()) {
            $item = $quoteItem->getParentItem();
        } else {
            $item = $quoteItem;
        }

        // Make sure we don't have a negative
        $attributes = $action->getRequest()->getParam('attributes');
        if (isset($attributes['bk_option'])) {
            $extraOptions = $attributes['bk_option'];
            $item->setCustomPrice($extraOptions['price']);
            $item->setOriginalCustomPrice($extraOptions['price']);
            $item->getProduct()->setIsSuperMode(true);
        } elseif (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $attribute = is_string($attribute) ? json_decode($attribute, true) : $attribute;
                if (isset($attribute['bk_option']['productId']) && $attribute['bk_option']['productId'] == $item->getProduct()->getId()) {
                    $extraOptions = $attribute['bk_option'];
                    $item->setCustomPrice($extraOptions['price']);
                    $item->setOriginalCustomPrice($extraOptions['price']);
                    $item->getProduct()->setIsSuperMode(true);
                    break;
                }
            }
        }

        if ($action->getFullActionName() == 'sales_order_reorder') {
            $buyInfo = $item->getBuyRequest();
            if ($options = $buyInfo->getExtraOptions()) {
                $additionalOptions = array();
                if ($additionalOption = $item->getOptionByCode('additional_options')) {
                    $additionalOptions = (array) unserialize($additionalOption->getValue());
                }
                foreach ($options as $key => $value) {
                    $additionalOptions[] = array(
                        'label' => $key,
                        'value' => $value,
                    );
                }
                $item->addOption(array(
                    'code' => 'additional_options',
                    'value' => serialize($additionalOptions)
                ));
            }
        }

        // Track event add to cart
        $productVariation = $quoteItem->getProduct();
        $product = $item->getProduct();
        // Get detail
        $itemDetails = [
            'id' => $quoteItem->getId(),
            'product_id' => $product->getId(),
            'variation_id' => $productVariation->getId(),
            'sku' => $productVariation->getSku(),
            'title' => htmlspecialchars($productVariation->getName(), ENT_QUOTES),
            'price' => $productVariation->getPrice(),
            'quantity' => $quoteItem->getQty(),
            'item_url' => $product->getProductUrl(),
            'image' => Mage::helper('catalog/image')->init($product, 'image')->resize(250)->__toString(),
        ];
        // Add to session
        Mage::getSingleton('core/session')->setLastAddedItem(json_encode($itemDetails));
    }

    /**
     * Remove from cart
     * @param Varien_Event_Observer $observer
     */
    public function removeFromCart($observer)
    {
        $quoteItem = $observer->getQuoteItem();
        $product = $quoteItem->getProduct();
        $itemDetails = [
            'id' => $quoteItem->getId(),
            'product_id' => $product->getId(),
            'variation_id' => $product->getId(),
            'sku' => $product->getSku(),
            'title' => htmlspecialchars($product->getName(), ENT_QUOTES),
            'price' => $product->getPrice(),
            'quantity' => $quoteItem->getQty(),
            'item_url' => $product->getProductUrl(),
            'image' => Mage::helper('catalog/image')->init($product, 'image')->resize(250)->__toString(),
        ];
        // Add to session
        Mage::getSingleton('core/session')->setLastDeletedItem(json_encode($itemDetails));
    }
}