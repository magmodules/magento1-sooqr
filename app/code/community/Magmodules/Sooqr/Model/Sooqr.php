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
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Sooqr_Model_Sooqr extends Magmodules_Sooqr_Model_Common
{

    /**
     * @param $storeId
     * @param $timeStart
     *
     * @return array
     */
    public function generateFeed($storeId, $timeStart)
    {
        $this->setMemoryLimit($storeId);
        $helper = Mage::helper('sooqr');
        $config = $this->getFeedConfig($storeId);
        $products = $this->getProducts($config, $config['limit']);
        $prices = $helper->getTypePrices($config, $products);
        $parentAttributes = $helper->getConfigurableAttributesAsArray($products, $config);

        $io = $helper->createFeed($config);
        $summary = $this->getFeedHeader($config);
        $helper->writeRow($summary, $io, 'config');
        $feedStats = $this->getFeedData($products, $config, $parentAttributes, $prices, $io, $timeStart);
        $helper->closeFeed($io, $config);

        return $feedStats;
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getFeedConfig($storeId, $type = 'xml')
    {

        $config = array();
        $feed = Mage::helper('sooqr');
        $filename = $this->getFileName('sooqr', $storeId);
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        // DEFAULTS
        $config['store_id'] = $storeId;
        $config['website_name'] = $feed->cleanData(Mage::getModel('core/website')->load($websiteId)->getName(), 'striptags');
        $config['website_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_gallery_id'] = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'media_gallery');
        $config['image_source'] = Mage::getStoreConfig('sooqr_connect/products/image_source', $storeId);
        $config['image_resize'] = Mage::getStoreConfig('sooqr_connect/products/image_resize', $storeId);
        $config['file_name'] = $filename;
        $config['limit'] = Mage::getStoreConfig('sooqr_connect/generate/limit', $storeId);
        $config['version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Sooqr->version;
        $config['filter_enabled'] = Mage::getStoreConfig('sooqr_connect/products/category_enabled', $storeId);
        $config['filter_cat'] = Mage::getStoreConfig('sooqr_connect/products/categories', $storeId);
        $config['filter_type'] = Mage::getStoreConfig('sooqr_connect/products/category_type', $storeId);
        $config['cms_pages'] = Mage::getStoreConfig('sooqr_connect/products/cms_pages', $storeId);
        $config['cms_include'] = Mage::getStoreConfig('sooqr_connect/products/cms_include', $storeId);
        $config['filters'] = @unserialize(Mage::getStoreConfig('sooqr_connect/products/advanced', $storeId));
        $config['product_url_suffix'] = $feed->getProductUrlSuffix($storeId);
        $config['stock_manage'] = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
        $config['backorders'] = Mage::getStoreConfig('cataloginventory/item_options/backorders');
        $config['token'] = Mage::getStoreConfig('sooqr_connect/generate/token');

        // PRICE
        $config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = Mage::getStoreConfig('sooqr_connect/products/add_tax', $storeId);
        $config['price_add_tax_perc'] = Mage::getStoreConfig('sooqr_connect/products/tax_percentage', $storeId);
        $config['price_grouped'] = Mage::getStoreConfig('sooqr_connect/products/grouped_price', $storeId);
        $config['simple_price'] = Mage::getStoreConfig('sooqr_connect/products/simple_price', $storeId);
        $config['force_tax'] = Mage::getStoreConfig('sooqr_connect/products/force_tax', $storeId);
        $config['price_rules'] = true;
        $config['currency'] = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        $config['currency_allow'] = Mage::getStoreConfig('currency/options/allow', $storeId);
        $config['hide_currency'] = true;
        $config['base_currency_code'] = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
        $config['currency_data'] = $this->getCurrencies($storeId, $config['base_currency_code'], $config['currency']);
        $config['conf_enabled'] = Mage::getStoreConfig('sooqr_connect/products/conf_enabled', $storeId);
        $config['conf_fields'] = Mage::getStoreConfig('sooqr_connect/products/conf_fields', $storeId);

        $config['markup'] = $feed->getPriceMarkup($config);
        $config['use_tax'] = $feed->getTaxUsage($config);

        // FIELD & CATEGORY DATA
        $config['field'] = $this->getFeedAttributes($storeId, $type, $config);
        $config['category_data'] = $feed->getCategoryData($config, $storeId);

        if ($config['image_resize'] == 'fixed') {
            $config['image_size'] = Mage::getStoreConfig('sooqr_connect/products/image_size_fixed', $storeId);
        } else {
            $config['image_size'] = Mage::getStoreConfig('sooqr_connect/products/image_size_custom', $storeId);
        }

        $websiteUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['file_name'] = $this->getFileName('sooqr', $storeId);
        $config['file_path'] = Mage::getBaseDir() . DS . 'media' . DS . 'sooqr';
        $config['file_url'] = $websiteUrl . 'sooqr' . DS . $config['file_name'];

        return $config;
    }

    /**
     * @param $type
     * @param $storeId
     *
     * @return mixed|string
     */
    public function getFileName($type, $storeId)
    {
        if (!$fileName = Mage::getStoreConfig($type . '/generate/filename', $storeId)) {
            $fileName = $type . '.xml';
        }

        if (substr($fileName, -3) != 'xml') {
            $fileName = $fileName . '-' . $storeId . '.xml';
        } else {
            $fileName = substr($fileName, 0, -4) . '-' . $storeId . '.xml';
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

        $baseRates = array();
        foreach ($rates as $key => $rate) {
            $baseRates[$key] = $rate * ($rates[$baseCurrency] / $rates[$storeCurrency]);
        }

        return $baseRates;
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
            'source' => Mage::getStoreConfig('sooqr_connect/products/id_attribute', $storeId)
        );
        $attributes['name'] = array(
            'label'  => 'title',
            'source' => Mage::getStoreConfig('sooqr_connect/products/name_attribute', $storeId)
        );
        $attributes['sku'] = array(
            'label'  => 'sku',
            'source' => Mage::getStoreConfig('sooqr_connect/products/sku_attribute', $storeId)
        );
        $attributes['description'] = array(
            'label'  => 'description',
            'source' => Mage::getStoreConfig('sooqr_connect/products/description_attribute', $storeId),
            'action' => 'striptags'
        );
        $attributes['brand'] = array(
            'label'  => 'brand',
            'source' => Mage::getStoreConfig('sooqr_connect/products/brand_attribute', $storeId)
        );
        $attributes['product_url'] = array(
            'label'  => 'url',
            'source' => ''
        );
        $attributes['image_link'] = array(
            'label'  => 'image_link',
            'source' => Mage::getStoreConfig('sooqr_connect/products/image_source', $storeId)
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
            'source' => 'stock_status'
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
        if ($extraFields = @unserialize(Mage::getStoreConfig('sooqr_connect/products/extra', $storeId))) {
            foreach ($extraFields as $extraField) {
                $attributes[$extraField['attribute']] = array(
                    'label'  => $extraField['attribute'],
                    'source' => $extraField['attribute'],
                    'action' => 'striptags'
                );
            }
        }

        if ($type == 'flatcheck') {
            if ($filters = @unserialize(Mage::getStoreConfig('sooqr_connect/products/advanced', $storeId))) {
                foreach ($filters as $filter) {
                    $attributes[$filter['attribute']] = array(
                        'label'  => $filter['attribute'],
                        'source' => $filter['attribute']
                    );
                }
            }
        }

        return Mage::helper('sooqr')->addAttributeData($attributes, $config);
    }

    /**
     * @param $products
     * @param $parents
     * @param $config
     * @param $parentAttributes
     * @param $prices
     * @param $io
     *
     * @return array
     */
    public function getFeedData($products, $config, $parentAttributes, $prices, $io, $timeStart)
    {
        $feedStats = array();
        $helper = Mage::helper('sooqr');
        $qty = 0;

        foreach ($products as $product) {
            if ($parentId = $helper->getParentData($product, $config)) {
                $parent = $products->getItemById($parentId);
            } else {
                $parent = '';
            }

            $productData = $helper->getProductDataRow($product, $config, $parent, $parentAttributes);

            if ($productData) {
                foreach ($productData as $key => $value) {
                    if (!is_array($value)) {
                        $productRow[$key] = $value;
                    }
                }

                if (!empty($parent)) {
                    $sAtts = $this->getSuperAtts($parent);
                } else {
                    $sAtts = '';
                }

                if ($extraData = $this->getExtraDataFields($productData, $config, $product, $sAtts, $parent, $prices)) {
                    $productRow = array_merge($productRow, $extraData);
                }

                $helper->writeRow($productRow, $io, 'product');
                unset($productRow);
                $qty++;
            }
        }

        if ($cmsPages = $this->getCmspages($config)) {
            foreach ($cmsPages as $item) {
                $helper->writeRow($item, $io, 'product');
            }
        }

        $summary = $this->getFeedResults($timeStart, $qty, $config['limit']);
        $helper->writeRow($summary, $io, 'results');

        $feedStats['qty'] = $qty;
        $feedStats['date'] = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $feedStats['url'] = $config['file_url'];
        $feedStats['shop'] = Mage::app()->getStore($config['store_id'])->getCode();

        return $feedStats;
    }

    /**
     * @param $parent
     *
     * @return mixed
     */
    public function getSuperAtts($parent)
    {
        if ($parent->getTypeId() == 'configurable') {
            return $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
        }

        return false;
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

        if ($pricesData = $this->getPrices($productData['price'], $config['currency'], $config, $prices, $product)) {
            $extraDataFields = array_merge($extraDataFields, $pricesData);
        }

        if ($assocId = $this->getAssocId($productData)) {
            $extraDataFields = array_merge($extraDataFields, $assocId);
        }

        if ($stockData = $this->getStockData($config, $product)) {
            $extraDataFields = array_merge($extraDataFields, $stockData);
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
     * @param $data
     * @param $currency
     * @param $config
     * @param $confPrices
     * @param $product
     *
     * @return array
     */
    public function getPrices($data, $currency, $config, $confPrices, $product)
    {
        $prices = array();
        $id = $product->getEntityId();
        $prices['currency'] = $currency;
        if (!empty($confPrices[$id])) {
            $confPrice = Mage::helper('tax')->getPrice($product, $confPrices[$id], true);
            $confPriceReg = Mage::helper('tax')->getPrice($product, $confPrices[$id . '_reg'], true);
            if ($confPriceReg > $confPrice) {
                $prices['price'] = $confPrice;
                $prices['normal_price'] = $confPriceReg;
            } else {
                $prices['price'] = $confPrice;
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

        if ($data['product_object_type'] != 'simple') {
            $assocId['is_parent'] = '1';
        } else {
            $assocId['is_parent'] = '0';
        }

        return $assocId;
    }

    /**
     * @param $config
     * @param $product
     *
     * @return array
     */
    public function getStockData($config, $product)
    {
        $stockData = array();
        if ($product->getUseConfigManageStock()) {
            $stockData['manage_stock'] = (string)$config['stock_manage'];
        } else {
            $stockData['manage_stock'] = (string)$product->getManageStock();
        }

        if ($product->getUseConfigBackorders()) {
            $stockData['backorders'] = (string)$config['backorders'];
        } else {
            $stockData['backorders'] = (string)$product->getBackorders();
        }

        return $stockData;
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
     */
    public function getInstallation()
    {
        $json = array();
        $json['search']['enabled'] = '0';
        $storeIds = Mage::helper('sooqr')->getStoreIds('sooqr_connect/generate/enabled');
        foreach ($storeIds as $storeId) {
            $mediaUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            if (!$fileName = Mage::getStoreConfig('sooqr_connect/generate/filename', $storeId)) {
                $fileName = 'soorq.xml';
            }

            if (substr($fileName, -3) != 'xml') {
                $fileName = $fileName . '-' . $storeId . '.xml';
            } else {
                $fileName = substr($fileName, 0, -4) . '-' . $storeId . '.xml';
            }

            $name = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $name = str_replace(array('https://', 'http://', 'www'), '', $name);

            $json['feeds'][$storeId]['name'] = $name;
            $json['feeds'][$storeId]['feed_url'] = $mediaUrl . DS . 'sooqr' . DS . $fileName;
            $json['feeds'][$storeId]['currency'] = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
            $json['feeds'][$storeId]['locale'] = Mage::getStoreConfig('general/locale/code', $storeId);
            $json['feeds'][$storeId]['country'] = Mage::getStoreConfig('general/country/default', $storeId);
            $json['feeds'][$storeId]['timezone'] = Mage::getStoreConfig('general/locale/timezone', $storeId);
            $json['feeds'][$storeId]['system'] = Mage::getStoreConfig('general/locale/timezone', $storeId);
            $json['feeds'][$storeId]['extension'] = 'Magmodules_Sooqr';
            $json['feeds'][$storeId]['extension_version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Sooqr->version;
        }

        return $json;
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
            $pages = Mage::getModel('cms/page')->getCollection()->addStoreFilter($config['store_id'])->addFieldToFilter(
                'is_active',
                1
            )->addFieldToFilter('identifier', array(array('nin' => array('no-route', 'enable-cookies'))));
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
                'content'      => Mage::helper('sooqr')->cleanData($page->getContent(), 'striptags'),
                'url'          => $config['website_url'] . $page->getIdentifier()
            );
        }

        return $cmspages;
    }

    /**
     * @param $storeId
     */
    public function setMemoryLimit($storeId)
    {
        if (Mage::getStoreConfig('sooqr_connect/generate/overwrite', $storeId)) {
            if ($memoryLimit = Mage::getStoreConfig('sooqr_connect/generate/memory_limit', $storeId)) {
                ini_set('memory_limit', $memoryLimit);
            }

            if ($maxExecutionTime = Mage::getStoreConfig('sooqr_connect/generate/max_execution_time', $storeId)) {
                ini_set('max_execution_time', $maxExecutionTime);
            }
        }
    }
}