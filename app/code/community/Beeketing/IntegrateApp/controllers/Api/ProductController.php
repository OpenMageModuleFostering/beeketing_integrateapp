<?php

/**
 * User: ducanh
 * Date: 22/12/2015
 * Time: 17:58
 */

/**
 * Api Product Controller
 *
 * @category   Beeketing
 * @package    Beeketing_IntegrateApp
 * @author      Beeketing
 */
class Beeketing_IntegrateApp_Api_ProductController extends Mage_Core_Controller_Front_Action
{
    /**
     * Products action
     */
    public function productsAction()
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

        if (!isset($query['visible'])) {
            $visibility = [1,2,3,4];
        } elseif ($query['visible']) {
            $visibility = [2,3,4];
        } else {
            $visibility = [1];
        }

        $collections = Mage::getModel('catalog/product')->getCollection();

        $collections->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'));
        if (isset($query['updated_at_min'])) {
            $collections->addAttributeToFilter('updated_at', array('gteq' => $query['updated_at_min']));
        }
        if (isset($query['updated_at_max'])) {
            $collections->addAttributeToFilter('updated_at', array('lteq' => $query['updated_at_max']));
        }
        $collections->addAttributeToFilter('visibility', $visibility);
        $collections->setPageSize($limit);
        $collections->setCurPage($page);

        $products = [];
        foreach ($collections as $product) {
            $products[] = Mage::helper('beeketing_integrateapp/product')->getFormattedProduct($product);

        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $products,
        ]));
    }

    /**
     * Get single product action
     */
    public function productAction()
    {
        $query = $this->getRequest()->getParams();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        if (!isset($query['id'])) {
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Product id not found',
            ]));

            return;
        }

        $product = Mage::helper('beeketing_integrateapp/product')->getFormattedProduct($query['id']);

        if (!$product) {
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Product not found',
            ]));

            return;
        }

        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data'  => $product,
        ]));
    }

    /**
     * Count product action
     */
    public function productCountAction()
    {
        $query = $this->getRequest()->getParams();

        if (!isset($query['title'])) {
            $searchString = '';
        } else {
            $searchString = $query['title'];
        }

        if (!isset($query['visible'])) {
            $visibility = [1,2,3,4];
        } elseif ($query['visible']) {
            $visibility = [2,3,4];
        } else {
            $visibility = [1];
        }

        $collections = Mage::getModel('catalog/product')->getCollection();

        $collections->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'));
        if (isset($query['updated_at_min'])) {
            $collections->addAttributeToFilter('updated_at', array('gteq' => $query['updated_at_min']));
        }
        if (isset($query['updated_at_max'])) {
            $collections->addAttributeToFilter('updated_at', array('lteq' => $query['updated_at_max']));
        }
        $collections->addAttributeToFilter('visibility', $visibility);

        $collectionsCount = $collections->count();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectionsCount,
        ]));
    }
}