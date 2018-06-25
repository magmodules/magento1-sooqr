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
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Sooqr_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        $storeId = $this->getStoreId();
        if (Mage::getStoreConfig('sooqr_connect/generate/bypass_flat', $storeId)) {
            return false;
        }

        if (!isset($this->_flatEnabled[$storeId])) {
            if (version_compare(Mage::getVersion(), '1.8', '>=')) {
                $flatHelper = $this->getFlatHelper();
                $this->_flatEnabled[$storeId] = $flatHelper->isAvailable() && $flatHelper->isBuilt($storeId);
            } else {
                $this->_flatEnabled[$storeId] = $this->getFlatHelper()->isEnabled($storeId);
            }
        }

        return $this->_flatEnabled[$storeId];
    }
}
