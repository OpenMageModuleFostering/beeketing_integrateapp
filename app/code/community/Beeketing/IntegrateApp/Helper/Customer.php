<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 09:20
 */
class Beeketing_IntegrateApp_Helper_Customer extends Mage_Core_Helper_Abstract
{
    /**
     * Get data of customer for Beeketing Api
     * @param Mage_Customer_Model_Customer $customer
     * @return mixed
     */
    public function getFormattedCustomer($customer)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customerId = $customer->getId();
        } else {
            $customerId = $customer;
        }
        // Load customer magento
        // This is function get all attribute of magento
        $customer = Mage::getModel('customer/customer')->load($customerId);

        if (!$customer || !$customer->getId()) {
            return false;
        }

        // Get date update product
        $date = Mage::getModel('core/date');
        $dateCreated = $date->gmtDate(BeeketingSDK_Config_BeeketingConfig::BEEKETING_FORMAT_DATE, $customer->getCreatedAt());

        // Get billing address
        $billingAddress = $customer->getDefaultBillingAddress();
        if ($billingAddress && $billingAddress->getId()) {

            $street1 = $billingAddress->getStreet1();
            $street2 = $billingAddress->getStreet2();
            $city = $billingAddress->getCity();
            $region = $billingAddress->getRegion();
            $postcode = $billingAddress->getPostcode();
            $company = $billingAddress->getCompany();

            // Get country name
            if ($billingAddress->getData('country_id')) {
                $country = Mage::getModel('directory/country')->load($billingAddress->getData('country_id'))->getIso2Code();
                $country = Mage::app()->getLocale()->getCountryTranslation($country);
            } else {
                $country = '';
            }

        } else {
            $street1 = '';
            $street2 = '';
            $city = '';
            $region = '';
            $postcode = '';
            $country = '';
            $company = '';
        }

        $data = [
            'ref_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'signed_up_at' => $dateCreated,
            'address1' => $street1,
            'address2' => $street2,
            'city' => $city,
            'company' => $company,
            'province' => $region,
            'zip' => $postcode,
            'country' => $country,
        ];

        // Get order of customer
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('customer_id', $customer->getId());

        // Get total_spent
        $totalSpent = 0;
        foreach ($orders as $order) {
            if ($order->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE) {
                $totalSpent += $order->getGrandTotal();
            }
        }
        $data['orders_count'] = count($orders);
        $data['total_spent'] = $totalSpent;
        return $data;
    }

    /**
     * Add customer to Beeketing Api
     * @param Mage_Customer_Model_Customer $customer
     * @param string $requestType
     */
    public function saveToBeeketingApi($customer, $requestType = BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK)
    {
        // Load customer magento
        $customer = Mage::getModel('customer/customer')->load($customer->getId());

        // Get customer data
        $customerData = $this->getFormattedCustomer($customer);

        // Push customer data to Beeketing platform
        Mage::helper('beeketing_integrateapp/core')->sendRequest('customers/create_update', $customerData,
            Zend_Http_Client::POST, ['X-Beeketing-Source' => $requestType]);

    }
}