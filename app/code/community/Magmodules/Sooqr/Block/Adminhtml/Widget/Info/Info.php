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

class Magmodules_Sooqr_Block_Adminhtml_Widget_Info_Info
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $accountId = Mage::getStoreConfig('sooqr_connect/general/account_id');
        $apiKey = Mage::getStoreConfig('sooqr_connect/general/api_key');
        $magentoVersion = Mage::getVersion();
        $module_version = Mage::getConfig()->getNode()->modules->Magmodules_Sooqr->version;
        $logoLink = '//www.magmodules.eu/logo/sooqr/' . $module_version . '/' . $magentoVersion . '/logo.png';
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        $html = '<div style="background:url(\'' . $logoLink . '\') no-repeat scroll 15px center #EAF0EE;border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 200px;">
					<h4>About Magmodules.eu</h4>
					<p>We are a Magento only E-commerce Agency located in the Netherlands and we developed this extension in association with Sooqr.<br>
                    <br />
                    <table width="500px" border="0">
						<tr>
							<td width="58%">More Extensions from Magmodules:</td>
							<td width="42%"><a href="http://www.magentocommerce.com/magento-connect/developer/Magmodules" target="_blank">Magento Connect</a></td>
						</tr>
						<tr>
							<td>For Help:</td>
							<td><a href="https://www.magmodules.eu/support.html?ext=sooqr">Visit our Support Page</a></td>
						</tr>
						<tr>
							<td height="30">Visit our Website:</td>
							<td><a href="http://www.magmodules.eu" target="_blank">www.Magmodules.eu</a></td>
						</tr>';

        if (empty($accountId) && empty($apiKey)) {
            $html .= '	<tr>
							<td>Registration on Sooqr (and free trial):</td>
							<td><a href="https://my.sooqr.com/magtrial?base=' . $baseUrl . '" target="_blank">Register here</a></td>
						</tr>';
        } else {
            $html .= '  <tr>
							<td>Sooqr Conversion Suite</td>
							<td><a href="https://my.sooqr.com/user/login" target="_blank">Login here</a></td>
						</tr>';
        }

        $html .= '		<tr>
							<td height="30">Sooqr Support</td>
							<td><a href="http://support.sooqr.com/support/home" target="_blank">Sooqr Support</a> or <a href="mailto:support@sooqr.com" target="_blank">support@sooqr.com</a></td>
						</tr>
						<tr>
							<td height="30"><strong>Read everything about the extension configuration in our <a href="http://www.magmodules.eu/help/sooqr" target="_blank">Knowledgebase.</a></strong></td>
							<td>&nbsp;</td>
						</tr>
					</table>
                </div>';

        $flatProduct = Mage::getStoreConfig('catalog/frontend/flat_catalog_product');
        $flatCategory = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');
        $bypassFlat = Mage::getStoreConfig('sooqr_connect/generate/bypass_flat');

        if ((!$flatProduct || !$flatCategory) && !$bypassFlat) {
            $msg = '<div id="messages">
             <ul class="messages">
              <li class="notice-msg">
               <ul>
                <li>
                 <span>
                  ' . Mage::helper('sooqr')->__('Note: We recommend to enable the "Flat Catalog Category" and "Flat Catalog Product" to reduce the server load for the feed generation. <a href="https://www.magmodules.eu/help/sooqr/enable-flat-catalog" target="_blank">More information.</a>') . '
                 </span>
                </li>
               </ul>
              </li>
             </ul>
             </div>';
            $html = $html . $msg;
        }

        if (!$bypassFlat && Mage::getStoreConfig('catalog/frontend/flat_catalog_product')) {
            $storeId = Mage::helper('sooqr')->getStoreIdConfig();
            $atts = Mage::getModel("sooqr/sooqr")->getFeedAttributes('', $storeId, 'flatcheck');
            $nonFlatAttributes = Mage::helper('sooqr')->checkFlatCatalog($atts);
            if (count($nonFlatAttributes) > 0) {

                $msg = 'Note: The following used attribute(s) were not found in the flat catalog: %s. ';
                $msg .= 'This can result in a higher resource usage which can slow down the feed generation. ';
                $msg .= 'Click <a target="_blank" href="%s">here</a> to add these to the flat catalog. ';
                $msg .= 'Or click <a target="_blank" href="%s">here</a> to bypass Flat Catalog.';
                $msg .= '<span style="float:right"><a target="_blank" href="%s">[Read More]</a></span>';

                $html .= '<div id="messages"><ul class="messages"><li class="notice-msg"><ul><li><span>';
                $html .= $this->__(
                    $msg,
                    implode($nonFlatAttributes, ', '),
                    $this->getUrl('*/sooqr/addToFlat'),
                    $this->getUrl('*/sooqr/bypassFlat'),
                    'https://www.magmodules.eu/help/sooqr/enable-flat-catalog'
                );
                $html .= '</span></ul></li></ul></div>';
            }
        }
        return $html;
    }

}