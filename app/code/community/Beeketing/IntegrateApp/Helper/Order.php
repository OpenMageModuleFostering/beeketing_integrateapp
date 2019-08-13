<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 10:14
 */
class Beeketing_IntegrateApp_Helper_Order extends Mage_Core_Helper_Abstract
{
    /**
     * Get data of order for Beeketing Api
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function getFormattedOrder($order)
    {
        $data = [
            'ref_id' => $order->getId(),
            'contact_ref_id' => $order->getCustomerId(),
            'currency' => $order->getBaseCurrencyCode(),
            'email' => $order->getCustomerEmail(),
            'financial_status' => $order->getStatus(),
            'line_items' => [],
            'name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'subtotal_price' => $order->getSubtotal(),
            'total_tax' => $order->getTaxAmount(),
            'total_discounts' => $order->getDiscountAmount(),
            'total_price' => $order->getGrandTotal(),
            'cart_token' => Mage::helper('beeketing_integrateapp/core')->getCartToken(),
        ];

        $products = [];
        // Get all item in order
        foreach ($order->getItemsCollection() as $item) {
            $products[] = [
                'ref_id' => $item->getId(),
                'price' => $item->getPrice(),
                'sku' => $item->getSku(),
                'product_ref_id' => $item->getProductId(),
                'variant_id' => null,
                'fulfillable_quantity' => $item->getQtyOrdered(),
            ];
        }

        $data['line_items'] = $products;
        return $data;
    }

    /**
     * Add order to Beeketing Api
     * @param Mage_Sales_Model_Order $order
     * @param string $requestType
     */
    public function saveToBeeketingApi($order, $requestType = BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK)
    {
        // Load order magento
        $order = Mage::getModel('sales/order')->load($order->getId());

        // Get order data
        $orderData = $this->getFormattedOrder($order);

        // Push collection to Beeketing platform
        Mage::helper('beeketing_integrateapp/core')->sendRequest('orders/create_update', $orderData,
            Zend_Http_Client::POST, ['X-Beeketing-Source' => $requestType]);

    }
}