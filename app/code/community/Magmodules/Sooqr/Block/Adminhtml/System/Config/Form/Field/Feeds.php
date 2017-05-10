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
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Sooqr_Block_Adminhtml_System_Config_Form_Field_Feeds
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('sooqr');
        $storeIds = $helper->getStoreIds('sooqr_connect/generate/enabled');
        $htmlFeedlinks = '';

        foreach ($storeIds as $storeId) {
            $generateUrl = $this->getUrl('*/sooqr/generateFeed/store_id/' . $storeId);
            $downloadUrl = $this->getUrl('*/sooqr/download/store_id/' . $storeId);
            $disableUrl = $this->getUrl('*/sooqr/disableFeed/store_id/' . $storeId);
            $feedText = $helper->getUncachedConfigValue('sooqr_connect/generate/feed_result', $storeId);

            if (empty($feedText)) {
                $feedText = $helper->__('No active feed found');
                $downloadUrl = '';
            }

            $htmlFeedlinks .= '<tr>
              <td>
                ' . $helper->__('✓ Enabled') . '
              </td>
              <td valign="top">
                ' . Mage::app()->getStore($storeId)->getName() . '<br/>
                <small>Code: ' . Mage::app()->getStore($storeId)->getCode() . '</small>
              </td>
              <td>
                ' . $feedText . '
              </td>
              <td style="line-height: 25px;">
                <a style="text-decoration: none;padding-left: 4px;" href="' . $generateUrl . '">
                  ' . $helper->__('↺ Generate') . '
                </a>
                <br/>
                <a style="text-decoration: none;padding-left: 3px;" href="' . $downloadUrl . '">
                  ' . $helper->__('➞ Download') . '
                </a>
                <br/>
                <a style="text-decoration: none; padding: 5px;" href="' . $disableUrl . '">
                  ' . $helper->__('✕ Disable') . '</a>
                </td>
            </tr>';
        }

        $storeIds = $helper->getDisabledStoreIds('sooqr_connect/generate/enabled');

        foreach ($storeIds as $storeId) {
            $enableUrl = $this->getUrl('*/sooqr/enableFeed/store_id/' . $storeId);
            $feedText = $helper->__('No active feed found');
            $htmlFeedlinks .= '<tr>
              <td>
                ' . $helper->__('✕ Disabled') . '
              </td>
              <td valign="top">
                ' . Mage::app()->getStore($storeId)->getName() . '
                <br/>
                <small>Code: ' . Mage::app()->getStore($storeId)->getCode() . '</small>
              </td>
              <td>
                ' . $feedText . '
              </td>
              <td>
                <a style="text-decoration: none;padding-left: 5px;" href="' . $enableUrl . '">
                  ' . $helper->__('✓ Enable') . '
                </a>
              </td>
            </tr>';
        }

        if (empty($htmlFeedlinks)) {
            $htmlFeedlinks = $helper->__('No enabled feed(s) found');
        } else {
            $htmlHeader = '<div class="grid">
             <table cellpadding="0" cellspacing="0" class="border" style="width: 100%">
              <tbody>
               <tr class="headings">
                <th>' . $helper->__('Status') . '</th>
                <th>' . $helper->__('Storeview') . '</th>
                <th>' . $helper->__('Feed') . '</th>
                <th>' . $helper->__('Action') . '</th>
               </tr>';
            $htmlFooter = '</tbody></table></div>';
            $htmlFeedlinks = $htmlHeader . $htmlFeedlinks . $htmlFooter;
        }

        return sprintf(
            '<tr id="row_%s"><td colspan="7" class="label" style="margin-bottom: 10px;">%s</td></tr>',
            $element->getHtmlId(),
            $htmlFeedlinks
        );
    }

}
