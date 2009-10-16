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
 * Web service json output class
 *
 *
 * @package    WiFiDogAuthServer
 * @subpackage WebService
 * @author     Geneviève Bastien <gbastien@versatic.net>
 */
class WSOutput_Json extends WSOutput
{

    
    /**
      * Constructor for the web service 
      * @param $params array  the array of GET parameters
      * */
    protected function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Formats the actual output.  This function is typically called from outputError or outputSuccess
     * 
     * @param $returnArray
     * @return the properly formatted output
     */
    protected function output($returnArray = array()) {
        return json_encode($returnArray);
    }
    
}