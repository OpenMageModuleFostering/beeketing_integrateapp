<?php

class Beeketing_IntegrateApp_Helper_Data extends Mage_Core_Helper_Data
{
    public function getCartData()
    {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $cartItems = array();
        $hasChildProductTypes = [
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
            Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
            Mage_Catalog_Model_Product_Type::TYPE_GROUPED
        ];
        foreach($cart->getItemsCollection() as $item) {
            if (in_array($item->getProductType(), $hasChildProductTypes)) {
                continue;
            }
            if ($item->getParentItem()) {
                $parentItem = $item->getParentItem();
            } else {
                $parentItem = null;
            }
            $customOptions = [];
            foreach ($item->getOptions() as $option) {
                if (is_string($option->getValue()) && strpos($option->getValue(), 'BK-Type') !== false &&
                    $value = unserialize($option->getValue())) {
                    $customOptions = $value;
                }
            }

            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $row=array();
            $row['id'] = $item->getId();
            $row['product_id'] = $parentItem ? $parentItem->getProductId() : $item->getProductId();
            $row['variant_id'] = $item->getProductId();
            $row['item_url'] = $product->getProductUrl();
            $row['title'] = htmlspecialchars($product->getName(), ENT_QUOTES);
            $row['image'] = Mage::helper('catalog/image')->init($product, 'image')->resize(250)->__toString();
            $row['sku'] = $item->getSku();
            $row['line_price'] = $item->getOriginalPrice();
            $row['price'] = $item->getPrice();
            $row['quantity']= (int)$item->getQty();
            $row['subtotal']= $item->getSubtotal();
            $row['tax_amount']= $item->getTaxAmount();
            $row['tax_percent']= $item->getTaxPercent();
            $row['discount_amount']= $item->getDiscountAmount();
            $row['row_total']= $item->getRowTotal();
            $row['custom_options'] = $customOptions;
            $cartItems[]=$row;
        }

        $cartData['item_count'] = Mage::helper('checkout/cart')->getSummaryCount();
        $cartData['sub_total'] = $cart->getSubtotal();
        $cartData['grand_total'] = $cart->getGrandTotal();
        $cartData['cart_url'] = Mage::getUrl('checkout/cart');
        $cartData['checkout_url'] = Mage::helper('checkout/url')->getCheckoutUrl();
        $cartData['shop_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $cartData['items'] = $cartItems;
        $cartData['token'] = Mage::helper('beeketing_integrateapp/core')->getCartToken();

        return $cartData;
    }

    public function getCurrencyFormat()
    {
        $formattedPrice = Mage::helper('core')->currency(11.11, true, false);
        $formattedPrice = str_replace('11.11', '{{amount}}', $formattedPrice);
        $formattedPrice = preg_replace('/\d+/u', '', $formattedPrice);
        return $formattedPrice;
    }

    /**
     * Get data of store for Beeketing Api
     * @param Mage_Core_Model_Store $store
     * @return mixed
     */
    public function getFormattedStore($store)
    {
        $data = [
            'absolute_path' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            'currency_format' => $this->getCurrencyFormat(),
            'currency' => $store->getCurrentCurrencyCode(),
            'timezone' => Mage::getStoreConfig('general/locale/timezone')
        ];
        return $data;
    }
}