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
class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Frequency
{

    public function toOptionArray()
    {
        $frequency = array();
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Daily'), 'value' => '0');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every 6 hours'), 'value' => '6');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every 4 hours'), 'value' => '4');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every 2 hours'), 'value' => '2');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every hour'), 'value' => '1');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every 30 minutes'), 'value' => '30');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Every 15 minutes'), 'value' => '15');
        $frequency[] = array('label' => Mage::helper('adminhtml')->__('Custom'), 'value' => 'custom_expr');
        return $frequency;
    }

}