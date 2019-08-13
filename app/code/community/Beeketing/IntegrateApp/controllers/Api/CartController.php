<?php

/**
 * User: ducanh
 * Date: 23/12/2015
 * Time: 15:29
 */
class Beeketing_IntegrateApp_Api_CartController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get cart action
     */
    public function getCartAction()
    {
        $cartData = Mage::helper('beeketing_integrateapp')->getCartData();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $cartData,
        ]));
    }

    /**
     * Add cart action
     */
    public function addCartAction()
    {
        $query = $this->getRequest()->getParams();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        if (!isset($query['product_id'])) {

            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Product id not found',
            ]));

            return;
        }

        $cart = Mage::getSingleton('checkout/cart');

        // Add multiple products to cart
        if (is_array($query['product_id'])) {
            foreach ($query['product_id'] as $key => $productId) {
                if (isset($query['qty'][$key])) {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                        array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    $params['qty'] = $filter->filter($query['qty'][$key]);
                }

                $params['related_product'] = isset($query['related_product']) ? $query['related_product'][$key] : [];

                if (isset($query['attributes'][$key])) {
                    $attribute = json_decode($query['attributes'][$key], true);
                    if (isset($attribute['super_attribute'])) {
                        $params['super_attribute'] = $attribute['super_attribute'];
                    }

                    if (isset($attribute['custom_option'])) {
                        $params['options'] = $attribute['custom_option'];
                    }

                    if (isset($attribute['links'])) {
                        $params['links'] = $attribute['links'];
                    }

                    // Grouped product
                    if (isset($attribute['super_group'])) {
                        $params['super_group'] = $attribute['super_group'];
                    }

                    // Bundle products
                    if (isset($attribute['bundle_option'])) {
                        $params['bundle_option'] = $attribute['bundle_option'];
                    }
                }

                try {
                    $product = $product = Mage::getModel('catalog/product')->load((int) $productId);
                    $cart->addProduct($product, $params);

                } catch (Exception $e) {
                    $this->getResponse()->setBody(json_encode([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ]));

                    return;
                }

            }
        } else {
            // Add single product to cart
            if (isset($query['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($query['qty']);
            }

            $params['related_product'] = isset($query['related_product']) ? $query['related_product'] : [];

            // Configurable product
            if (isset($query['super_attribute'])) {
                $params['super_attribute'] = $query['super_attribute'];
            } elseif (isset($query['attributes']['super_attribute'])) {
                $params['super_attribute'] = $query['attributes']['super_attribute'];
            }

            // Grouped product
            if (isset($query['super_group'])) {
                $params['super_group'] = $query['super_group'];
            } elseif (isset($query['attributes']['super_group'])) {
                $params['super_group'] = $query['attributes']['super_group'];
            }

            if (isset($query['custom_option'])) {
                $params['options'] = $query['custom_option'];
            } elseif (isset($query['attributes']['custom_option'])) {
                $params['options'] = $query['attributes']['custom_option'];
            }

            // Downloadable products
            if (isset($query['links'])) {
                $params['links'] = $query['links'];
            } elseif (isset($query['attributes']['links'])) {
                $params['links'] = $query['attributes']['links'];
            }

            // Bundle products
            if (isset($query['bundle_option'])) {
                $params['bundle_option'] = $query['bundle_option'];
            } elseif (isset($query['attributes']['bundle_option'])) {
                $params['bundle_option'] = $query['attributes']['bundle_option'];
            }

            $product = $product = Mage::getModel('catalog/product')->load((int) $query['product_id']);

            if (!$product || !$product->getId()) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'data' => 'Product not found',
                ]));
                return;
            }

            try {
                $cart->addProduct($product, $params);
            } catch (Exception $e) {
                $this->getResponse()->setBody(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));

                return;
            }
        }

        try {
            $cart->save();

            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        } catch (Exception $e) {
            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));

            return;
        }

        $cartData = Mage::helper('beeketing_integrateapp')->getCartData();
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => $cartData,
        ]));
    }

    /**
     * Remove from cart
     */
    public function removeFromCartAction()
    {
        $query = $this->getRequest()->getParams();
        $this->getResponse()->setHeader('Content-type', 'application/json');
        if (!isset($query['id'])) {

            $this->getResponse()->setBody(json_encode([
                'success' => false,
                'message' => 'Item id not found',
            ]));

            return;
        }

        $cartHelper = Mage::helper('checkout/cart');
        $cartHelper->getCart()->removeItem($query['id'])->save();

        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'data' => [

            ],
        ]));
    }
}