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

class Magmodules_Sooqr_Helper_Write extends Mage_Core_Helper_Abstract
{

    /**
     * @param $config
     *
     * @return Varien_Io_File
     */
    public function createFeed($config)
    {
        $header = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        $header .= '<rss xmlns:sqr="http://base.sooqr.com/ns/1.0" version="2.0" encoding="utf-8">' . PHP_EOL;
        $header .= ' <products>' . PHP_EOL;

        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => Mage::getBaseDir('tmp')));
        $io->streamOpen($config['file_name']);
        $io->streamWrite($header);

        return $io;
    }

    /**
     * @param                $row
     * @param Varien_Io_File $io
     * @param string         $item
     */
    public function writeRow($row, Varien_Io_File $io, $item = 'product')
    {
        $io->streamWrite($this->getXmlFromArray($row, $item));
    }

    /**
     * @param $data
     * @param $type
     *
     * @return string
     */
    public function getXmlFromArray($data, $type)
    {
        $outputEmpty = array();
        $xml = '  <' . $type . '>' . PHP_EOL;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= '   <sqr:' . $key . '>' . PHP_EOL;
                foreach ($value as $ks => $vs) {
                    if (!empty($vs)) {
                        $xml .= '      <node>' . $this->cleanValue($vs) . '</node>' . PHP_EOL;
                    }
                }

                $xml .= '   </sqr:' . $key . '>' . PHP_EOL;
            } else {
                if (!empty($value) || in_array($key, $outputEmpty)) {
                    $xml .= '   <sqr:' . $key . '>' . $this->cleanValue($value) . '</sqr:' . $key . '>' . PHP_EOL;
                }
            }
        }

        $xml .= '  </' . $type . '>' . PHP_EOL;

        return $xml;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function cleanValue($value)
    {
        return htmlspecialchars($value, ENT_XML1);
    }

    /**
     * @param $io
     * @param $config
     */
    public function closeFeed(Varien_Io_File $io, $config)
    {
        $footer = ' </products>' . PHP_EOL;
        $footer .= '</rss>';
        $io->streamWrite($footer);
        $io->streamClose();

        $tmp = Mage::getBaseDir('tmp') . DS . $config['file_name'];
        $new = $config['file_path'] . DS . $config['file_name'];

        if (!file_exists($config['file_path'])) {
            mkdir($config['file_path']);
        }

        rename($tmp, $new);
    }

}