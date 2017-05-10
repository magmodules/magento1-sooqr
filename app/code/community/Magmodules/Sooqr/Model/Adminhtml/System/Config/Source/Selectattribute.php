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
class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Selectattribute
{

    protected $_ignore = array(
        'ebizmarts_mark_visited',
        'is_recurring',
        'links_purchased_separately',
        'price_view',
        'status',
        'tax_class_id',
        'visibility',
        'sooqr_condition',
        'sooqr_exclude',
        'shipment_type',
    );

    public function toOptionArray()
    {
        $options = array();
        $options[] = array('value' => '', 'label' => Mage::helper('sooqr')->__('-- none'));
        $entityTypeId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getEntityTypeId();
        $attributes = Mage::getModel('eav/entity_attribute')->getCollection()->addFilter(
            'entity_type_id',
            $entityTypeId
        )->setOrder('attribute_code', 'ASC');
        foreach ($attributes as $attribute) {
            if ($attribute->getBackendType() == 'int') {
                if ($attribute->getFrontendLabel()) {
                    if (!in_array($attribute->getAttributeCode(), $this->_ignore)) {
                        $options[] = array(
                            'value' => $attribute->getAttributeCode(),
                            'label' => $attribute->getFrontendLabel()
                        );
                    }
                }
            }
        }

        return $options;
    }

}