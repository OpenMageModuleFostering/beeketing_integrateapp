<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 10:02
 */

/**
 * Api Collection Controller
 *
 * @category   Beeketing
 * @package    Beeketing_IntegrateApp
 * @author      Beeketing
 */
class Beeketing_IntegrateApp_Api_CollectionController extends Mage_Core_Controller_Front_Action
{
    /**
     *  Get collections action
     */
    public function collectionsAction()
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

        $collections = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'))
            ->setPageSize($limit)
            ->setCurPage($page);

        $collectionsArray = [];
        foreach ($collections as $collection) {
            $collectionsArray[] = Mage::helper('beeketing_integrateapp/collection')->getFormattedCollection($collection);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectionsArray,
        ]));
    }

    /**
     * Get single collection action
     */
    public function collectionAction()
    {
        $query = $this->getRequest()->getParams();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        if (!isset($query['id'])) {
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Collection id not found',
            ]));

            return;
        }

        $collection = Mage::helper('beeketing_integrateapp/collection')->getFormattedCollection($query['id']);

        if (!$collection) {
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Collection not found',
            ]));

            return;
        }

        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data'  => $collection,
        ]));
    }

    /**
     * Count collections action
     */
    public function collectionCountAction()
    {
        $query = $this->getRequest()->getParams();

        if (!isset($query['title'])) {
            $searchString = '';
        } else {
            $searchString = $query['title'];
        }

        $collectionsCount = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToFilter('name', array('like' => '%' . $searchString . '%'))
            ->count();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectionsCount,
        ]));
    }

    /**
     * Get collect action
     */
    public function collectsAction()
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

        $offset = ($page - 1) * $limit;

        $this->getResponse()->setHeader('Content-type', 'application/json');

        $collects = [];
        if (isset($query['collection_id'])) {
            $collection = Mage::getModel('catalog/category')->load((int) $query['collection_id']);
            if (!$collection || !$collection->getId()) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'message' => 'Collection not found',
                ]));

                return;
            }
            $productCollection = $collection->getProductCollection();
            $productPositions = $collection->getProductsPosition();


            $productIds = $productCollection->getAllIds($limit, $offset);

            foreach ($productIds as $productId) {
                $collects[] = Mage::helper('beeketing_integrateapp/collection')->getFormattedCollect(
                    $collection->getId(), $productId, $productPositions[$productId]);
            }
        } elseif (isset($query['product_id']) && $page == 1) {
            $product = Mage::getModel('catalog/product')->load((int) $query['product_id']);

            if (!$product || !$product->getId()) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'message' => 'Product not found',
                ]));

                return;
            }

            $collectionIds = $product->getCategoryIds();

            $productId = $product->getId();

            foreach ($collectionIds as $collectionId) {
                $collects[] = Mage::helper('beeketing_integrateapp/collection')->getFormattedCollect(
                    $collectionId, $productId, null);
            }
        } else {
            $categories = Mage::getModel('catalog/category')
                ->getCollection();

            $collectCount = 0;

            foreach ($categories as $category) {
                $collectCount += $category->getProductCollection()->count();
            }

            if ($collectCount == 0) {
                goto response;
            }

            // TODO count collect
            foreach ($categories as $category) {
                $productPositions = $category->getProductsPosition();
                $productCollection = $category->getProductCollection();
                $newLimit = round($productCollection->count() / $collectCount * $limit);
                $newOffset = ($page-1) * $newLimit;
                $productIds = $productCollection->getAllIds($newLimit, $newOffset);

                foreach ($productIds as $productId) {
                    $collects[] = Mage::helper('beeketing_integrateapp/collection')->getFormattedCollect(
                        $category->getId(), $productId, $productPositions[$productId]);
                }
            }
        }

        response:

        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collects,
        ]));
    }

    /**
     * Collect count
     */
    public function collectCountAction()
    {
        $query = $this->getRequest()->getParams();
        $collectCount = 0;
        $this->getResponse()->setHeader('Content-type', 'application/json');

        if (isset($query['collection_id'])) {
            $collection = Mage::getModel('catalog/category')->load((int) $query['collection_id']);
            if (!$collection || !$collection->getId()) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'message' => 'Collection not found',
                ]));

                return;
            }

            $collectCount = $collection->getProductCollection()->count();
        } elseif (isset($query['product_id'])) {
            $product = Mage::getModel('catalog/product')->load((int) $query['product_id']);

            if (!$product || !$product->getId()) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'message' => 'Product not found',
                ]));

                return;
            }

            $collectionIds = $product->getCategoryIds();

            $collectCount = count($collectionIds);
        } else {
            $categories = Mage::getModel('catalog/category')
                ->getCollection();

            foreach ($categories as $category) {
                $collectCount += $category->getProductCollection()->count();
            }
        }

        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $collectCount,
        ]));
    }
}