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
 * Abstract class for the web service
 *
 * @todo Make all the setter functions no-op if the value is the same as what
 * was already stored Use setCustomPortalReduirectUrl as an example
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
abstract class WifidogWS
{

    protected $_params;
    /**
     * The appropriate output class
     * @var WSOutput
     */
    protected $_output;
    
    /**
     * Array of values to return
     */
    protected $_outputArr = array();
	
    /** Instantiate the web service.  The corresponding class is WifidogWS_V$version
     * @param $version the version of the web service protocol
     * @return a WifidogWS object
     */
    public static function webServiceFactory($version = 1)
    {
        if (!is_int($version))
            $version = 1;
        $classname = "WifidogWS_V$version";
        if (!class_exists($classname, false)) {
            if (file_exists('ws/classes/WifidogWS/V'.$version.'.php'))
                include_once('ws/classes/WifidogWS/V'.$version.'.php');
        }
        if (!class_exists($classname, false)) {
            $classname="WifidogWS_V1";
            include_once('ws/classes/WifidogWS/V1.php');        	
        }
        
        return (new $classname());
    }
    
     /**
      * Constructor for the web service 
      * 
      * */
    protected function __construct()
    {
       
    }
    
    /**
     * Returns the web service output class
     * @return WSOutput
     */
    public function getOutput() {
        return $this->_output;
    }
    
    /**
     * Returns the formatted output
     * @return string
     */
    public function output() {
        if (!isset($this->_output)) {
            throw new WSException("Can't output message because no output class is defined.");
        }
        return $this->_output->outputSuccess($this->_outputArr);
    }
    
    /**
     * Set the web service parameters
     * @param $params   the arrray of GET parameters
     */
    public function setParams($params = array()) {
        // Construct the output of the web service
        if (isset($params['f'])) {
            $this->_output = WSOutput::OutputFactory($params['f']);
            unset($params['f']);
        } else {
            $this->_output = WSOutput::OutputFactory();
        }
        $this->_params = $params;
    }
    
    abstract protected function executeAction();
    
    /**
     * Passes the control to the web service class to execute the request
     * @return a properly formatted output
     */
    public function execute() {
        $this->executeAction();
    }
}