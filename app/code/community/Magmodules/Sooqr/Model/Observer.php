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

class Magmodules_Sooqr_Model_Observer
{

    /**
     * @param $schedule
     */
    public function scheduledGenerateSooqr($schedule)
    {
        $enabled = Mage::getStoreConfig('sooqr_connect/general/enabled');
        $cron = Mage::getStoreConfig('sooqr_connect/generate/cron');
        $nextStore = Mage::helper('sooqr')->getUncachedConfigValue('sooqr_connect/generate/cron_next');
        $storeIds = Mage::helper('sooqr')->getStoreIds('sooqr_connect/generate/enabled');
        $nrStores = count($storeIds);

        if ($enabled && $cron && ($nrStores > 0)) {
            if (empty($nextStore) || ($nextStore >= $nrStores)) {
                $nextStore = 0;
            }

            $storeId = $storeIds[$nextStore];
            $timeStart = microtime(true);
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            $config = Mage::getModel('core/config');
            if ($result = Mage::getModel('sooqr/sooqr')->generateFeed($storeId, $timeStart)) {
                $html = '<a href="' . $result['url'] . '" target="_blank">' . $result['url'] . '</a>
                <br/>
                <small>
                    Date: ' . $result['date'] . ' (cron) - 
                    Products: ' . $result['qty'] . ' - 
                    Time: ' . number_format((microtime(true) - $timeStart), 4) . '
                </small>';
                $config->saveConfig('sooqr_connect/generate/feed_result', $html, 'stores', $storeId);
            }

            $config->saveConfig('sooqr_connect/generate/cron_next', ($nextStore + 1), 'default', 0);
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }

}