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

class Magmodules_Sooqr_Adminhtml_SooqrController extends Mage_Adminhtml_Controller_Action
{

    const XPATH_RESULT = 'sooqr_connect/generate/feed_result';
    const XPATH_BYPASSFLAT = 'sooqr_connect/generate/bypass_flat';

    /**
     * @var Magmodules_Sooqr_Helper_Data
     */
    public $helper;
    /**
     * @var Magmodules_Sooqr_Helper_Selftest
     */
    public $selftest;
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
    public function _construct()
    {
        $this->helper = Mage::helper('sooqr');
        $this->selftest = Mage::helper('sooqr/selftest');
        $this->config = Mage::getModel('core/config');
        $this->feed = Mage::getModel('sooqr/sooqr');
    }

    /**
     *
     */
    public function generateManualAction()
    {
        try {
            if (Mage::getStoreConfig('sooqr_connect/general/enabled')) {
                $storeId = $this->getRequest()->getParam('store_id');
                if (!empty($storeId)) {
                    $timeStart = microtime(true);
                    /** @var Mage_Core_Model_App_Emulation $appEmulation */
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                    if ($result = $this->feed->generateFeed($storeId)) {
                        $this->feed->updateConfig($result, 'manual', $timeStart, $storeId);
                        $downloadUrl = $this->getUrl('*/sooqr/download/store_id/' . $storeId);
                        $msg = $this->helper->__(
                            'Generated feed with %s products. %s',
                            $result['qty'],
                            '<a style="float:right;" href="' . $downloadUrl . '">Download XML</a>'
                        );
                        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
                    } else {
                        $this->config->saveConfig(self::XPATH_RESULT, '', 'stores', $storeId);
                        $msg = $this->helper->__('No products found, make sure your filters are configured with existing values.');
                        Mage::getSingleton('adminhtml/session')->addError($msg);
                    }

                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }
            } else {
                $msg = $this->helper->__('Please enable the extension before generating the xml');
                Mage::getSingleton('adminhtml/session')->addError($msg);
            }
        } catch (\Exception $e) {
            $this->helper->addToLog('generateManualAction', $e->getMessage());
            if (strpos($e->getMessage(), 'SQLSTATE[42S22]') !== false) {
                $msg = $this->helper->__(
                    'SQLSTATE[42S22]: Column not found, please go to %s and rebuild required indexes.',
                    '<a href="' . $this->getUrl('adminhtml/process/list') . '">Index Management</a>'
                );
                Mage::getSingleton('adminhtml/session')->addError($msg);
            } else {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     *
     */
    public function previewAction()
    {
        try {
            if (Mage::getStoreConfig('sooqr_connect/general/enabled')) {
                $storeId = $this->getRequest()->getParam('store_id');

                if (!empty($storeId)) {
                    /** @var Mage_Core_Model_App_Emulation $appEmulation */
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                    $this->feed->generateFeed($storeId, 'preview');
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

                    $filePath = '';
                    if ($fileName = $this->feed->getFileName('sooqr_connect', $storeId, 'preview')) {
                        $filePath = Mage::getBaseDir() . DS . 'media' . DS . 'sooqr' . DS . $fileName;
                    }

                    if (!empty($filePath) && file_exists($filePath)) {
                        $this->getResponse()
                            ->setHttpResponseCode(200)
                            ->setHeader(
                                'Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                                true
                            )
                            ->setHeader('Pragma', 'no-cache', 1)
                            ->setHeader('Content-type', 'application/force-download')
                            ->setHeader('Content-Length', filesize($filePath))
                            ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filePath));
                        $this->getResponse()->clearBody();
                        $this->getResponse()->sendHeaders();
                        readfile($filePath);
                    } else {
                        $msg = $this->helper->__('Error creating preview XML');
                        Mage::getSingleton('adminhtml/session')->addError($msg);
                        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
                    }
                }
            } else {
                $msg = $this->helper->__('Please enable the extension before generating the xml');
                Mage::getSingleton('adminhtml/session')->addError($msg);
                $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
            }
        } catch (\Exception $e) {
            $this->helper->addToLog('previewAction', $e->getMessage());
            if (strpos($e->getMessage(), 'SQLSTATE[42S22]') !== false) {
                $msg = $this->helper->__(
                    'SQLSTATE[42S22]: Column not found, please go to %s and rebuild required indexes.',
                    '<a href="' . $this->getUrl('adminhtml/process/list') . '">Index Management</a>'
                );
                Mage::getSingleton('adminhtml/session')->addError($msg);
            } else {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }

            $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
        }
    }

    /**
     *
     */
    public function addToFlatAction()
    {
        try {
            $nonFlatAttributes = $this->helper->checkFlatCatalog($this->feed->getFeedAttributes());
            foreach ($nonFlatAttributes as $key => $value) {
                Mage::getModel('catalog/resource_eav_attribute')->load($key)->setUsedInProductListing(1)->save();
            }

            $msg = $this->helper->__('Attributes added to Flat Catalog, please reindex Product Flat Data.');
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        } catch (\Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     *
     */
    public function bypassFlatAction()
    {
        $this->config->saveConfig(self::XPATH_BYPASSFLAT, 1, 'default', 0);
        $msg = $this->helper->__('Settings saved!');
        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
    }

    /**
     *
     */
    public function downloadAction()
    {
        try {
            $filePath = '';
            $storeId = $this->getRequest()->getParam('store_id');
            if ($fileName = $this->feed->getFileName('sooqr_connect', $storeId)) {
                $filePath = Mage::getBaseDir() . DS . 'media' . DS . 'sooqr' . DS . $fileName;
            }

            if (!empty($filePath) && file_exists($filePath)) {
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
        } catch (\Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/system_config/edit/section/sooqr_connect');
        }
    }

    /**
     *
     */
    public function selftestAction()
    {
        $results = $this->selftest->runTests();
        $msg = implode('<br/>', $results);
        Mage::app()->getResponse()->setBody($msg);
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/sooqr_connect');
    }

}