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

class Magmodules_Sooqr_Block_Search extends Mage_Core_Block_Template
{

    const SEARCH_FIELD_ID = 'search';

    /**
     *
     */
    public function _construct()
    {
        if ($this->isEnabled()) {
            $this->setTemplate('magmodules/sooqr/form.mini.phtml');
        } else {
            $this->setTemplate('catalogsearch/form.mini.phtml');
        }

        parent::_construct();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $enabled = Mage::getStoreConfig('sooqr_connect/general/enabled', 0);
        $frontendEnabled = Mage::getStoreConfig('sooqr_connect/general/frontend_enabled');
        $accountId = Mage::getStoreConfig('sooqr_connect/general/account_id');
        $apiKey = Mage::getStoreConfig('sooqr_connect/general/api_key');
        if ($enabled && $frontendEnabled && (!empty($accountId)) && (!empty($apiKey))) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function multiSearch()
    {
        $multi = Mage::getStoreConfig('sooqr_connect/general/extra_search', 0);
        if (!empty($multi)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getSooqrOptions($type = '')
    {
        $options = array();
        $accountId = Mage::getStoreConfig('sooqr_connect/general/account_id');

        if ($type == 'second') {
            $options['fieldId'] = Mage::getStoreConfig('sooqr_connect/general/extra_search');
            $options['namespace'] = 'suggest2';
            $options['account'] = substr($accountId, 0, -1) . '2';
        } else {
            $options['account'] = $accountId;
            $options['fieldId'] = self::SEARCH_FIELD_ID;
        }

        $parent = Mage::getStoreConfig('sooqr_connect/general/parent');
        if (!empty($parent)) {
            $options['containerParent'] = $parent;
        }

        $version = Mage::getStoreConfig('sooqr_connect/general/frontend_version');
        if (!empty($version)) {
            $options['version'] = $version;
        }

        return $options;
    }

    /**
     * @return string
     */
    public function getSooqrLanguage()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }

    /**
     * @return mixed
     */
    public function getSooqrJavascript()
    {
        $customJs = Mage::getStoreConfig('sooqr_connect/general/custom_js');
        if (!empty($customJs)) {
            return $customJs;
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isTrackingEnabled()
    {
        if (Mage::getStoreConfig('sooqr_connect/general/statistics')) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSooqrScriptUri()
    {
        if (Mage::getStoreConfig('sooqr_connect/general/staging')) {
            return 'static.staging.sooqr.com/sooqr.js';
        }

        return 'static.sooqr.com/sooqr.js';
    }

}