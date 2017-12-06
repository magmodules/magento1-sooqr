<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Sooqr
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Sooqr_Model_Common extends Mage_Core_Helper_Abstract
{

    /**
     * @param        $config
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProducts($config)
    {
        $storeId = $config['store_id'];
        $limit = $config['limit'];

        if (!empty($config['bypass_flat'])) {
            $collection = Mage::getModel('sooqr/resource_product_collection');
        } else {
            $collection = Mage::getResourceModel('catalog/product_collection');
        }

        $collection->setStore($storeId)
            ->addStoreFilter($storeId)
            ->addFinalPrice()
            ->addUrlRewrite()
            ->addAttributeToFilter('status', 1);

        if ($limit > 0) {
            $collection->setPageSize($limit);
        }

        if (!empty($config['filter_status'])) {
            $visibility = $config['filter_status'];
            if (strlen($visibility) > 1) {
                $visibility = explode(',', $visibility);
                if ($config['conf_enabled']) {
                    $visibility[] = '1';
                }

                $collection->addAttributeToFilter('visibility', array('in' => array($visibility)));
            } else {
                if (!empty($config['conf_enabled'])) {
                    $visibility = '1,' . $visibility;
                    $visibility = explode(',', $visibility);
                    $collection->addAttributeToFilter('visibility', array('in' => array($visibility)));
                } else {
                    $collection->addAttributeToFilter('visibility', array('eq' => array($visibility)));
                }
            }
        }

        if (!empty($config['filter_enabled'])) {
            $type = $config['filter_type'];
            $categories = $config['filter_cat'];
            if ($type && $categories) {
                $table = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
                if ($type == 'include') {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id in (' . $categories . ')');
                } else {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id not in (' . $categories . ')');
                }
            }
        }

        $attributes = $this->getDefaultAttributes();

        if (!empty($config['filter_exclude'])) {
            $attributes[] = $config['filter_exclude'];
        }

        foreach ($config['field'] as $field) {
            if (!empty($field['source'])) {
                $attributes[] = $field['source'];
            }
        }

        if (!empty($config['delivery_att'])) {
            $attributes[] = $config['delivery_att'];
        }

        if (!empty($config['delivery_att_be'])) {
            $attributes[] = $config['delivery_att_be'];
        }

        $customValues = '';
        if (isset($config['custom_name'])) {
            $customValues .= $config['custom_name'] . ' ';
        }

        if (isset($config['custom_description'])) {
            $customValues .= $config['custom_description'] . ' ';
        }

        preg_match_all("/{{([^}]*)}}/", $customValues, $foundAtts);
        if (!empty($foundAtts)) {
            foreach ($foundAtts[1] as $att) {
                $attributes[] = $att;
            }
        }

        if (!empty($config['extra_attributes'])) {
            foreach ($config['extra_attributes'] as $att) {
                if (!empty($att['source'])) {
                    $attributes[] = $att['source'];
                }
            }
        }

        if (!empty($config['size_multiple'])) {
            $sizeMultiple = explode(',', $config['size_multiple']);
            foreach ($sizeMultiple as $att) {
                if (!empty($att)) {
                    $attributes[] = $att;
                }
            }
        }

        if (!empty($config['size_na_multiple'])) {
            $sizeMultiple = explode(',', $config['size_na_multiple']);
            foreach ($sizeMultiple as $att) {
                if (!empty($att)) {
                    $attributes[] = $att;
                }
            }
        }

        $collection->addAttributeToSelect(array_unique($attributes));

        $collection->joinTable(
            'cataloginventory/stock_item', 'product_id=entity_id', array(
                'qty'                       => 'qty',
                'is_in_stock'               => 'is_in_stock',
                'manage_stock'              => 'manage_stock',
                'use_config_manage_stock'   => 'use_config_manage_stock',
                'min_sale_qty'              => 'min_sale_qty',
                'qty_increments'            => 'qty_increments',
                'enable_qty_increments'     => 'enable_qty_increments',
                'use_config_qty_increments' => 'use_config_qty_increments',
                'backorders'                => 'backorders',
                'use_config_backorders'     => 'use_config_backorders',
            )
        )->addAttributeToSelect(
            array(
                'qty',
                'is_in_stock',
                'manage_stock',
                'use_config_manage_stock',
                'min_sale_qty',
                'qty_increments',
                'enable_qty_increments',
                'use_config_qty_increments',
                'backorders',
                'use_config_backorders'
            )
        );

        if (!empty($config['hide_no_stock'])) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        $collection->getSelect()->group('e.entity_id');

        if (!empty($config['filters'])) {
            $collection = $this->addFilters($config['filters'], $collection);
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function getDefaultAttributes()
    {
        $attributes = array();
        $attributes[] = 'url_key';
        $attributes[] = 'url_path';
        $attributes[] = 'sku';
        $attributes[] = 'price';
        $attributes[] = 'final_price';
        $attributes[] = 'price_model';
        $attributes[] = 'price_type';
        $attributes[] = 'special_price';
        $attributes[] = 'special_from_date';
        $attributes[] = 'special_to_date';
        $attributes[] = 'type_id';
        $attributes[] = 'tax_class_id';
        $attributes[] = 'tax_percent';
        $attributes[] = 'weight';
        $attributes[] = 'visibility';
        $attributes[] = 'type_id';
        $attributes[] = 'image';
        $attributes[] = 'small_image';
        $attributes[] = 'thumbnail';
        $attributes[] = 'status';

        return $attributes;
    }

    /**
     * @param                                                $filters
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addFilters($filters, $collection)
    {
        $cType = array(
            'eq'   => '=',
            'neq'  => '!=',
            'gt'   => '>',
            'gteq' => '>=',
            'lt'   => '<',
            'lteg' => '<='
        );

        foreach ($filters as $filter) {
            $attribute = $filter['attribute'];
            if ($filter['type'] == 'select') {
                $attribute = $filter['attribute'] . '_value';
            }

            $condition = $filter['condition'];
            $value = $filter['value'];

            if ($attribute == 'final_price') {
                if (isset($cType[$condition])) {
                    $collection->getSelect()->where('price_index.final_price ' . $cType[$condition] . ' ' . $value);
                }

                continue;
            }

            if ($attribute == 'min_sale_qty') {
                if (isset($cType[$condition])) {
                    $collection->getSelect()->where('cataloginventory_stock_item.min_sale_qty ' . $cType[$condition] . ' ' . $value);
                }

                continue;
            }

            switch ($condition) {
                case 'nin':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $collection->addAttributeToFilter(
                        array(
                            array(
                                'attribute' => $attribute,
                                $condition  => $value
                            ),
                            array('attribute' => $attribute, 'null' => true)
                        )
                    );
                    break;
                case 'in';
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $collection->addAttributeToFilter($attribute, array($condition => $value));
                    break;
                case 'neq':
                    $collection->addAttributeToFilter(
                        array(
                            array('attribute' => $attribute, $condition => $value),
                            array('attribute' => $attribute, 'null' => true)
                        )
                    );
                    break;
                case 'empty':
                    $collection->addAttributeToFilter($attribute, array('null' => true));
                    break;
                case 'not-empty':
                    $collection->addAttributeToFilter($attribute, array('notnull' => true));
                    break;
                default:
                    $collection->addAttributeToFilter($attribute, array($condition => $value));
                    break;
            }
        }

        return $collection;
    }

    /**
     * @param $atts
     *
     * @return array
     */
    public function getParentAttributeSelection($atts)
    {
        $attributes = $this->getDefaultAttributes();
        foreach ($atts as $attribute) {
            if (!empty($attribute['parent'])) {
                if (!empty($attribute['source'])) {
                    if ($attribute['source'] != 'entity_id') {
                        $attributes[] = $attribute['source'];
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * @param array $parentRelations
     * @param array $config
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getParents($parentRelations, $config)
    {

        if (!empty($config['conf_enabled']) && !empty($parentRelations)) {
            if (!empty($config['bypass_flat'])) {
                $collection = Mage::getModel('sooqr/resource_product_collection');
            } else {
                $collection = Mage::getResourceModel('catalog/product_collection');
            }

            $collection->setStore($config['store_id'])
                ->addStoreFilter($config['store_id'])
                ->addFinalPrice()
                ->addUrlRewrite()
                ->addAttributeToFilter('entity_id', array('in' => array_values($parentRelations)))
                ->addAttributeToSelect(array_unique($config['parent_att']))
                ->addAttributeToFilter('status', 1);

            if (!empty($config['hide_no_stock'])) {
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
            }

            return $collection->load();
        }
    }
}