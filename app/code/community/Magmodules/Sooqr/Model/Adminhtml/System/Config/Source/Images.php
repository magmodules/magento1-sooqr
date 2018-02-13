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
class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Images
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('frontend_input', 'media_image');

            $this->options[] = array(
                'value' => '',
                'label' => Mage::helper('sooqr')->__('Use default')
            );

            foreach ($attributes as $attribute) {
                $this->options[] = array(
                    'value' => $attribute->getData('attribute_code'),
                    'label' => str_replace("'", "", $attribute->getData('frontend_label'))
                );
            }
        }

        return $this->options;
    }

}