<?php

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Web service xml output class
 * Main element is WiFiDogWebService
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
class WSOutput_Xml extends WSOutput
{
    protected $_xmldoc;
    
    /**
      * Constructor for the output
      * @param $params array  the array of GET parameters
      * */
    protected function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Transforms an array to xml
     * @param $array The array to transform
     * @param $parentNode DomElement  the parent node
     * @return unknown_type
     */
    protected function _toXml($array, &$parentNode)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $childNode = $this->_xmldoc->createElement(!is_numeric($key) ? $key : "item" );
                $this->_toXml($value, $childNode);
                $parentNode->appendChild($childNode);
            } else {
                $childNode = $this->_xmldoc->createElement(!is_numeric($key) ? $key : "item" , $value);
                $parentNode->appendChild($childNode);
            }
        }

    }
      
    /**
     * Formats the actual output.  This function is typically called from outputError or outputSuccess
     * 
     * @param $returnArray
     * @return the properly formatted output
     */
    protected function output($returnArray = array()) {
        
        //Prepare header
        header("Content-Type: text/xml; charset=UTF-8");
        $this->_xmldoc = new DOMDocument("1.0", "UTF-8");
        $this->_xmldoc->formatOutput = true;
        $wsRootNode = $this->_xmldoc->createElement("WiFiDogWebService");
        $this->_toXml($returnArray, $wsRootNode);
        $this->_xmldoc->appendChild($wsRootNode);
        
        return $this->_xmldoc->saveXML();
    }
    
}