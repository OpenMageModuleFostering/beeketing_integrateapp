<?php

class Beeketing_IntegrateApp_Model_Observer
{
    /**
     * Show notification
     * @return bool
     */
    public function showNotifcation()
    {
        //get the admin session
        Mage::getSingleton('core/session', array('name'=>'adminhtml'));

        //verify if the user is logged in to the backend
        if(!Mage::getSingleton('admin/session')->isLoggedIn()){
            return false;
        }

        /**
         * @var Beeketing_BeeketingMagento $coreContainer
         */
        $coreContainer = Mage::helper('beeketing_integrateapp/core')->getBeeketingMagentoCore();

        /**
         * Get api key
         */
        $apiKey = $coreContainer->getBeeketingConfig()->getApiKey();

        /**
         * Has api key?
         */
        $hasApiKey = ($apiKey) ? true : false;

        if (!$hasApiKey) {
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit', array(
                'section'=>'bee_options'
            ));

            Mage::getSingleton('core/session')->addNotice(
                'Beeketing has been activated. Please click <a href="'.$url.'">here</a> to add your Access Key.'
            );
        }
    }

    /**
     * Add block to beeketing configuration
     * @param Varien_Event_Observer $observer
     */
    public function addBlockHtmlBefore(Varien_Event_Observer $observer)
    {
        /**
         * @var Mage_Adminhtml_Block_System_Config_Edit $block
         */
        $block = $observer->getBlock();

        if (get_class($block) == 'Mage_Adminhtml_Block_System_Config_Edit') {
            /**
             * Get current section
             */
            $currentSection = Mage::app()->getRequest()->getParam('section');

            /**
             * @var Beeketing_BeeketingMagento $coreContainer
             */
            $coreContainer = Mage::helper('beeketing_integrateapp/core')->getBeeketingMagentoCore();

            if ($currentSection == 'bee_options') {
                /**
                 * Get apps data
                 */
                $beeketingAppsData = $coreContainer->getBeeketingConfig()->getAppsDataByApiKey();

                /**
                 * Get api key
                 */
                $apiKey = $coreContainer->getBeeketingConfig()->getApiKey();

                /**
                 * Has api key?
                 */
                $hasApiKey = ($apiKey) ? true : false;

                include(__DIR__ . '/../Block/AppsData.php');

                $signInUrl = $coreContainer->getBeeketingConfig()->getSignInUrl();

                echo '<script type="text/javascript">window.setTimeout(function() { document.getElementById("beeketing-get-access-key").setAttribute("href", "'. $signInUrl .'"); }, 500);</script>';
            }
        }
    }

    /**
     * Add front end scripts.
     * @param Varien_Event_Observer $observer
     */
    public function addFrontendScripts(Varien_Event_Observer $observer)
    {
        /**
         * @var Beeketing_BeeketingMagento $coreContainer
         */
        $coreContainer = Mage::helper('beeketing_integrateapp/core')->getBeeketingMagentoCore();

        /**
         * @var BeeketingSDK_Snippet_SnippetManager $snippetManager;
         */
        $snippetManager = $coreContainer->getSnippetManager();

        /** @var Mage_Core_Controller_Front_Action $controller */
        $controller = $observer->getAction();

        /** @var Mage_Core_Model_Layout $layout */
        $layout = $controller->getLayout();

        if (!$layout->getBlock('before_body_end')) {
            return;
        }

        // Add coupon box
        /** @var Mage_Core_Block_Abstract $block */
        $block = $layout->createBlock('core/text');

        $snippetContent = $snippetManager->getAllSnippetContent();

        if (Mage::registry('current_product')) {
            $snippetContent .= "<script> var __bkt = {}; __bkt.p = 'product'; __bkt.rid = " . Mage::registry('current_product')->getId() . ";</script>";
        } elseif (Mage::registry('current_category')) {
            $snippetContent .= "<script>var __bkt = {}; __bkt.p = 'collection'; __bkt.rid = " . Mage::registry('current_category')->getId() .";</script>";
        } elseif (Mage::getUrl('') == Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true))) {
            $snippetContent .= "<script>var __bkt = {}; __bkt.p = 'home';</script>";
        } elseif(parse_url(Mage::getUrl('checkout/onepage'))['path'] == parse_url(Mage::helper('core/url')->getCurrentUrl())['path']) {
            $snippetContent .= "<script>var __bkt = {}; __bkt.p = 'checkout';</script>";
        } else {
            $request = Mage::app()->getFrontController()->getRequest();
            $module = $request->getModuleName();
            $controller = $request->getControllerName();
            $action = $request->getActionName();

            if($module == 'checkout' && $controller == 'cart' && $action == 'index') {
                $snippetContent .= "<script>var __bkt = {}; __bkt.p = 'cart';</script>";
            }
        }

        $website_id = Mage::app()->getWebsite()->getId();
        $store_id = Mage::app()->getStore()->getStoreId();
        $snippetContent .= "<script>if(typeof __bkt != 'undefined') {__bkt.website_id = ". $website_id . "; __bkt.store_id = " . $store_id . ";}</script>";

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            $snippetContent .= "<script>var BKCustomer = {}; BKCustomer.id = " . $customerId . ";</script>";
        }

        if ($lastAddedItem = Mage::getSingleton('core/session')->getLastAddedItem()) {
            $snippetContent .= "<script>var bkLastAddedItem = JSON.parse('" . $lastAddedItem . "');</script>";
            Mage::getSingleton('core/session')->unsLastAddedItem();
        } elseif ($lastDeletedItem = Mage::getSingleton('core/session')->getLastDeletedItem()) {
            $snippetContent .= "<script>var bkLastDeletedItem = JSON.parse('" . $lastDeletedItem . "');</script>";
            Mage::getSingleton('core/session')->unsLastDeletedItem();
        }

        if (Mage::getSingleton('core/session')->getBKOrderSuccess()) {
            $snippetContent .= "<script>var bkOrderSuccess = true;</script>";
            Mage::getSingleton('core/session')->unsBKOrderSuccess();
        }


        $cartData = Mage::helper('beeketing_integrateapp')->getCartData();

        $snippetContent .= "<script>var bkCartData = JSON.parse('" . json_encode($cartData)."');</script>";

        $block->setText($snippetContent);
        $layout->getBlock('before_body_end')->append($block);
    }
}