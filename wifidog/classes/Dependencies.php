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
 * @copyright  2006-2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

// Detect Gettext support
if (!function_exists('gettext')) {
    /**
     * Load Locale class if Gettext support is not available
     */
    require_once ('classes/Locale.php');
}
require_once ('classes/Utils.php');

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
	 * @var array
	 */
	private static $_components = array(
	'mbstring' => array (
	    'mandatory' => 1,
	    "type" => "phpExtension", 
	    'description' => 'Required for core auth-server and RSS support'
	    ),
	'session' => array (
	    'mandatory' => 1,
	    "type" => "phpExtension", 
	    'description' => 'Required for core auth-server'
	    ),
	'pgsql' => array (
	    'mandatory' => 1,
	    "type" => "phpExtension", 
	    'description' => 'Required for auth-server to connect to Postgresql database'
	    ),
	"Smarty" => array (
	    "type" => "localLib", 
	    "detectFiles" => "lib/smarty/Smarty.class.php", 
	    'description' => "Required for all parts of wifidog",
	    'website' => "http://smarty.php.net/"
	    ),
	'gettext' => array (
	    "type" => "phpExtension", 
	    'description' => 'Almost essential: Without gettext, the auth-server will still work, but you will loose internationalization'
	    ),
	'dom' => array (
	    "type" => "phpExtension", 
	    'description' => 'Required to export the list of HotSpots as a RSS feed and for the geocoders'
	    ),
	"FPDF" => array (
	    "type" => "localLib", 
	    "detectFiles" => "lib/fpdf/fpdf.php", 
	    'description' => "Required if you want to be able to export the node list as a PDF file",
	    'website' => "http://www.fpdf.org/"
	    ),
	"Image_Graph" => array (
	    "type" => "pearStandard", 
	    "detectFiles" => "Image/Graph.php", 
	    'description' => "Required if you want to use to see the graphs in statistics"
	    ),
	"Phlickr" => array (
	    "type" => "pearCustom", 
	    "detectFiles" => "Phlickr/Api.php", 
	    'description' => "Required by content type FlickrPhotostream",
	    'website' => "http://drewish.com/projects/phlickr/"
	    ),
	"FCKeditor" => array (
	    "type" => "localLib", 
	    "detectFiles" => "lib/FCKeditor/fckeditor.php", 
	    'description' => "Required by content type FCKEditor (WYSIWYG HTML)",
	    'website' => "http://www.fckeditor.net/"
	    ),
	"HtmlSafe" => array (
	    "type" => "pearStandard", 
	    "detectFiles" => "HTML/Safe.php", 
	    'description' => "Optional for content type Langstring (and subtypes) to better strip out dangerous HTML"
	    ),
	"RADIUS" => array (
	    "type" => "pearStandard", 
	    'description' => "Required by the optional Radius Authenticator"
	    ),
	'Auth_RADIUS' => array (
	    "type" => "pearStandard", 
	    'description' => "Required by the optional Radius Authenticator"
	    ),
	'Crypt_CHAP' => array (
	    "type" => "pearStandard", 
	    'description' => "Required by the optional Radius Authenticator"
	    ),
	'mcrypt' => array (
	    "type" => "phpExtension", 
	    'description' => 'Required by the optional Radius Authenticator'
	    ),
	'mhash' => array (
	    "type" => "phpExtension", 
	    'description' => 'Required by the optional Radius Authenticator'
	    ),
	'xmlrpc' => array (
	    "type" => "phpExtension", 
	    'description' => 'Required by the optional Radius Authenticator'
	    ),
	"ldap" => array (
	    "type" => "phpExtension", 
	    'description' => "Required by the optional LDAP Authenticator"
	    ),
	'xml' => array (
	    "type" => "phpExtension", 
	    'description' => 'Required for RSS support'
	    ),
	 "Cache" => array (
	    "type" => "pearStandard", 
	    "detectFiles" => "Cache/Lite.php", 
	    'description' => "Required if you want to turn on the experimental USE_CACHE_LITE in config.php"
	    ),
	    );

	       	    /** Object cache for the object factory (getObject())*/
	       	    private static $instanceArray = array();

	       	    private $id;

	   	/** Get the entire array of dependencies
	 * @return boolean Returns whether the file has been found or not.
	 */
	   	public static function getDependencies()
	   	{
	   	    $retval = array();
	   	    foreach (self::$_components as $component_key=>$component_info)
	   	    {
	   	        $retval[]=self::getObject($component_key);
	   	    }
	   	    return $retval;
	   	}

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
	 */
	public static function check($component, &$errmsg = null)
	{
	    // Init values
	    $_returnValue = false;

	    // Check, if the requested component can be found.
	    if (isset(self::$_components[$component])) {
	        // Are we checking for a PHP extension or a PHP library?
	        if (self::$_components[$component]["type"] == "phpExtension") {
	            // Warning : extension_loaded(string) is case sensitive
	            $_returnValue = extension_loaded($component);
	        }
	        else if (self::$_components[$component]["type"] == "localLib") {
	            if (is_array(self::$_components[$component]["detectFiles"])) {
	                $_singleReturns = true;

	                foreach (self::$_components[$component]["detectFiles"] as $_fileNames) {
	                    $_filePath = WIFIDOG_ABS_FILE_PATH . $_fileNames;

	                    if (!file_exists($_filePath)) {
	                        $_singleReturns = false;

	                        if (!is_null($errmsg)) {
	                            // The component has NOT been found. Return error message.
	                            $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), $component, $_filePath);
	                        }
	                    }
	                }

	                $_returnValue = $_singleReturns;
	            }
	            else {
	                $_filePath = WIFIDOG_ABS_FILE_PATH . self::$_components[$component]["detectFiles"];

	                if (file_exists($_filePath)) {
	                    // The component has been found.
	                    $_returnValue = true;
	                } else {
	                    if (!is_null($errmsg)) {
	                        // The component has NOT been found. Return error message.
	                        $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), $component, $_filePath);
	                    }
	                }
	            }
	        }
	        else  if (self::$_components[$component]["type"] == "standardLib"){
	            if (is_array(self::$_components[$component]["detectFiles"])) {
	                $_singleReturns = true;

	                foreach (self::$_components[$component]["detectFiles"] as $_fileNames) {
	                    // We need to use a custom file_exists to also check in the include path
	                    if (!self::file_exists_incpath($_fileNames)) {
	                        $_singleReturns = false;

	                        if (!is_null($errmsg)) {
	                            // The component has NOT been found. Return error message.
	                            $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), $component, get_include_path());
	                        }
	                    }
	                }

	                $_returnValue = $_singleReturns;
	            }
	            else {
	                // We need to use a custom file_exists to also check in the include path
	                if (self::file_exists_incpath(self::$_components[$component]["detectFiles"])) {
	                    // The component has been found.
	                    $_returnValue = true;
	                } else {
	                    if (!is_null($errmsg)) {
	                        // The component has NOT been found. Return error message.
	                        $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), $component, get_include_path());
	                    }
	                }
	            }
	        }
	        else  if (self::$_components[$component]["type"] == "pearStandard" || self::$_components[$component]["type"] == "pearCustom"){
	            $command = "pear list | grep ".escapeshellarg($component);
	            $output = null;
	            $cmd_retval = Utils::execCommand($command, $output, false);
	            $cmd_retval==0?$_returnValue = true: $_returnValue = false;
	        }
	        else
	        {
	            throw new Exception(sprintf("Unknown component type: %s", self::$_components[$component]["type"]));
	        }
	    }
	    else {
	        // The requested component has not been defined in this class.
	        throw new Exception("Component not found");
	    }

	    return $_returnValue;
	}
    /**
     * Get an instance of the object
     *
     * @param string $id The object id
     *
     * @return mixed The Content object, or null if there was an error
     *               (an exception is also thrown)
     *
     * @see GenericObject
     * @static
     * @access public
     */
    public static function getObject($id)
    {
        if(!isset(self::$instanceArray[$id]))
        {
            self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
    }
	/** Constructor */
	private function __construct($component){
	    $this->id = $component;
	}
	    /** Retreives the id of the object
     * @return The id, a string */
	    public function getId(){
	        return $this->id;
	    }
	/** Get website URL for the dependency (if available) 
@return URL or null*/
	public function getWebsiteURL(){
	    $retval = null;
	    if(self::$_components[$this->id]["type"] == "pearStandard")
	    {
	        $retval = "http://pear.php.net/package/".$this->id."/";
	    }
	    else if(self::$_components[$this->id]["type"] == "phpExtension")
	    {
	        $retval = "http://www.php.net/".$this->id."/";
	    }
	    else
	    {
	        if(!empty(self::$_components[$this->id]['website']))
	        {
	            $retval = self::$_components[$this->id]['website'];
	        }
	    }
	    return $retval;
	}
	/** Get the description of the dependency (if available) 
@return String or null*/
	public function getDescription(){
	    $retval = null;
	    if(!empty(self::$_components[$this->id]['description']))
	    {
	        $retval = self::$_components[$this->id]['description'];
	    }
	    return $retval;
	}
	/** Get the type of the dependency  
@return String*/
	public function getType(){
	    return self::$_components[$this->id]['type'];
	}
	/** Get the type of the dependency  
@return String*/
	public function isMandatory(){
	    $retval = null;
	    if(!empty(self::$_components[$this->id]['mandatory']))
	    {
	        $retval = true;
	    }
	    return $retval;
	}
}//End class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

