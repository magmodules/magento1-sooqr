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
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Sooqr_Helper_Selftest extends Magmodules_Sooqr_Helper_Data
{

    const SUPPORT_URL = 'https://www.magmodules.eu/help/sooqr/sooqr-selftest-results';
    const GITHUB_URL = 'https://api.github.com/repos/magmodules/magento1-sooqr/tags';

    /**
     *
     */
    public function runTests()
    {
        $result = array();

        /** @var Magmodules_Sooqr_Model_Sooqr $model */
        $model = Mage::getModel("sooqr/sooqr");

        $enabled = Mage::getStoreConfig('sooqr_connect/general/enabled');
        if ($enabled) {
            $result[] = $this->getPass('Module Enabled');
        } else {
            $result[] = $this->getFail('Module Disabled');
        }

        $searchEnabled = Mage::getStoreConfig('sooqr_connect/general/frontend_enabled');
        if ($searchEnabled) {
            $result[] = $this->getPass('Frontend Search Enabled');
        } else {
            $result[] = $this->getFail('Frontend Search not Enabled');
        }

        $accountId = Mage::getStoreConfig('sooqr_connect/general/account_id');
        $apiKey = Mage::getStoreConfig('sooqr_connect/general/api_key');
        if (!empty($accountId) && !empty($apiKey)) {
            $result[] = $this->getPass('Account ID & Api Key Set');
        } else {
            $result[] = $this->getFail('Account ID or Api Key missing');
        }

        $local = $this->checkOldVersion('Sooqr');
        if ($local) {
            $result[] = $this->getNotice('Old version or local overwrite detected', '#local');
        }

        $flatProduct = Mage::getStoreConfig('catalog/frontend/flat_catalog_product');
        $bypassFlat = Mage::getStoreConfig('sooqr_connect/generate/bypass_flat');

        if ($flatProduct) {
            if ($bypassFlat) {
                $result[] = $this->getNotice('Catalog Product Flat bypass is enabled', '#bypass');
            } else {
                $result[] = $this->getPass('Catalog Product Flat is enabled');

                $storeId = $this->getStoreIdConfig();
                $nonFlatAttributes = $this->checkFlatCatalog($model->getFeedAttributes('', $storeId));

                if (!empty($nonFlatAttributes)) {
                    $atts = '<i>' . implode($nonFlatAttributes, ', ') . '</i>';
                    $url = Mage::helper("adminhtml")->getUrl('adminhtml/sooqr/addToFlat');
                    $msg = $this->__('Missing Attribute(s) in Catalog Product Flat: %s', $atts);
                    $msg .= '<br/> ' . $this->__('<a href="%s">Add</a> attributes to Flat Catalog or enable "Bypass Flat Product Tables"',
                            $url);
                    $result[] = $this->getFail($msg, '#missingattributes');
                }
            }
        } else {
            $result[] = $this->getNotice('Catalog Product Flat is disabled', '#flatcatalog');
        }

        $flatCategoy = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');
        if ($flatCategoy) {
            $result[] = $this->getPass('Catalog Catagory Flat is enabled');
        } else {
            $result[] = $this->getNotice('Catalog Catagory Flat is disabled', '#flatcatalog');
        }

        if ($lastRun = $this->checkMagentoCron()) {
            if ((time() - strtotime($lastRun)) > 3600) {
                $msg = $this->__('Magento cron not seen in last hour (last: %s)', $lastRun);
                $result[] = $this->getFail($msg, '#cron');
            } else {
                $msg = $this->__('Magento cron seems to be running (last: %s)', $lastRun);
                $result[] = $this->getPass($msg);
            }
        } else {
            $result[] = $this->getFail('Magento cron not setup', '#cron');
        }

        $latestVersion = $this->latestVersion();
        if (isset($latestVersion['version'])) {
            $modulesArray = (array)Mage::getConfig()->getNode('modules')->children();
            $currentVersion = $modulesArray['Magmodules_Sooqr']->version;
            if (version_compare($currentVersion, $latestVersion['version']) >= 0) {
                $msg = $this->__('Running the latest version (Installed: v%s - Github: v%s)', $currentVersion,
                    $latestVersion['version']);
                $result[] = $this->getPass($msg);
            } else {
                $msg = $this->__('v%s is latest version, currenlty running v%s, please update!',
                    $latestVersion['version'], $currentVersion);
                $result[] = $this->getNotice($msg, '#update');
            }
        } else {
            $result[] = $this->getFail($latestVersion['error'], '#update-error');
        }

        return $result;
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getPass($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'pass', $link);
    }

    /**
     * @param        $msg
     * @param        $type
     * @param string $link
     *
     * @return string
     */
    public function getHtmlResult($msg, $type, $link)
    {
        $format = null;

        if ($type == 'pass') {
            $format = '<span class="sooqr-success">%s</span>';
        }
        if ($type == 'fail') {
            $format = '<span class="sooqr-error">%s</span>';
        }
        if ($type == 'notice') {
            $format = '<span class="sooqr-notice">%s</span>';
        }

        if ($format) {
            if ($link) {
                $format = str_replace('</span>', '<span class="more"><a href="%s">More Info</a></span></span>',
                    $format);
                return sprintf($format, Mage::helper('sooqr')->__($msg), self::SUPPORT_URL . $link);
            } else {
                return sprintf($format, Mage::helper('sooqr')->__($msg));
            }
        }
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getFail($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'fail', $link);
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getNotice($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'notice', $link);
    }

    /**
     * @return array
     */
    public function latestVersion()
    {
        $version = null;
        $error = null;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::GITHUB_URL);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Version Check Magento 1');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $data = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode >= 200 && $httpcode < 300) {
                $data = json_decode($data, true);
                if (isset($data[0]['name'])) {
                    $version = str_replace(array('v.', 'v'), '', $data[0]['name']);
                }
            }
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            return array('error' => $this->__('Could not fetch latest version from Github, error: %s', $error));
        }

        if ($version) {
            return array('version' => $version);
        } else {
            return array('error' => $this->__('Could not fetch latest version from Github'));
        }
    }

    /**
     *
     */
    public function checkMagentoCron()
    {
        $tasks = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToSelect('finished_at')
            ->addFieldToFilter('status', 'success');

        $tasks->getSelect()
            ->limit(1)
            ->order('finished_at DESC');

        return $tasks->getFirstItem()->getFinishedAt();
    }
}
