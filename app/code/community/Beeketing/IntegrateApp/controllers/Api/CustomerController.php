<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 09:06
 */

/**
 * Api Customer Controller
 *
 * @category   Beeketing
 * @package    Beeketing_IntegrateApp
 * @author      Beeketing
 */
class Beeketing_IntegrateApp_Api_CustomerController extends Mage_Core_Controller_Front_Action
{

    public function customersAction()
    {
        $query = $this->getRequest()->getParams();
        if (!isset($query['limit'])) {
            $limit = 250;
        } else {
            $limit = $query['limit'];
        }

        if (!isset($query['page'])) {
            $page = 1;
        } else {
            $page = $query['page'];
        }

        if (!isset($query['title'])) {
            $searchString = '';
        } else {
            $searchString = $query['title'];
        }

        $collections = Mage::getModel('customer/customer')
            ->getCollection()
            /*->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'))*/
            ->setPageSize($limit)
            ->setCurPage($page);

        $customers = [];
        foreach ($collections as $customer) {
            $customers[] = Mage::helper('beeketing_integrateapp/customer')->getFormattedCustomer($customer);

        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $customers,
        ]));
    }

    /**
     * Count customers action
     */
    public function customerCountAction()
    {
        $collectionsCount = Mage::getModel('customer/customer')
            ->getCollection()
            ->count();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectionsCount,
        ]));
    }
}