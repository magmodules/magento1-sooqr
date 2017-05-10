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

class Magmodules_Sooqr_Adminhtml_SooqrController extends Mage_Adminhtml_Controller_Action
{

    public function generateFeedAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $config = Mage::getModel('core/config');

        if (!empty($storeId)) {
            $timeStart = microtime(true);
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            if ($result = Mage::getModel('sooqr/sooqr')->generateFeed($storeId, $timeStart)) {
                $html = '<a href="' . $result['url'] . '" target="_blank">' . $result['url'] . '</a>
                <br/>
                <small>
                    Date: ' . $result['date'] . ' (manual) - 
                    Products: ' . $result['qty'] . ' - 
                    Time: ' . number_format((microtime(true) - $timeStart), 4) . '
                </small>';
                $config->saveConfig('sooqr_connect/generate/feed_result', $html, 'stores', $storeId);
                $url = $this->getUrl('*/sooqr/download/store_id/' . $storeId);
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('sooqr')->__(
                        'Generated feed with %s products. %s',
                        $result['qty'],
                        '<a  style="float:right;" href="' . $url . '">Download XML</a>'
                    )
                );
                $limit = Mage::getStoreConfig('sooqr_connect/generate/limit', $storeId);
                if ($limit > 0) {
                    Mage::getSingleton('adminhtml/session')->addNotice(
                        Mage::helper('sooqr')->__(
                            'Note, in the feed generate configuration tab you have enabled the product limit of %s.',
                            $limit
                        )
                    );
                }
            } else {
                $config->saveConfig('sooqr_connect/generate/feed_result', '', 'stores', $storeId);
                $msg = $this->__('No products found, make sure your filters are configured with existing values.');
                Mage::getSingleton('adminhtml/session')->addError($msg);
            }

            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }

        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    public function downloadAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if ($fileName = Mage::getModel('sooqr/sooqr')->getFileName('sooqr', $storeId, 0)) {
            $filePath = Mage::getBaseDir() . DS . 'media' . DS . 'sooqr' . DS . $fileName;
        }

        if (file_exists($filePath)) {
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Pragma', 'no-cache', 1)
                ->setHeader('Content-type', 'application/force-download')
                ->setHeader('Content-Length', filesize($filePath))
                ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filePath));
            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();
            readfile($filePath);
        }
    }

    /**
     *
     */
    public function addToFlatAction()
    {
        $storeIds = Mage::helper('sooqr')->getStoreIds('sooqr_connect/generate/enabled');
        foreach ($storeIds as $storeId) {
            $nonFlatAttributes = Mage::helper('sooqr')->checkFlatCatalog(
                Mage::getModel("sooqr/sooqr")->getFeedAttributes(
                    $storeId,
                    'flatcheck'
                )
            );
            foreach ($nonFlatAttributes as $key => $value) {
                Mage::getModel('catalog/resource_eav_attribute')->load($key)
                    ->setUsedInProductListing(1)
                    ->save();
            }
        }

        $msg = $this->__('Attributes added to Flat Catalog, please reindex Product Flat Data.');
        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     *
     */
    public function disableFeedAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if ($storeId > 0) {
            $config = Mage::getModel('core/config');
            $config->saveConfig('sooqr_connect/generate/enabled', '', 'stores', $storeId);
        }

        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     *
     */
    public function enableFeedAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if ($storeId > 0) {
            $config = Mage::getModel('core/config');
            $config->saveConfig('sooqr_connect/generate/enabled', 1, 'stores', $storeId);
        }

        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     * @return mixed
     */
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/sooqr_connect');
    }

}