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

class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Cacheresize
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
            $storeId = Mage::helper('sooqr')->getStoreIdConfig();
            $source = Mage::getStoreConfig('sooqr_connect/products/image_source', $storeId);
            if ($source) {
                $dir = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . 'cache' . DS . $storeId . DS . $source . DS;
                if (file_exists($dir)) {
                    $dirs = array_filter(glob($dir . '*'), 'is_dir');
                    if (count($dirs)) {
                        foreach ($dirs as $imgOption) {
                            $imgOption = str_replace($dir, '', $imgOption);
                            if (strlen($imgOption) < 8) {
                                $this->options[] = array('value' => $imgOption, 'label' => $imgOption);
                            }
                        }
                    }
                }
            }

            if (empty($options)) {
                $this->options[] = array('value' => '', 'label' => Mage::helper('adminhtml')->__('No cached sizes found'));
            }
        }

        return $this->options;
    }

}