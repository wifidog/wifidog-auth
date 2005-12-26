<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * @author     Philippe April
 * @copyright  2005 Philippe April
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

class Dependencies {

    /**
     * Check if a component is available.
     *
     * @author Philippe April
     * @author Max Horvath <max.horvath@maxspot.de>
	 * @copyright 2005 Philippe April
	 * @copyright  2005 Max Horvath <max.horvath@maxspot.de> - maxspot GmbH
     * @param string $component Name of component to be checked.
     * @param string $errmsg Reference of a string which would contain an error
     * message.
     * @return boolean Returns whether the component has been found or not.
     */
    static public function check($component, & $errmsg) {
        // Init values.
        $components = array();

        // Define all available modules.
        $components["ImageGraph"] = array ("name" => "PEAR::Image_Graph", "file" => "Image/Graph.php");
        $components["Phlickr"] = array ("name" => "PEAR::Phlickr", "file" => "Phlickr/Api.php");
        $components["Cache"] = array ("name" => "PEAR::Cache_Lite", "file" => "Cache/Lite.php");
        $components["FCKeditor"] = array ("name" => "FCKeditor", "file" => BASEPATH."lib/FCKeditor/fckeditor.php");

        // Check, if the requested component can be found.
        if (isset ($components[$component])) {
            $component_info = $components[$component];
            if (@include_once($component_info["file"])) {
                // The component has NOT been found.
                return true;
            } else {
                // The component has NOT been found. Return error message.
                require_once BASEPATH.'classes/Locale.php';
                $errmsg = $component_info["name"]._(" is not installed");
                return false;
            }
        } else {
            // The requested component has not been defined in this class.
            throw new Exception("Component not found");
        }
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
