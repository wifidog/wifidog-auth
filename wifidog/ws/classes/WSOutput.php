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
 * Abstract class for the web service output
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
abstract class WSOutput
{
	
    /** Instantiate the web service.  The corresponding class is WifidogWS_V$version
     * @param $version the version of the web service protocol
     * @return a WifidogWS object
     */
    public static function OutputFactory($format = 'json')
    {
        if (!is_string($format) || ($format == ''))
            $format = 'json';
        $format = ucfirst(strtolower($format));
        $classname = "WSOutput_$format";

        if (!class_exists($classname, false)) {
            if (file_exists(dirname(__FILE__)."/WSOutput/$format.php"))
                include_once("ws/classes/WSOutput/$format.php");
        }
        if (!class_exists($classname, false)) {
            throw new WSException("Output type $format not supported");           	
        }
        
        return (new $classname());
    }
    
     /**
      * Constructor for the web service 
      * @param $params array  the array of GET parameters
      * */
    protected function __construct()
    {

    }
    
    /**
     * Output the result with an error flag
     * @param $returnArray   the values to return
     * @return the properly formatted output
     */
    public function outputError($returnArray = array()) {
        $errArr = array('result' => 0, 'values' => $returnArray);
        return $this->output($errArr);
    }
    
    /**
     * Output the result with a success flag
     * @param $returnArray  the values to return
     * @return the properly formatted output
     */
    public function outputSuccess($returnArray = array()) {
        $succArr = array('result' => 1, 'values' => $returnArray);
        return $this->output($succArr);
    }
    
    /**
     * Formats the actual output.  This function is typically called from outputError or outputSuccess
     * To be implemented in every child class
     * @param $returnArray
     * @return the properly formatted output
     */
    abstract protected function output($returnArray = array());
    
}