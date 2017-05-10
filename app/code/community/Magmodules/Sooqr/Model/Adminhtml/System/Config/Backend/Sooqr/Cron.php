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

class Magmodules_Sooqr_Model_Adminhtml_System_Config_Backend_Sooqr_Cron extends Mage_Core_Model_Config_Data
{

    const CRON_MODEL_PATH = 'sooqr_connect/generate/cron_schedule';
    const CRON_STRING_PATH = 'crontab/jobs/sooqr_generate/schedule/cron_expr';
    const CRON_RUNMODEL_PATH = 'crontab/jobs/sooqr_generate/run/model';

    /**
     * @throws Exception
     */
    public function _afterSave()
    {
        $time = $this->getData('groups/generate/fields/time/value');
        $frequency = $this->getData('groups/generate/fields/frequency/value');
        $count = count(Mage::helper('sooqr')->getStoreIds('sooqr_connect/generate/enabled'));
        $cronExprString = '';

        if ($count > 0) {
            switch ($frequency) {
                case 'custom_expr':
                    $cronExprString = $this->getData('groups/generate/fields/custom_cron/value');
                    break;
                case 0:
                    $hours = array();
                    for ($i = 0; $i < $count; $i++) {
                        $hours[] = $i;
                    }

                    $cronExprArray = array('40', implode(',', $hours), '*', '*', '*');
                    break;
                case 6:
                    $cronExprArray = array('40', '*/6', '*', '*', '*');
                    break;
                case 4:
                    $cronExprArray = array('40', '*/4', '*', '*', '*');
                    break;
                case 2:
                    $cronExprArray = array('40', '*/2', '*', '*', '*');
                    break;
                case 1:
                    $cronExprArray = array('40', '*', '*', '*', '*');
                    break;
                case 30:
                    $cronExprArray = array('10,40', '*', '*', '*', '*');
                    break;
                case 15:
                    $cronExprArray = array('0,15,30,45', '*', '*', '*', '*');
                    break;
            }
        }

        if (!empty($cronExprArray)) {
            $cronExprString = join(' ', $cronExprArray);
        }

        try {
            Mage::getModel('core/config_data')
                ->load(self::CRON_MODEL_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_MODEL_PATH)
                ->save();
            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();
            Mage::getModel('core/config_data')
                ->load(self::CRON_RUNMODEL_PATH, 'path')
                ->setValue((string)Mage::getConfig()->getNode(self::CRON_RUNMODEL_PATH))
                ->setPath(self::CRON_RUNMODEL_PATH)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }
    }

}