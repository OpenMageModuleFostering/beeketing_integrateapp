<?php

/**
 * User: ducanh
 * Date: 24/12/2015
 * Time: 11:08
 */
class Beeketing_IntegrateApp_Helper_Product extends Mage_Core_Helper_Data
{
    /**
     * Get data of product for Beeketing Api
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getFormattedProduct($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        } else {
            $productId = $product;
        }
        // Load product magento
        $product = Mage::getModel('catalog/product')->load($productId);

        if (!$product || !$product->getId()) {
            return false;
        }

        // Get date update product
        $date = Mage::getModel('core/date');
        $updatedAt = $date->gmtDate(BeeketingSDK_Config_BeeketingConfig::BEEKETING_FORMAT_DATE, $product->getUpdatedAt());
        $createdAt = $date->gmtDate(BeeketingSDK_Config_BeeketingConfig::BEEKETING_FORMAT_DATE, $product->getCreatedAt());


        // Get tag
        $modelTag = Mage::getModel('tag/tag');
        $tags = $modelTag->getResourceCollection()
            ->addPopularity()
            ->addStatusFilter($modelTag->getApprovedStatus())
            ->addProductFilter($product->getId())
            ->setFlag('relation', true)
            ->setActiveFilter()
            ->load();

        $tagString = [];
        if (isset($tags) && !empty($tags)) {
            foreach ($tags as $tag) {
                $tagString[] = $tag->getName();
            }
        }

        $images = [];
        foreach ($product->getMediaGalleryImages() as $image) {
            $images[] = Mage::helper('catalog/image')->init($product, 'image', $image->getFile())->resize(250)->__toString();
        }

        $featureImage = Mage::helper('catalog/image')->init($product, 'image')->resize(250)->__toString();

        $data = [
            'sku' => $product->getData('sku'),
            'handle' => $product->getUrlPath(),
            'title' => $product->getName(),
            'type' => $product->getTypeId(),
            'ref_id' => (int)$product->getId(),
            'vendor' => '',
            'published_at' => $product->getVisibility() != 1 ? $createdAt : null,
            'updated_at' => $updatedAt,
            'created_at' => $createdAt,
            'out_stock' => !$product->getIsSalable(),
            'image_source_url' => $featureImage,
            'price' => $product->getPrice(),
            'price_compare' => ($product->getFinalPrice() < $product->getPrice()) ? $product->getFinalPrice() : null,
            'tags' => count($tagString) ? implode(',', $tagString) : null,
            'description' => $product->getDescription(),
            'images' => $images,
            'featured_image' => $featureImage,
            'attributes' => [
                'store_id' => $product->getStoreIds(),
                'website_ids' => $product->getWebsiteIds(),
            ],
            'variants' => $this->getProductVariants($product),
        ];
        return $data;
    }

    /**
     * Add product to Beeketing Api
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductVariants($product)
    {
        // Get product type
        $productType = $product->getTypeId();

        // Variation product
        $variationProduct = [];

        // Get product variation data by product type
        switch ($productType) {
            // Get all configurable
            case 'configurable' :
                // Get product collection configurable
                $configurableProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

                foreach ($configurableProducts as $configurableProduct) {
                    // Not save this product
                    if ($configurableProduct->getId() == $product->getId()) {
                        continue;
                    }
                    $configurableProduct = Mage::getModel('catalog/product')->load($configurableProduct->getId());
                    $variationProduct[] = $this->getFormattedVariant($configurableProduct, $product,  $productAttributeOptions);
                }
                // Get config attribute
                $productModelConfigurable = Mage::getModel('catalog/product_type_configurable');
                $config = $productModelConfigurable->getConfigurableAttributes($product)->getData();
                $variationProduct['raw_data']['configurable'] = $config;
                break;
            // Get all grouped
            case 'grouped' :
                $groupedProducts = Mage::getModel('catalog/product_type_grouped')->getAssociatedProducts($product);
                foreach ($groupedProducts as $groupedProduct) {
                    $variationProduct[] = $this->getFormattedVariant($groupedProduct, $product, $groupedProducts);
                }
                break;
            // Product simple and virtual, downloadable, bundle
            default:
                $variationProduct[] = $this->getFormattedVariant($product);
                break;

        }

        $variantWithOptions = [];

        foreach ($product->getOptions() as $o) {
            $optionType = $o->getType();

            if ($optionType == 'drop_down') {
                $values = $o->getValues();

                foreach ($values as $k => $v) {
                    foreach ($variationProduct as $variant) {
                        $variant['attributes'][] = [
                            'name' => $v->getTitle(),
                            'slug' => $v->getTitle(),
                            'option' => $v->getTitle()
                        ];

                        $variant['attributes']['custom_option'][$o->getId()] = $v->getOptionTypeId();
                        if ($v->getPriceType() == 'fixed') {
                            $variant['price'] = (string) ($variant['price'] + $v->getPrice());
                            if ($variant['price_compare']) {
                                $variant['price_compare'] = (string) ($variant['price_compare'] + $v->getPrice());
                            }
                        } else {
                            $variant['price'] = (string) ($variant['price'] + $variant['price'] * $v->getPrice() / 100);
                            if ($variant['price_compare']) {
                                $variant['price_compare'] = (string) ($variant['price_compare'] + $variant['price_compare'] * $v->getPrice() / 100);
                            }
                        }

                        $variantWithOptions[] = $variant;
                    }
                }
            }
        }

        if (count($variantWithOptions) > 0) {
            return $variantWithOptions;
        }

        return $variationProduct;

    }

    /**
     * Get data of product for Beeketing Api
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Product $parent
     * @param $productAttributeOptions array
     * @return array
     */
    public function getFormattedVariant($product, $parent = null, $productAttributeOptions = [])
    {
        // Get date product
        $date = Mage::getModel('core/date');
        $dateUpdated = $date->gmtDate(BeeketingSDK_Config_BeeketingConfig::BEEKETING_FORMAT_DATE, $product->getUpdatedAt());
        $dateCreated = $date->gmtDate(BeeketingSDK_Config_BeeketingConfig::BEEKETING_FORMAT_DATE, $product->getCreatedAt());

        // Get stock model of product
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

        $data = [
            'ref_id' => (int)$product->getId(),
            'title' => $product->getName(),
            'product_ref_id' => $parent ? (int) $parent->getId(): (int) $product->getId(),
            'price' => $product->getPrice(),
            'price_compare' => ($product->getFinalPrice() < $product->getPrice()) ? $product->getFinalPrice() : null,
            'sku' => $product->getData('sku'),
            'inventory_management' => null,
            'inventory_quantity' => $stock->getQty(),
            'in_stock' => $product->isInStock(),
            'weight' => $product->getWeight(),
            'taxable' => $product->getTaxClassId(),
            'visible' => $product->getIsSalable(),
            'attributes' => [
                'store_id' => $product->getStoreIds(),
                'website_ids' => $product->getWebsiteIds(),
            ],
            'updated' => $dateUpdated,
            'created' => $dateCreated,
        ];

        if ($product->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
            $links=Mage::getModel('downloadable/link')
                ->getCollection()
                ->addFieldToFilter('product_id',array('eq'=>$product->getId()));

            foreach($links as $key => $link){
                $data['attributes'][] = [
                    'name' => $key,
                    'slug' => $key,
                    'option' => $key
                ];

                $data['attributes']['links'][] = $link->getId();
            }
        } elseif($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            // Get all product of bundle
            $bundleProducts = $collection = $product->getTypeInstance(true)
                ->getSelectionsCollection(
                    $product->getTypeInstance(true)
                        ->getOptionsIds($product), $product);

            // Get only one option for each child product
            foreach ($bundleProducts as $bundleProduct) {
                $data['attributes']['bundle_option'][$bundleProduct->option_id] = $bundleProduct->selection_id;
                if ($bundleProduct->selection_can_change_qty) {
                    $bundleQty = 1;
                } else {
                    $bundleQty = (int) $bundleProduct->selection_qty;
                }

                $data['attributes']['bundle_option_qty'][$bundleProduct->option_id] = $bundleQty;
            }
        } elseif ($parent && $parent->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $data['attributes']['super_group'][$product->getId()] = 1;
            foreach ($productAttributeOptions as $childProduct) {
                if ($childProduct->getId() == $product->getId()) {
                    continue;
                }
                $data['attributes']['super_group'][$childProduct->getId()] = 0;
            }
        }

        if (count($productAttributeOptions)) {
            // Get config attribute
            $productData = $product->getData();
            foreach ($productAttributeOptions as $index => $productAttribute) {
                if (isset($productData[$productAttribute['attribute_code']])) {
                    foreach ($productAttribute['values'] as $attribute) {
                        if ($attribute['value_index'] == $productData[$productAttribute['attribute_code']]) {
                            $data['option' . ($index + 1)] = $productAttribute['attribute_code'] . ' ' . $attribute['store_label'];
                            $data['attributes'][] = [
                                'name' => $productAttribute['attribute_code'],
                                'slug' => $productAttribute['attribute_code'],
                                'option' => $attribute['store_label']
                            ];
                            $data['attributes']['super_attribute'][$productAttribute['attribute_id']] = $attribute['value_index'];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Add product to Beeketing Api
     * @param Mage_Catalog_Model_Product $product
     * @param string $requestType
     */
    public function saveToBeeketingApi($product, $requestType = BeeketingSDK_Config_BeeketingConfig::SOURCE_TYPE_WEBHOOK)
    {
        // Load product magento
        // This is function get all attribute of magento
        $product = Mage::getModel('catalog/product')->load($product->getId());

        // Get product data
        $productData = $this->getFormattedProduct($product);

        // Push product data to Beeketing platform
        Mage::helper('beeketing_integrateapp/core')->sendRequest('products/create_update', $productData,
            Zend_Http_Client::POST, ['X-Beeketing-Source' => $requestType]);

    }
}