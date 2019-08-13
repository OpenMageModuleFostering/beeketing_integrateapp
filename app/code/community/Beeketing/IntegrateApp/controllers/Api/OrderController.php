<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 10:13
 */

/**
 * Api Order Controller
 *
 * @category   Beeketing
 * @package    Beeketing_IntegrateApp
 * @author      Beeketing
 */
class Beeketing_IntegrateApp_Api_OrderController extends Mage_Core_Controller_Front_Action
{
    public function ordersAction()
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
        $collections = Mage::getModel('sales/order')
            ->getCollection()
            /*->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'))*/
            ->setPageSize($limit)
            ->setCurPage($page);

        $orders = [];
        foreach ($collections as $order) {
            $orders[] = Mage::helper('beeketing_integrateapp/order')->getFormattedOrder($order);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $orders,
        ]));
    }

    /**
     * Count orders action
     */
    public function orderCountAction()
    {
        $collectionsCount = Mage::getModel('sales/order')
            ->getCollection()
            ->count();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectionsCount,
        ]));
    }
}