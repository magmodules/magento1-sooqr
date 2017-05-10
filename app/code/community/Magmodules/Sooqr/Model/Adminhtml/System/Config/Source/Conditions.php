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
class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Conditions
{

    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => '', 'label' => Mage::helper('sooqr')->__(''));
        $type[] = array('value' => 'eq', 'label' => Mage::helper('sooqr')->__('Equal'));
        $type[] = array('value' => 'neq', 'label' => Mage::helper('sooqr')->__('Not equal'));
        $type[] = array('value' => 'gt', 'label' => Mage::helper('sooqr')->__('Greater than'));
        $type[] = array('value' => 'gteq', 'label' => Mage::helper('sooqr')->__('Greater than or equal to'));
        $type[] = array('value' => 'lt', 'label' => Mage::helper('sooqr')->__('Less than'));
        $type[] = array('value' => 'lteg', 'label' => Mage::helper('sooqr')->__('Less than or equal to'));
        $type[] = array('value' => 'in', 'label' => Mage::helper('sooqr')->__('In'));
        $type[] = array('value' => 'nin', 'label' => Mage::helper('sooqr')->__('Not in'));
        $type[] = array('value' => 'like', 'label' => Mage::helper('sooqr')->__('Like'));
        $type[] = array('value' => 'empty', 'label' => Mage::helper('sooqr')->__('Empty'));
        $type[] = array('value' => 'not-empty', 'label' => Mage::helper('sooqr')->__('Not Empty'));
        return $type;
    }

}