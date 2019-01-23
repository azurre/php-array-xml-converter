<?php
/**
 *  OpenLSS - Lighter Smarter Simpler
 *    This file is part of OpenLSS.
 *    OpenLSS is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Lesser General Public License as
 *    published by the Free Software Foundation, either version 3 of
 *    the License, or (at your option) any later version.
 *    OpenLSS is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Lesser General Public License for more details.
 *    You should have received a copy of the
 *    GNU Lesser General Public License along with OpenLSS.
 *    If not, see <http://www.gnu.org/licenses/>.
 */

namespace LSS;

/**
 * XML2Array: A class to convert XML to array in PHP
 * It returns the array which can be converted back to XML using the Array2XML script
 * It takes an XML string or a DOMDocument object as an input.
 * See Array2XML: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-xml-to-array-in-php-xml2array
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (07 Dec 2011)
 * Version: 0.2 (04 Mar 2012)
 *            Fixed typo 'DomDocument' to 'DOMDocument'
 * Usage:
 *       $array = Xml2Array::createArray($xml);
 */
class Xml2Array
{
    protected static $xml;
    protected static $encoding = 'UTF-8';
    protected static $prefix_attributes = '@';

    /**
     * Initialize the root XML node [optional]
     *
     * @param string $version
     * @param string $encoding
     * @param bool $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
    {
        static::$xml = new \DOMDocument($version, $encoding);
        static::$xml->formatOutput = $format_output;
        static::$encoding = $encoding;
    }

    /**
     * Convert an XML to Array
     *
     * @param \DOMDocument|string $input_xml
     * @param int $options
     * @return array
     * @throws \Exception
     */
    public static function &createArray($input_xml, $options = 0)
    {
        $xml = static::getXMLRoot();
        if (is_string($input_xml)) {
            $parsed = $xml->loadXML($input_xml, $options);
            if (!$parsed) {
                throw new \Exception('Error parsing the XML string.');
            }
        } else {
            if (!$input_xml instanceof \DOMDocument) {
                throw new \Exception('The input XML object should be of type: DOMDocument.');
            }
            static::$xml = $input_xml;
        }
        $array[$xml->documentElement->tagName] = static::convert($xml->documentElement);
        static::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }

    /**
     * Convert an Array to XML
     *
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    protected static function &convert($node)
    {
        $output = array();

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output[static::$prefix_attributes . 'cdata'] = trim($node->textContent);
                break;

            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:
                // for each child node, call the covert function recursively
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = static::convert($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;

                        // avoid fatal error if the content looks like '<html><body>You are being <a href="https://some.url">redirected</a>.</body></html>'
                        if ($output !== null && !is_array($output)) {
                            continue;
                        }
                        // assume more nodes of same kind are coming
                        if (!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    } else {
                        //check if it is not an empty text node
                        if ($v !== '') {
                            $output = $v;
                        }
                    }
                }

                if (is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) === 1) {
                            $output[$t] = $v[0];
                        }
                    }
                    if (empty($output)) {
                        //for empty nodes
                        $output = '';
                    }
                }

                // loop through the attributes and collect them
                if ($node->attributes->length) {
                    $a = array();
                    foreach ($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = (string)$attrNode->value;
                    }
                    // if its an leaf node, store the value in @value instead of directly storing it.
                    if (!is_array($output)) {
                        $output = array(static::$prefix_attributes . 'value' => $output);
                    }
                    $output[static::$prefix_attributes . 'attributes'] = $a;
                }
                break;
        }
        return $output;
    }

    /**
     * Get the root XML node, if there isn't one, create it
     *
     * @return \DOMDocument
     */
    protected static function getXMLRoot()
    {
        if (empty(static::$xml)) {
            static::init();
        }
        return static::$xml;
    }
}
