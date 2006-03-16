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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Philippe April
 * @copyright  2005-2006 Max Horváth, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * This class checks the existence of components required by WiFiDog.
 * Note that it implicitely depends on the defines in include/path_defines_base.php
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Philippe April
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 */
class Dependencies
{
	/**
	 * List of components used by WiFiDog
	 *
	 * @var array
	 * @access private
	 */
	private static $_components = array(
	   "LDAP" => array ("name" => "LDAP", "files" => "ldap", "PhpExtension" => true, "localLib" => false),
	   "ImageGraph" => array ("name" => "PEAR::Image_Graph", "files" => "Image/Graph.php", "PhpExtension" => false, "localLib" => false),
	   "Phlickr" => array ("name" => "PEAR::Phlickr", "files" => "Phlickr/Api.php", "PhpExtension" => false, "localLib" => false),
	   "Cache" => array ("name" => "PEAR::Cache_Lite", "files" => "Cache/Lite.php", "PhpExtension" => false, "localLib" => false),
	   "HtmlSafe" => array ("name" => "PEAR::HTML_Safe", "files" => "HTML/Safe.php", "PhpExtension" => false, "localLib" => false),
	   "Radius" => array ("name" => "PEAR::RADIUS", "files" => array("Auth/RADIUS.php", "Crypt/CHAP.php"), "PhpExtension" => false, "localLib" => false),
	   "FCKeditor" => array ("name" => "FCKeditor", "files" => "lib/FCKeditor/fckeditor.php", "PhpExtension" => false, "localLib" => true),
	   "Smarty" => array ("name" => "Smarty", "files" => "lib/smarty/Smarty.class.php", "PhpExtension" => false, "localLib" => true)
	   );

	/**
	 * Checks if a file exists, including checking in the include path
	 *
	 * @param string $file Path or name of a file
	 *
	 * @return boolean Returns whether the file has been found or not.
	 *
	 * @author Aidan Lister <aidan@php.net>
	 * @link http://aidanlister.com/repos/v/function.file_exists_incpath.php
	 *
	 * @static
	 * @access public
	 */
	public static function file_exists_incpath($file)
	{
		$_paths = explode(PATH_SEPARATOR, get_include_path());

		foreach ($_paths as $_path) {
		    // Formulate the absolute path
			$_fullPath = $_path . DIRECTORY_SEPARATOR . $file;

			// Check it
			if (file_exists($_fullPath)) {
				return $_fullPath;
			}
		}

		return false;
	}

	/**
	 * Checks if a component is available.
	 *
	 * This function checks, if a specific component is available to be used
	 * by WiFiDog.
	 *
	 * Here's an example of how to use the function:
	 * <code>
	 * Dependencies::check("FCKeditor", $errmsg);
	 * </code>
	 *
	 * @param string $component Name of component to be checked.
	 * @param string $errmsg    Reference of a string which would contain an
	 *                          error message.
	 *
	 * @return boolean Returns whether the component has been found or not.
	 *
	 * @static
	 * @access public
	 */
	public static function check($component, &$errmsg = null)
	{
		// Init values
		$_returnValue = false;

		// Check, if the requested component can be found.
		if (isset(self::$_components[$component])) {
		    // Are we checking for a PHP extension or a PHP library?
		    if (self::$_components[$component]["PhpExtension"]) {
		        if (is_array(self::$_components[$component]["files"])) {
		            $_singleReturns = true;

		            foreach (self::$_components[$component]["files"] as $_fileNames) {
        		        if (!extension_loaded($_fileNames)) {
        		            $_singleReturns = false;
        		        }
		            }

		            $_returnValue = $_singleReturns;
		        } else {
    		        if (extension_loaded(self::$_components[$component]["files"])) {
    		            $_returnValue = true;
    		        }
		        }
		    } else {
    			if (self::$_components[$component]["localLib"]) {
    		        if (is_array(self::$_components[$component]["files"])) {
    		            $_singleReturns = true;

    		            foreach (self::$_components[$component]["files"] as $_fileNames) {
                            $_filePath = WIFIDOG_ABS_FILE_PATH . $_fileNames;

                            if (!file_exists($_filePath)) {
                                $_singleReturns = false;

            				    if (!is_null($errmsg)) {
                					// The component has NOT been found. Return error message.
                					require_once ('Locale.php');

                					$errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self::$_components[$component]["name"], $_filePath);
            				    }
            				}
    		            }

    		            $_returnValue = $_singleReturns;
    		        } else {
                        $_filePath = WIFIDOG_ABS_FILE_PATH . self::$_components[$component]["files"];

                        if (file_exists($_filePath)) {
        					// The component has been found.
        					$_returnValue = true;
        				} else {
        				    if (!is_null($errmsg)) {
            					// The component has NOT been found. Return error message.
            					require_once ('Locale.php');

            					$errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self::$_components[$component]["name"], $_filePath);
        				    }
        				}
    		        }
    			} else {
    		        if (is_array(self::$_components[$component]["files"])) {
    		            $_singleReturns = true;

    		            foreach (self::$_components[$component]["files"] as $_fileNames) {
            				// We need to use a custom file_exists to also check in the include path
            				if (!self::file_exists_incpath($_fileNames)) {
            				    $_singleReturns = false;

            				    if (!is_null($errmsg)) {
                					// The component has NOT been found. Return error message.
                					require_once ('Locale.php');

                                    $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self::$_components[$component]["name"], get_include_path());
            				    }
            				}
    		            }

    		            $_returnValue = $_singleReturns;
    		        } else {
        				// We need to use a custom file_exists to also check in the include path
        				if (self::file_exists_incpath(self::$_components[$component]["files"])) {
        					// The component has been found.
        					$_returnValue = true;
        				} else {
        				    if (!is_null($errmsg)) {
            					// The component has NOT been found. Return error message.
            					require_once ('Locale.php');

                                $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self::$_components[$component]["name"], get_include_path());
        				    }
        				}
    		        }
    			}
		    }
		} else {
			// The requested component has not been defined in this class.
			throw new Exception("Component not found");
		}

		return $_returnValue;
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