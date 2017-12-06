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
     * @var Magmodules_Sooqr_Helper_Data
     */
    public $helper;

    /**
     * @var Mage_Core_Model_Config
     */
    public $config;

    /**
     * @var Magmodules_Sooqr_Model_Sooqr
     */
    public $feed;

    /**
     * Magmodules_Sooqr_Model_Sooqr constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('sooqr');
        $this->config = Mage::getModel('core/config');
        $this->feed = Mage::getModel('sooqr/sooqr');
    }

    /**
     *
     */
    public function scheduledGenerateSooqr()
    {
        $storeIds = $this->helper->getStoreIds('sooqr_connect/generate/enabled');
        $cron = Mage::getStoreConfig('sooqr_connect/generate/cron');

        if ($cron & count($storeIds)) {
            $nextStore = $this->helper->getUncachedConfigValue('sooqr_connect/generate/cron_next');
            if (empty($nextStore) || ($nextStore >= count($storeIds))) {
                $nextStore = 0;
            }

            $storeId = $storeIds[$nextStore];
            $timeStart = microtime(true);

            /** @var Mage_Core_Model_App_Emulation $appEmulation */
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

            if ($result = $this->feed->generateFeed($storeId)) {
                $html = sprintf(
                    '<a href="%s" target="_blank">%s</a><br/><small>On: %s (cron) - Products: %s/%s - Time: %s</small>',
                    $result['url'],
                    $result['url'],
                    $result['date'],
                    $result['qty'],
                    $result['pages'],
                    $this->helper->getTimeUsage($timeStart)
                );
                $this->config->saveConfig('sooqr_connect/generate/feed_result', $html, 'stores', $storeId);
            }

            $this->config->saveConfig('sooqr_connect/generate/cron_next', ($nextStore + 1), 'default', 0);
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }
}