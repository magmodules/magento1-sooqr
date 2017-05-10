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
class Magmodules_Sooqr_Model_Adminhtml_System_Config_Source_Cmspages
{

    public function toOptionArray()
    {
        $storeId = '';
        $cms = array();
        $code = Mage::app()->getRequest()->getParam('store');
        if (!empty($code)) {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        } else {
            $code = Mage::app()->getRequest()->getParam('website');
            if (!empty($code)) {
                $websiteId = Mage::getModel('core/website')->load($code)->getId();
                $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
            }
        }

        if ($storeId) {
            $cmspages = Mage::getModel('cms/page')->getCollection()->addStoreFilter($storeId);
        } else {
            $cmspages = Mage::getModel('cms/page')->getCollection();
        }

        foreach ($cmspages as $page) {
            $cms[] = array(
                'value' => $page->getId(),
                'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
            );
        }

        return $cms;
    }

}