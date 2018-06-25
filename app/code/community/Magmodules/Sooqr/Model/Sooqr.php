<?php
/**
 * Magmodules.eu - http://www.magmodules.eu
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Sooqr
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Sooqr_Model_Sooqr extends Magmodules_Sooqr_Model_Common
{

    /**
     * @var Magmodules_Sooqr_Helper_Data
     */
    public $helper;
    /**
     * @var Mage_Tax_Helper_Data
     */
    public $taxHelper;
    /**
     * @var Mage_Core_Model_Config
     */
    public $config;

    /**
     * Magmodules_Sooqr_Model_Sooqr constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('sooqr');
        $this->taxHelper = Mage::helper('tax');
        $this->config = Mage::getModel('core/config');
    }

    /**
     * @param string $type
     * @param null   $storeId
     * @param bool   $return
     *
     * @return array|null
     */
    public function runScheduled($type = 'cron', $storeId = null, $return = false)
    {
        $returnValue = null;
        $enabled = $this->helper->getConfigData('general/enabled');
        $cron = $this->helper->getConfigData('generate/cron');
        $timeStart = microtime(true);

        if ($enabled && $cron) {
            if ($storeId == null) {
                $nextStore = $this->helper->getUncachedConfigValue('sooqr_connect/generate/cron_next');
                $storeIds = $this->helper->getStoreIds('sooqr_connect/generate/enabled');
                if(!count($storeIds)) {
                    return $returnValue;
                }

                if (empty($nextStore) || ($nextStore >= count($storeIds))) {
                    $nextStore = 0;
                }

                $storeId = $storeIds[$nextStore];
                $nextStore++;
            }

            try {
                /** @var Mage_Core_Model_App_Emulation $appEmulation */
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                if ($result = $this->generateFeed($storeId)) {
                    $this->updateConfig($result, $type, $timeStart, $storeId);
                }

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                $returnValue = ($return) ? $result : null;
            } catch (\Exception $e) {
                $this->helper->addToLog('runScheduled', $e->getMessage(), null, true);
            }

            if (!empty($nextStore)) {
                $this->config->saveConfig('sooqr_connect/generate/cron_next', $nextStore, 'default', 0);
            }
        }

        return $returnValue;
    }
    
    /**
     * @param $result
     * @param $type
     * @param $timeStart
     * @param $storeId
     */
    public function updateConfig($result, $type, $timeStart, $storeId)
    {
        $html = sprintf(
            '<a href="%s" target="_blank">%s</a><br/><small>On: %s (%s) - Products: %s/%s - Time: %s</small>',
            $result['url'],
            $result['url'],
            $result['date'],
            $type,
            $result['qty'],
            $result['pages'],
            $this->helper->getTimeUsage($timeStart)
        );

        $this->config->saveConfig('sooqr_connect/generate/feed_result', $html, 'stores', $storeId);

        if ($this->helper->getConfigData('generate/log_generation', $storeId)) {
            $msg = strip_tags(str_replace('<br/>', ' => ', $html));
            $this->helper->addToLog('Feed Generation Store ID ' . $storeId, $msg, null, true);
        }
    }
    
    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function generateFeed($storeId, $type = 'xml')
    {
        $timeStart = microtime(true);
        $this->setMemoryLimit($storeId);
        $config = $this->getFeedConfig($storeId, $type);
        $io = $this->helper->createFeed($config);
        $summary = $this->getFeedHeader($config);
        $this->helper->writeRow($summary, $io, 'config');
        $products = $this->getProducts($config);

        if ($type == 'preview') {
            $pages = 1;
        } else {
            $pages = $products->getLastPageNumber();
        }

        $curPage = 1;
        $processed = 0;

        do {
            $products->setCurPage($curPage);
            $products->load();

            if ($config['reviews']) {
                Mage::getModel('review/review')->appendSummary($products);
            }

            $parentRelations = $this->helper->getParentsFromCollection($products, $config);
            $parents = $this->getParents($parentRelations, $config);
            $prices = $this->helper->getTypePrices($config, $parents);
            $parentAttributes = $this->helper->getConfigurableAttributesAsArray($parents, $config);
            $processed += $this->getFeedData($products, $parents, $config, $parentAttributes, $prices, $io, $parentRelations);

            if ($config['debug_memory'] && ($type != 'preview')) {
                $this->helper->addLog($curPage, $pages, $processed);
            }

            $products->clear();
            $parents = null;
            $prices = null;
            $parentAttributes = null;
            $curPage++;
        } while ($curPage <= $pages);

        if ($cmsPages = $this->getCmspages($config)) {
            foreach ($cmsPages as $item) {
                $this->helper->writeRow($item, $io, 'product');
            }
        }

        $summary = $this->getFeedResults($timeStart, $processed, $config['limit']);
        $this->helper->writeRow($summary, $io, 'results');

        $feedStats = array();
        $feedStats['qty'] = $processed;
        $feedStats['date'] = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $feedStats['url'] = $config['file_url'];
        $feedStats['pages'] = $pages;

        $this->helper->closeFeed($io, $config);

        return $feedStats;
    }

    /**
     * @param $storeId
     */
    protected function setMemoryLimit($storeId)
    {
        if ($this->helper->getConfigData('generate/overwrite', $storeId)) {
            if ($memoryLimit = $this->helper->getConfigData('generate/memory_limit', $storeId)) {
                ini_set('memory_limit', $memoryLimit);
            }

            if ($maxExecutionTime = $this->helper->getConfigData('generate/max_execution_time', $storeId)) {
                ini_set('max_execution_time', $maxExecutionTime);
            }
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getFeedConfig($storeId, $type = 'xml')
    {
        $config = array();

        /** @var  Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        /** @var  Mage_Core_Model_Website $website */
        $website = Mage::getModel('core/website')->load($store->getWebsiteId());
        $websiteId = $website->getId();
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $attribute */
        $attribute = Mage::getResourceModel('eav/entity_attribute');

        $config['store_id'] = $storeId;
        $config['website_id'] = $websiteId;
        $config['website_name'] = $this->helper->cleanData($website->getName(), 'striptags');
        $config['website_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_gallery_id'] = $attribute->getIdByCode('catalog_product', 'media_gallery');
        $config['image_source'] = $this->helper->getConfigData('products/image_source', $storeId);
        $config['image_resize'] = $this->helper->getConfigData('products/image_resize', $storeId);
        $config['file_name_temp'] = $this->getFileName('sooqr_connect', $storeId, $type, true);
        $config['file_name'] = $this->getFileName('sooqr_connect', $storeId, $type);
        $config['file_path'] = Mage::getBaseDir() . DS . 'media' . DS . 'sooqr';
        $config['file_url'] = $config['media_url'] . 'sooqr' . DS . $config['file_name'];
        $config['version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Sooqr->version;
        $config['filter_enabled'] = $this->helper->getConfigData('products/category_enabled', $storeId);
        $config['filter_cat'] = $this->helper->getConfigData('products/categories', $storeId);
        $config['filter_type'] = $this->helper->getConfigData('products/category_type', $storeId);
        $config['filter_status'] = $this->helper->getConfigData('products/visibility_inc', $storeId);
        $config['hide_no_stock'] = $this->helper->getConfigData('products/stock', $storeId);

        $config['reviews'] = $this->helper->getConfigData('products/reviews');
        $config['cms_pages'] = $this->helper->getConfigData('products/cms_pages', $storeId);
        $config['cms_include'] = $this->helper->getConfigData('products/cms_include', $storeId);
        $config['filters'] = $this->helper->getSerializedConfigData('products/advanced', $storeId);
        $config['product_url_suffix'] = $this->helper->getProductUrlSuffix($storeId);
        $config['token'] = $this->helper->getConfigData('generate/token');
        $config['conf_switch_urls'] = true;

        // PRICE
        $config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = $this->helper->getConfigData('products/add_tax', $storeId);
        $config['price_add_tax_perc'] = $this->helper->getConfigData('products/tax_percentage', $storeId);
        $config['price_grouped'] = $this->helper->getConfigData('products/grouped_price', $storeId);
        $config['simple_price'] = $this->helper->getConfigData('products/simple_price', $storeId);
        $config['force_tax'] = $this->helper->getConfigData('products/force_tax', $storeId);
        $config['price_rules'] = true;
        $config['currency'] = $store->getDefaultCurrencyCode();
        $config['currency_allow'] = Mage::getStoreConfig('currency/options/allow', $storeId);
        $config['hide_currency'] = true;
        $config['base_currency_code'] = $store->getBaseCurrencyCode();
        $config['currency_data'] = $this->getCurrencies($storeId, $config['base_currency_code'], $config['currency']);
        $config['conf_enabled'] = $this->helper->getConfigData('products/conf_enabled', $storeId);
        $config['conf_fields'] = $this->helper->getConfigData('products/conf_fields', $storeId);
        $config['bypass_flat'] = $this->helper->getConfigData('generate/bypass_flat', $storeId);
        $config['debug_memory'] = $this->helper->getConfigData('generate/debug_memory', $storeId);
        $config['markup'] = $this->helper->getPriceMarkup($config);
        $config['use_tax'] = $this->helper->getTaxUsage($config);
        $config['inventory'] = $this->getInventoryData();

        // FIELD & CATEGORY DATA
        $config['field'] = $this->getFeedAttributes($storeId, $type, $config);
        $config['parent_att'] = $this->getParentAttributeSelection($config['field']);
        $config['root_category_id'] = $store->getRootCategoryId();
        $config['category_data'] = $this->helper->getCategoryData($config, $storeId);

        if ($config['image_resize'] == 'fixed') {
            $config['image_size'] = $this->helper->getConfigData('products/image_size_fixed', $storeId);
        } else {
            $config['image_size'] = $this->helper->getConfigData('products/image_size_custom', $storeId);
        }

        if ($limit = $this->helper->getConfigData('generate/limit', $storeId)) {
            $config['limit'] = preg_replace('/\D/', '', $limit);
        } else {
            $config['limit'] = '';
        }

        if ($type == 'preview') {
            $config['limit'] = 100;
        }

        return $config;
    }

    /**
     * @param      $feed
     * @param      $storeId
     * @param null $type
     * @param bool $temp
     *
     * @return mixed|string
     */
    public function getFileName($feed, $storeId, $type = null, $temp = false)
    {
        if (!$fileName = Mage::getStoreConfig($feed . '/generate/filename', $storeId)) {
            $fileName = $feed . '.xml';
        }

        if (substr($fileName, -3) != 'xml') {
            $fileName = $fileName . '-' . $storeId . '.xml';
        } else {
            $fileName = substr($fileName, 0, -4) . '-' . $storeId . '.xml';
        }

        if ($type == 'preview') {
            $fileName = str_replace('.xml', '-preview.xml', $fileName);
        }

        if ($temp) {
            $fileName = time() . '-' . $fileName;
        }

        return $fileName;
    }

    /**
     * @param $storeId
     * @param $baseCurrency
     * @param $storeCurrency
     *
     * @return array
     */
    public function getCurrencies($storeId, $baseCurrency, $storeCurrency)
    {
        $allow = explode(',', Mage::getStoreConfig('currency/options/allow', $storeId));
        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrency, array_values($allow));

        if ($baseCurrency == $storeCurrency) {
            return $rates;
        }

        if (!isset($rates[$baseCurrency]) || !isset($rates[$storeCurrency])) {
            return $rates;
        }

        $baseRates = array();
        foreach ($rates as $key => $rate) {
            $baseRates[$key] = $rate * ($rates[$baseCurrency] / $rates[$storeCurrency]);
        }

        return $baseRates;
    }

    /**
     * @return array
     */
    public function getInventoryData()
    {
        $invAtt = array();
        $invAtt['attributes'][] = 'qty';
        $invAtt['attributes'][] = 'is_in_stock';
        $invAtt['attributes'][] = 'use_config_manage_stock';
        $invAtt['attributes'][] = 'use_config_qty_increments';
        $invAtt['attributes'][] = 'enable_qty_increments';
        $invAtt['attributes'][] = 'use_config_enable_qty_inc';
        $invAtt['attributes'][] = 'use_config_min_sale_qty';
        $invAtt['attributes'][] = 'backorders';
        $invAtt['attributes'][] = 'use_config_backorders';
        $invAtt['attributes'][] = 'manage_stock';
        $invAtt['config_backorders'] = Mage::getStoreConfig('cataloginventory/item_options/backorders');
        $invAtt['config_manage_stock'] = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
        $invAtt['config_qty_increments'] = Mage::getStoreConfig('cataloginventory/item_options/qty_increments');
        $invAtt['config_enable_qty_inc'] = Mage::getStoreConfig('cataloginventory/item_options/enable_qty_increments');
        $invAtt['config_min_sale_qty'] = Mage::getStoreConfig('cataloginventory/item_options/min_qty');

        return $invAtt;
    }

    /**
     * @param int    $storeId
     * @param string $type
     * @param string $config
     *
     * @return mixed
     */
    public function getFeedAttributes($storeId = 0, $type = 'xml', $config = '')
    {
        $attributes = array();
        $attributes['content_type'] = array(
            'label'  => 'content_type',
            'static' => 'product'
        );
        $attributes['id'] = array(
            'label'  => 'id',
            'source' => $this->helper->getConfigData('products/id_attribute', $storeId)
        );
        $attributes['name'] = array(
            'label'  => 'title',
            'source' => $this->helper->getConfigData('products/name_attribute', $storeId)
        );
        $attributes['sku'] = array(
            'label'  => 'sku',
            'source' => $this->helper->getConfigData('products/sku_attribute', $storeId)
        );
        $attributes['description'] = array(
            'label'  => 'description',
            'source' => $this->helper->getConfigData('products/description_attribute', $storeId),
            'action' => 'striptags'
        );
        $attributes['brand'] = array(
            'label'  => 'brand',
            'source' => $this->helper->getConfigData('products/brand_attribute', $storeId)
        );
        $attributes['product_url'] = array(
            'label'  => 'url',
            'source' => ''
        );
        $attributes['image_link'] = array(
            'label'  => 'image_link',
            'source' => $this->helper->getConfigData('products/image_source', $storeId)
        );
        $attributes['price'] = array(
            'label'  => 'price',
            'source' => ''
        );
        $attributes['parent_id'] = array(
            'label'  => 'assoc_id',
            'source' => 'entity_id',
            'parent' => 1
        );
        $attributes['qty'] = array(
            'label'  => 'stock',
            'source' => 'qty',
            'action' => 'round'
        );
        $attributes['stock_status'] = array(
            'label'  => 'stock_status',
            'source' => 'is_in_stock'
        );
        $attributes['type'] = array(
            'label'  => 'product_object_type',
            'source' => 'type_id'
        );
        $attributes['visibility'] = array(
            'label'  => 'visibility',
            'source' => 'visibility'
        );
        $attributes['status'] = array(
            'label'  => 'status',
            'source' => 'status'
        );
        $attributes['categories'] = array(
            'label'  => 'categories',
            'source' => '',
            'parent' => 1
        );
        $attributes['manage_stock'] = array(
            'label'  => 'manage_stock',
            'source' => 'manage_stock'
        );
        $attributes['allow_backorder'] = array(
            'label'  => 'backorders',
            'source' => 'allow_backorder'
        );
        $attributes['is_saleable'] = array(
            'label'  => 'is_saleable',
            'source' => 'is_saleable'
        );
        if ($extraFields = @unserialize($this->helper->getConfigData('products/extra', $storeId))) {
            foreach ($extraFields as $extraField) {
                $attributes[$extraField['attribute']] = array(
                    'label'  => $extraField['attribute'],
                    'source' => $extraField['attribute'],
                    'action' => 'striptags'
                );
            }
        }

        if ($type == 'flatcheck') {
            if ($filters = @unserialize($this->helper->getConfigData('products/advanced', $storeId))) {
                foreach ($filters as $filter) {
                    $attributes[$filter['attribute']] = array(
                        'label'  => $filter['attribute'],
                        'source' => $filter['attribute']
                    );
                }
            }
        }

        if ($type != 'config') {
            return $this->helper->addAttributeData($attributes, $config);
        } else {
            return $attributes;
        }
    }

    /**
     * @param $config
     *
     * @return array
     */
    public function getFeedHeader($config)
    {
        $header = array();
        $header['system'] = 'Magento';
        $header['extension'] = 'Magmodules_Sooqr';
        $header['extension_version'] = $config['version'];
        $header['store'] = $config['website_name'];
        $header['url'] = $config['website_url'];
        $header['token'] = $config['token'];
        return $header;
    }

    /**
     * @param $products
     * @param $parents
     * @param $config
     * @param $parentAttributes
     * @param $prices
     * @param $io
     *
     * @return int
     */
    public function getFeedData($products, $parents, $config, $parentAttributes, $prices, $io, $parentRelations)
    {
        $qty = 0;

        foreach ($products as $product) {
            $parent = null;
            if (!empty($parentRelations[$product->getEntityId()])) {
                foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                    if ($parent = $parents->getItemById($parentId)) {
                        continue;
                    }
                }
            }

            $productData = $this->helper->getProductDataRow($product, $config, $parent, $parentAttributes);

            if ($productData) {
                $productRow = array();
                foreach ($productData as $key => $value) {
                    if (!is_array($value)) {
                        $productRow[$key] = $value;
                    }
                }

                if ($extraData = $this->getExtraDataFields($productData, $config, $product, $prices)) {
                    $productRow = array_merge($productRow, $extraData);
                }

                $this->helper->writeRow($productRow, $io);

                $productRow = null;
                $qty++;
            }
        }

        return $qty;
    }

    /**
     * @param $productData
     * @param $config
     * @param $product
     * @param $prices
     *
     * @return array
     */
    public function getExtraDataFields($productData, $config, $product, $prices)
    {
        $extraDataFields = array();
        if ($categoryData = $this->getCategoryData($productData)) {
            $extraDataFields = array_merge($extraDataFields, $categoryData);
        }

        if (!empty($productData['assoc_id'])) {
            $itemGroupId = $productData['assoc_id'];
        } else {
            $itemGroupId = '';
        }

        if ($priceData = $this->getPrices(
            $productData['price'],
            $prices,
            $product,
            $config['currency'],
            $itemGroupId
        )
        ) {
            $extraDataFields = array_merge($extraDataFields, $priceData);
        }

        if ($assocId = $this->getAssocId($productData)) {
            $extraDataFields = array_merge($extraDataFields, $assocId);
        }

        if ($reviewData = $this->getReviewData($config, $product)) {
            $extraDataFields = array_merge($extraDataFields, $reviewData);
        }

        return $extraDataFields;
    }

    /**
     * @param $productData
     *
     * @return array
     */
    public function getCategoryData($productData)
    {
        $category = array();
        if (!empty($productData['categories'])) {
            foreach ($productData['categories'] as $cat) {
                if (!empty($cat['path'])) {
                    $i = 0;
                    foreach ($cat['path'] as $catpath) {
                        $category[$i][] = $catpath;
                        $i++;
                    }
                }
            }
        }

        $categoryArray = array();
        $i = 0;
        if (!empty($category)) {
            foreach ($category as $cat) {
                $categoryArray['category' . $i] = array_unique($cat);
                $i++;
            }
        }

        return $categoryArray;
    }

    /**
     * @param                            $data
     * @param                            $confPrices
     * @param Mage_Catalog_Model_Product $product
     * @param                            $currency
     * @param                            $itemGroupId
     *
     * @return array
     */
    public function getPrices($data, $confPrices, $product, $currency, $itemGroupId)
    {
        $prices = array();
        $id = $product->getEntityId();
        $parentPriceIndex = $itemGroupId . '_' . $id;
        $prices['currency'] = $currency;
        if ($itemGroupId && !empty($confPrices[$parentPriceIndex])) {
            $confPrice = $this->taxHelper->getPrice($product, $confPrices[$parentPriceIndex], true);
            $confPriceReg = $this->taxHelper->getPrice($product, $confPrices[$parentPriceIndex . '_reg'], true);
            if ($confPriceReg > $confPrice) {
                $prices['special_price'] = number_format($confPrice, 2, '.', '');
                $prices['price'] = number_format($confPriceReg, 2, '.', '');
            } else {
                $prices['price'] = number_format($confPrice, 2, '.', '');
            }
        } else {
            if (!empty($config['currency_data'])) {
                foreach ($config['currency_data'] as $key => $value) {
                    if ($currency == $key) {
                        if (isset($data['sales_price'])) {
                            $prices['normal_price'] = $data['regular_price'];
                            $prices['price'] = $data['sales_price'];
                        } else {
                            $prices['price'] = $data['price'];
                        }
                    } else {
                        if (isset($data['sales_price'])) {
                            $prices['normal_price_' . strtolower($key)] = round(($data['regular_price'] * $value), 2);
                            $prices['price_' . strtolower($key)] = round(($data['sales_price'] * $value), 2);
                        } else {
                            $prices['price_' . strtolower($key)] = round(($data['price'] * $value), 2);
                        }
                    }
                }
            } else {
                if (isset($data['sales_price'])) {
                    $prices['normal_price'] = $data['regular_price'];
                    $prices['price'] = $data['sales_price'];
                } else {
                    $prices['price'] = $data['price'];
                }
            }
        }

        return $prices;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getAssocId($data)
    {
        $assocId = array();
        if (empty($data['assoc_id'])) {
            $assocId['assoc_id'] = $data['id'];
        }

        if (isset($data['product_object_type']) && $data['product_object_type'] != 'simple') {
            $assocId['is_parent'] = '1';
        } else {
            $assocId['is_parent'] = '0';
        }

        return $assocId;
    }

    /**
     * @param                            $config
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function getReviewData($config, $product)
    {
        $reviewData = array();
        if ($config['reviews'] && $ratingSummary = $product->getRatingSummary()) {
            $reviewData['rating'] = $ratingSummary->getRatingSummary();
        }

        return $reviewData;
    }

    /**
     * @param $config
     *
     * @return array
     */
    public function getCmspages($config)
    {
        $cmspages = array();
        if ($config['cms_pages'] == 1) {
            $exclude = array('no-route', 'enable-cookies');
            $pages = Mage::getModel('cms/page')->getCollection()
                ->addStoreFilter($config['store_id'])
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('identifier', array('nin' => $exclude));
        } else {
            $cmsInclude = explode(',', $config['cms_include']);
            $pages = Mage::getModel('cms/page')->getCollection()
                ->addStoreFilter($config['store_id'])
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('page_id', array('in' => $cmsInclude));
        }

        foreach ($pages as $page) {
            $cmspages[] = array(
                'content_type' => 'cms',
                'id'           => 'CMS-' . $page->getId(),
                'title'        => $page->getTitle(),
                'content'      => $this->helper->cleanData($page->getContent(), 'striptags'),
                'url'          => $config['website_url'] . $page->getIdentifier()
            );
        }

        return $cmspages;
    }

    /**
     * @param $timeStart
     * @param $count
     * @param $limit
     *
     * @return array
     */
    public function getFeedResults($timeStart, $count, $limit)
    {
        $summary = array();
        $summary['products_total'] = $count;
        $summary['products_limit'] = $limit;
        $summary['processing_time'] = number_format((microtime(true) - $timeStart), 4);
        $summary['date_created'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        return $summary;
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getInstallation()
    {
        $json = array();
        $json['search']['enabled'] = '0';
        $storeIds = $this->helper->getStoreIds('sooqr_connect/generate/enabled');
        foreach ($storeIds as $storeId) {
            /** @var Mage_Core_Model_Store $store */
            $store = Mage::getModel('core/store')->load($storeId);
            $mediaUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $fileName = $this->getFileName('sooqr_connect', $storeId);

            $name = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $name = str_replace(array('https://', 'http://', 'www'), '', $name);

            $json['feeds'][$storeId]['name'] = $name;
            $json['feeds'][$storeId]['feed_url'] = $mediaUrl . DS . 'sooqr' . DS . $fileName;
            $json['feeds'][$storeId]['currency'] = $store->getBaseCurrencyCode();
            $json['feeds'][$storeId]['locale'] = Mage::getStoreConfig('general/locale/code', $storeId);
            $json['feeds'][$storeId]['country'] = Mage::getStoreConfig('general/country/default', $storeId);
            $json['feeds'][$storeId]['timezone'] = Mage::getStoreConfig('general/locale/timezone', $storeId);
            $json['feeds'][$storeId]['system'] = Mage::getStoreConfig('general/locale/timezone', $storeId);
            $json['feeds'][$storeId]['extension'] = 'Magmodules_Sooqr';
            $json['feeds'][$storeId]['extension_version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Sooqr->version;
        }

        return $json;
    }

}