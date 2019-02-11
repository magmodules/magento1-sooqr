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
 * @copyright     Copyright (c) 2019 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Frequency
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
            $this->options = array(
                array('label' => Mage::helper('sooqr')->__('Daily'), 'value' => '0'),
                array('label' => Mage::helper('sooqr')->__('Every 6 hours'), 'value' => '6'),
                array('label' => Mage::helper('sooqr')->__('Every 4 hours'), 'value' => '4'),
                array('label' => Mage::helper('sooqr')->__('Every 2 hours'), 'value' => '2'),
                array('label' => Mage::helper('sooqr')->__('Every hour'), 'value' => '1'),
                array('label' => Mage::helper('sooqr')->__('Every 30 minutes'), 'value' => '30'),
                array('label' => Mage::helper('sooqr')->__('Every 15 minutes'), 'value' => '15'),
                array('label' => Mage::helper('sooqr')->__('Custom'), 'value' => 'custom_expr')
            );
        }

        return $this->options;
    }

}