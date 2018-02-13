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

require_once 'abstract.php';

class Sooqr_Shell_GenerateFeed extends Mage_Shell_Abstract
{

    const XPATH_ENABLED = 'sooqr_connect/generate/enabled';
    const XPATH_RESULT = 'sooqr_connect/generate/feed_result';

    /**
     *
     */
    public function run()
    {
        if ($generate = $this->getArg('generate')) {
            $storeIds = $this->getStoreIds($generate);
            foreach ($storeIds as $storeId) {
                $timeStart = microtime(true);
                $feed = Mage::getModel('sooqr/sooqr')->generateFeed($storeId, 'cli');
                echo $this->getResults($storeId, $feed, $timeStart) . PHP_EOL;
            }
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Returns all available storeIds for feed generation.
     *
     * @param $generate
     *
     * @return array
     */
    public function getStoreIds($generate)
    {
        $allStores = Mage::helper('sooqr')->getStoreIds(self::XPATH_ENABLED);
        if ($generate == 'next') {
            $nextStore = Mage::helper('sooqr')->getUncachedConfigValue('sooqr_connect/generate/cron_next');
            if (empty($nextStore) || ($nextStore >= count($allStores))) {
                $nextStore = 0;
            }
            Mage::getModel('core/config')->saveConfig('sooqr_connect/generate/cron_next', ($nextStore + 1), 'default', 0);
            return array($allStores[$nextStore]);
        }

        if ($generate == 'all') {
            return $allStores;
        }

        return explode(',', trim($generate));
    }

    /**
     * Parse and saves result.
     *
     * @param $storeId
     * @param $result
     * @param $timeStart
     *
     * @return string
     */
    public function getResults($storeId, $result, $timeStart)
    {
        if (!empty($result)) {
            $html = sprintf(
                '<a href="%s" target="_blank">%s</a><br/><small>On: %s (cli) - Products: %s/%s - Time: %s</small>',
                $result['url'],
                $result['url'],
                $result['date'],
                $result['qty'],
                $result['pages'],
                Mage::helper('sooqr')->getTimeUsage($timeStart)
            );
            Mage::getModel('core/config')->saveConfig(self::XPATH_RESULT, $html, 'stores', $storeId);

            return sprintf(
                'Generated %s - Products: %s/%s - Time: %s',
                $result['url'],
                $result['qty'],
                $result['pages'],
                Mage::helper('sooqr')->getTimeUsage($timeStart)
            );
        } else {
            return 'No feed found, please check storeId or is module is enabled';
        }
    }

    /**
     * Retrieve Usage Help Message.
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f sooqr.php -- [options]
  --generate all      Generate all stores
  --generate next     Generate next available store     
  --generate <id>     Generate store <id> (comma seperated supported)
USAGE;
    }

}

$shell = new Sooqr_Shell_GenerateFeed();
$shell->run();