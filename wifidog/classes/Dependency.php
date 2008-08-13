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
 * @author     Max HorvÃ¡th <max.horvath@freenet.de>
 * @author     Benoit GrÃ©goire <bock@step.polymtl.ca>
 * @copyright  2005-2007 Philippe April
 * @copyright  2005-2007 Max HorvÃ¡th, Horvath Web Consulting
 * @copyright  2006-2007 Benoit GrÃ©goire, Technologies Coeus inc.
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

define('OPENID_PATH', WIFIDOG_ABS_FILE_PATH.'lib/php-openid-2.0.0-rc2/');
define('SMARTY_PATH', WIFIDOG_ABS_FILE_PATH.'lib/Smarty-2.6.18/libs/');
/**
 * This class checks the existence of components required by WiFiDog.
 * Note that it implicitely depends on the defines in include/path_defines_base.php
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Max HorvÃ¡th <max.horvath@freenet.de>
 * @author     Benoit GrÃ©goire <bock@step.polymtl.ca>
 * @copyright  2005-2007 Philippe April
 * @copyright  2005-2007 Max HorvÃ¡th, Horvath Web Consulting
 * @copyright  2006-2007 Benoit GrÃ©goire, Technologies Coeus inc.
 */
class Dependency
{
    /**
     * An array of components used by WiFiDog
     * The main array key is the EXACT name name of the dependency.  Do NOT translate it or blindly change it;
     *   It is used in the code if various ways, for example to detect PHP or PEAR modules
     * Documentation of the various array keys:
     * 'mandatory' => Optional.  Set to true if the dependency absolutely required for basic operation of an auth server
     * 'type' => Mandatory.  The type of Dependency.  Currently, allowed values are:
     *	"phpExtension":  Standard PHP extension
     *	"peclStandard":  Standard (in the PECL reposidory) PECL PHP module
     *	"peclStandard":	 PEAR PHP module in the standard PEAR repository or in a custom channel
     * 	"pearCustom":	PEAR-compatible tarball
     *	"localLib": Custom PHP extension, to be downloaded and installed in wifidog/lib
     * 'detectFiles' => Mandatory for most type of dependencies, the relative path to the file that must exist for the dependency to be considered present.
     * 					The path is relative to the PHP path, or wifidog/lib depending on the type of install
     * 'description' => Description of the dependency, and what it's used for in wifidog
     * 'website' => URL to the dependency's official website
     * 'installSourceUrl' => For localLib and pearCustom dependency, the URL where the dependency can be downloaded.
     * 						For pearStandard, either the required alpha or beta name like "Image_Canvas-alpha" or the fulle channel URL like: "channel://pear.php.net/Image_graph-0.7.2" ( normally not used for dependencies in standard pear repositories)
     * 'installMethod' => For localLib, the protocol to be used to download and install the dependency.  Currently, allowed values are:
     * 	'tarball': Decompress a tarball in wifidog/lib
     * 'installDestination' => For localLib, the path, relative to wifidog/lib where the dependency should be installed.  Usually / if the tarball creates a folder.
     * 'filename' => temp download filename if sourceurl does not meet preg requirements.
     *
     * @var array
     */

    private static $_components = array(
    /* PHP extensions (mandatory) */
       'mbstring' => array (
       'mandatory' => true,
       "type" => "phpExtension",
       'description' => 'Required for core auth-server and RSS support'
       ),
       'session' => array (
       'mandatory' => true,
       "type" => "phpExtension",
       'description' => 'Required for core auth-server'
       ),
       'pgsql' => array (
       'mandatory' => true,
       "type" => "phpExtension",
       'description' => 'Required for auth-server to connect to Postgresql database'
       ),
       "Smarty" => array (
       'mandatory' => true,
       "type" => "localLib",
       "detectFiles" => "lib/Smarty-2.6.18/libs/Smarty.class.php",
       'description' => "Required for all parts of wifidog",
       'website' => "http://smarty.net/",
       'installSourceUrl' => "http://smarty.net/do_download.php?download_file=Smarty-2.6.18.tar.gz",
       'installMethod' => "tarball",
       'installDestination' => "/"
       ),
       "PHPMailer" => array (
       'mandatory' => true,
       "type" => "localLib",
       "detectFiles" => "lib/PHPMailer_v2.0.0/class.phpmailer.php",
       'description' => "Required for sending mail",
       'website' => "http://phpmailer.codeworxtech.com/",
       'installSourceUrl' => "http://superb-west.dl.sourceforge.net/sourceforge/phpmailer/PHPMailer_v2.0.0.tar.gz",
       'installMethod' => "tarball",
       'installDestination' => "/"
       ),

       /* PHP extensions (optional) */
       "simplepie" => array (
       "type" => "localLib",
       "detectFiles" => "lib/simplepie/simplepie.inc",
       'description' => "SimplePie is a dependency that provides an RSS parser in PHP. It is required for RssPressReview.  It is is recommended to install it, if you do not, RSS feed options will be disabled.",
       'website' => "http://simplepie.org/",
       'installSourceUrl' => "http://svn.simplepie.org/simplepie/branches/1.1/",
       'installMethod' => "svn",
       'installDestination' => "simplepie"
       ),
       'jpgraph' => array (
       "type" => "localLib",
       "detectFiles" => "lib/jpgraph-1.22/src/jpgraph.php",
       'description' => "JpGraph is a Object-Oriented Graph creating library for PHP.
JpGraph is not currently used by Wifidog (it will be use for statistic graphs in a later version). You can skip this installation if your not a developper.",
       'website' => "http://www.aditus.nu/jpgraph/",
       'installSourceUrl' => "http://hem.bredband.net/jpgraph/jpgraph-1.22.tar.gz",
       'installMethod' => "tarball",
       'installDestination' => "/"
       ),
       'feedpressreview' => array (
       "type" => "localLib",
       "detectFiles" => "lib/feedpressreview/FeedPressReview.inc",
       'description' => "Feed Press Review allows your athentication server to produce RSS Feeds.  It is recommended that it is installed.  If it is not installed, the RSS feed options will be disabled.",
       'website' => "http://projects.coeus.ca/feedpressreview/",
       'installSourceUrl' => "http://projects.coeus.ca/svn/feedpressreview/trunk/",
       'installMethod' => "svn",
       'installDestination' => "feedpressreview"
       ),
       'gettext' => array (
       "type" => "phpExtension",
       'description' => 'Almost essential: Without gettext, the auth-server will still work, but you will loose internationalization'
       ),
       'dom' => array (
       "type" => "phpExtension",
       'description' => 'Required to export the list of HotSpots as a RSS feed and for the geocoders'
       ),
       'libxml' => array (
       "type" => "phpExtension",
       'description' => 'Required to export the list of HotSpots as a RSS feed and for the geocoders'
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
       'curl' => array (
       "type" => "phpExtension",
       'description' => 'Allows faster RSS support and required if you want to use Phlickr'
       ),
       'gd' => array (
       "type" => "phpExtension",
       'description' => 'Required if you want to generate graphical statistics'
       ),
       'xsl' => array (
       "type" => "phpExtension",
       'description' => 'Required if you want to generate a node list using XSLT stylesheets'
       ),
       /* Pecl libraries */
       "radius" => array (
       "type" => "peclStandard",
       'description' => "Required by the optional Radius Authenticator.  If it's not detected, make sure it's installed (pecl list) AND loaded in php.ini (extension=radius.so)"
       ),

       /* Locally installed libraries */
       "FCKeditor" => array (
       "type" => "localLib",
       "detectFiles" => "lib/fckeditor/fckeditor.php",
       'description' => "Required by content type FCKEditor (WYSIWYG HTML)",
       'website' => "http://www.fckeditor.net/",
       'installSourceUrl' => "http://easynews.dl.sourceforge.net/sourceforge/fckeditor/FCKeditor_2.5.tar.gz",
       'installMethod' => "tarball",
       'installDestination' => "/",
       ),

       "FPDF" => array (
       "type" => "localLib",
       "detectFiles" => "lib/fpdf153/fpdf.php",
       'description' => "Required if you want to be able to export the node list as a PDF file",
       'website' => "http://www.fpdf.org/",
       'installSourceUrl' => "http://www.fpdf.org/en/dl.php?v=153&f=tgz",
       'installMethod' => "tarball",
       'installDestination' => "/",
       'filename' => "fpdf.tgz"
       ),
       "php-openid" => array (
       "type" => "localLib",
       "detectFiles" => "lib/php-openid-2.0.0-rc2/CHANGELOG",
       'description' => "Required for OpenID support (both as a consumer and Identity provider)",
       'website' => "http://www.openidenabled.com/php-openid/",
       'installSourceUrl' => "http://openidenabled.com/files/php-openid/packages/php-openid-2.0.0-rc2.tar.bz2",
       'installMethod' => "tarball",
       'installDestination' => "/"
       ),
       'gmp' => array (
       "type" => "phpExtension",
       'description' => 'Required for good OpenID support (otherwise, BCmath will be used, which is MUCH slower)'
       ),
       'BCmath' => array (
       "type" => "phpExtension",
       'description' => 'Required for OpenID support, but ONLY if GMP above is not available'
       ),
       /* PEAR libraries */
       'Auth_RADIUS' => array (
       "type" => "pearStandard",
       "detectFiles" => "Auth/RADIUS.php",
       'description' => "Required by the optional Radius Authenticator"
       ),
       'Crypt_CHAP' => array (
       "type" => "pearStandard",
       "detectFiles" => "Crypt/CHAP.php",
       'description' => "Required by the optional Radius Authenticator"
       ),
       "Cache_Lite" => array (
       "type" => "pearStandard",
       "detectFiles" => "Cache/Lite.php", #unsure whether this file still exists.
       'description' => "Required if you want to turn on the experimental USE_CACHE_LITE in config.php",
       ),
       "HTML_Safe" => array (
       "type" => "pearStandard",
       "detectFiles" => "HTML/Safe.php",
       'description' => "Optional for content type Langstring (and subtypes) to better strip out dangerous HTML",
       'installSourceUrl' => "HTML_Safe-beta"
       ),
       "Image_Graph" => array (
       "type" => "pearStandard",
       "detectFiles" => "Image/Graph.php",
       'description' => "Required if you want to generate graphical statistics",
       'installSourceUrl' => "Image_graph-alpha"
       ),
       "Image_Canvas" => array (
       "type" => "pearStandard",
       "detectFiles" => "Image/Canvas.php",
       'description' => "Required if you want to generate graphical statistics",
       'installSourceUrl' => "Image_Canvas-alpha"
       ),
       "Image_Color" => array (
       "type" => "pearStandard",
       "detectFiles" => "Image/Color.php",
       'description' => "Required if you want to generate graphical statistics"
       ),

       /* PHP extensions (custom installations) */
       "Phlickr" => array (
       "type" => "pearCustom",
       "detectFiles" => "Phlickr/Api.php",
       'description' => "Required by content type FlickrPhotostream",
       'website' => "http://drewish.com/projects/phlickr/",
       'installSourceUrl' => "http://superb-east.dl.sourceforge.net/sourceforge/phlickr/Phlickr-0.2.7.tgz"
       ),
       );

       /**
        * Object cache for the object factory (getObject())
        */
       private static $instanceArray = array();

       private $_id;

       /**
        * Constructor
        */
       private function __construct($component) {
           $this->_id = $component;
       }

       /**
        * Get the entire array of Dependency
        *
        * @return boolean Returns whether the file has been found or not.
        */
       public static function getDependencies()
       {
           $retval = array();

           foreach (self::$_components as $component_key=>$component_info) {
               $retval[] = self::getObject($component_key);
           }

           return $retval;
       }

       /** Use PHP internal functions to execute a command
        Â @return: Return value of the command*/
       function execVerbose($command, & $output, & $return_var, &$errMsg = null) {
           $errMsg .= "Executing: $command <br/>";
           exec($command.'  2>&1', $output, $return_var);
           if ($return_var != 0)
           $errMsg .= "<p style='color:red'><em>Error:</em>  Command did not complete successfully  (returned $return_var): <br/>\n";
           else
           $errMsg .= "<p style='color:green'><em>Command completed successfully</em>  (returned $return_var): <br/>\n";

           if (($return_var != 0) && $output) {
               foreach ($output as $output_line)
               $errMsg .= " $output_line <br/>\n";
           }
           $errMsg .= "</p>\n";
           return $return_var;
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
        * by Wifidog.
        *
        * @param string $component Name of component to be checked.
        * @param string $errmsg    Reference of a string which would contain an
        *                          error message.
        *
        * @return boolean Returns whether the component has been found or not.
        */
       public static function check($component, &$errmsg = null)
       {
           // Init values
           $returnValue = false;

           // Check, if the requested component can be found.
           if (isset(self::$_components[$component])) {
               // What are we checking for?
               if (self::$_components[$component]["type"] == "phpExtension" || self::$_components[$component]["type"] == "peclStandard") {
                   // Warning: extension_loaded(string) is case sensitive
                   $returnValue = extension_loaded($component);
               }
               else if (self::$_components[$component]["type"] == "localLib") {
                   if (is_array(self::$_components[$component]["detectFiles"])) {
                       $_singleReturns = true;
                       foreach (self::$_components[$component]["detectFiles"] as $_fileNames) {
                           $filePath = WIFIDOG_ABS_FILE_PATH . $_fileNames;

                           if (!file_exists($filePath)) {
                               echo "TEST";
                               $_singleReturns = false;
                               // The component has NOT been found. Return error message.
                               $errmsg .= sprintf(_("File %s not found"), $filePath);
                               break;
                           }
                       }

                       $returnValue = $_singleReturns;
                   }
                   else {
                       $filePath = WIFIDOG_ABS_FILE_PATH . self::$_components[$component]["detectFiles"];

                       if (file_exists($filePath)) {
                           // The component has been found.
                           $returnValue = true;
                       }
                       else {
                           // The component has NOT been found. Return error message.
                           $errmsg .= sprintf(_("File %s not found"), $filePath);
                       }
                   }
               }
               else if (self::$_components[$component]["type"] == "pearStandard" || self::$_components[$component]["type"] == "pearCustom") {
                   if (is_array(self::$_components[$component]["detectFiles"])) {
                       $_singleReturns = true;

                       foreach (self::$_components[$component]["detectFiles"] as $_fileNames) {
                           // We need to use a custom file_exists to also check in the include path
                           if (!self::file_exists_incpath($_fileNames)) {
                               $_singleReturns = false;

                               // The component has NOT been found. Return error message.
                               $errmsg .= sprintf(_("File %s not found in %s"), $_fileNames, get_include_path());
                           }
                       }

                       $returnValue = $_singleReturns;
                   } else {
                       // We need to use a custom file_exists to also check in the include path
                       if (self::file_exists_incpath(self::$_components[$component]["detectFiles"])) {
                           // The component has been found.
                           $returnValue = true;
                       }
                       else {

                           // The component has NOT been found. Return error message.
                           $errmsg .= sprintf(_("File %s not found in %s"), self::$_components[$component]["detectFiles"], get_include_path());
                       }
                   }
               }
               else {
                   throw new Exception(sprintf("Unknown component type: %s", self::$_components[$component]["type"]));
               }
           } else {
               // The requested component has not been defined in this class.
               throw new Exception("Component not found");
           }

           return $returnValue;
       }

       /**
        * Checks if one of the mandatory components is missing.
        *
        * @param string $errmsg    Reference of a string which would contain an
        *                          error message.
        *
        * @return boolean Returns false if any components are missing.
        */
       public static function checkMandatoryComponents(&$errmsg = null)
       {
           // Init values
           $returnValue = true;
           $components = self::getDependencies();
           foreach($components as $component) {
               if($component->isMandatory()) {
                   $returnValue &= self::check($component->getId(), $errmsg);
               }
           }

           return $returnValue;
       }

       /** Use PHP internal functions to download a file */
       static public function downloadFile($remoteURL, $localPath) {
           set_time_limit(1500); // 25 minutes timeout
           return copy($remoteURL, $localPath);
       }
       /**
        * Get a UI to install the component
        *
        * @return html markup.
        */
       public function getInstallUI()
       {
           // Init values
           $html = false;

           // Check, if the requested component can be found.
           if (self::check($this->getId())) {
               //Component already installed
           }
           else {
               // What are we checking for?
               $type = $this->getType();
               switch ($type) {
                   case "phpExtension":
                       $html .= sprintf(_("To install this standard PHP extension, look for a package with a similar name in your distribution's package manager.  Ex: For Debian based distributions, you may try 'sudo apt-get install php5-%s'"), $this->getId());

                       break;
                   case "localLib":
                       if($this->getInstallSourceUrl()) {
                           $name = $this->getId().'_install';
                           $value = sprintf(_("Install %s"), $this->getId());
                           $html .= sprintf("<input type='submit' name='%s' value='%s'/>", $name, $value);

                       }
                       else {
                           $html .= sprintf(_("Sorry, i couldn't find the source for %s in installSourceUrl"),
                           $this->getId());
                       }
                       break;


                   case "pearStandard":
                       if($this->getInstallSourceUrl()) {
                           $installSource=$this->getInstallSourceUrl();
                       }
                       else {
                           $installSource=$this->getId();
                       }
                       $html .= sprintf(_("To install this standard PEAR extension, try 'sudo pear install --onlyreqdeps %s'"), $installSource);
                       break;


                   case "peclStandard":
                       if($this->getInstallSourceUrl()) {
                           $installSource=$this->getInstallSourceUrl();
                       }
                       else {
                           $installSource=$this->getId();
                       }
                       $html .= sprintf(_("To install this standard PEAR extension, try 'sudo pecl install %s'"), $installSource);
                       break;


                   case "pearCustom":
                       if($this->getInstallSourceUrl()) {
                           $installSource=$this->getInstallSourceUrl();
                       }
                       else {
                           $installSource=sprintf(_("url_to_the_tarball (Sorry, i couldn't find the source for %s in installSourceUrl)"), $this->getId());
                       }
                       $html .= sprintf(_("To install this custom PEAR extension, use 'sudo pear install %s'"), $installSource);
                       break;


                   default:
                       $html .= sprintf(_("Sorry, I don't know how to install a %s extension"), $type);
               }
           }
           return $html;
       }

       /**
        * Get a UI to install the component
        *
        * @return true if something was processed.
        */
       public function processInstallUI(&$errMsg=null)
       {

           $retval = false;
           $name = $this->getId().'_install';
           if(!empty($_REQUEST[$name]))
           {
               $this->install($errMsg);
           }

           return $retval;
       }
       /**
        * Retreives the id of the object
        *
        * @return The id, a string
        */
       public function getId() {
           return $this->_id;
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
       public static function &getObject($id)
       {
           if(!isset(self::$instanceArray[$id])) {
               self::$instanceArray[$id] = new self($id);
           }

           return self::$instanceArray[$id];
       }

       /**
        * Get website URL for the dependency (if available)
        *
        * @return URL or null
        */
       public function getWebsiteURL()
       {
           $retval = null;

           if(self::$_components[$this->_id]["type"] == "phpExtension") {
               $retval = "http://www.php.net/" . $this->_id . "/";
           } else if(self::$_components[$this->_id]["type"] == "pearStandard") {
               $retval = "http://pear.php.net/package/" . $this->_id . "/";
           } else if(self::$_components[$this->_id]["type"] == "peclStandard") {
               $retval = "http://pecl.php.net/package/" . $this->_id . "/";
           } else {
               if(!empty(self::$_components[$this->_id]['website'])) {
                   $retval = self::$_components[$this->_id]['website'];
               }
           }

           return $retval;
       }

       /**
        * Get the description of the dependency (if available)
        *
        * @return String or null
        */
       public function getDescription()
       {
           $retval = null;

           if(!empty(self::$_components[$this->_id]['description'])) {
               $retval = self::$_components[$this->_id]['description'];
           }

           return $retval;
       }


       /**
        * Get the source URL where the package can be downloaded.  It's meaning depends on the install method (for example, it may be a svn source)
        *
        * @return String or null
        */
       public function getInstallSourceUrl()
       {
           $retval = null;
           if(!empty(self::$_components[$this->_id]['installSourceUrl'])) {
               $retval = self::$_components[$this->_id]['installSourceUrl'];
           }
           return $retval;
       }
       /**
        * Get the install method for this dependency (only for those that can be directly installed by the auth server)                  *
        * @return String or null
        */
       public function getInstallMethod()
       {
           $retval = null;
           if(!empty(self::$_components[$this->_id]['installMethod'])) {
               $retval = self::$_components[$this->_id]['installMethod'];
           }
           return $retval;
       }

       /**
        * Get the install destination.  Interpretation depends on the install method.  Usisally the parameter to be passed to tar or SVN                 *
        * @return String or null
        */
       public function getInstallDestination()
       {
           $retval = null;
           if(!empty(self::$_components[$this->_id]['installDestination'])) {
               $retval = self::$_components[$this->_id]['installDestination'];
           }
           return $retval;
       }
       /**
        * Get the type of the dependency
        *
        * @return String
        */
       public function getType()
       {
           return self::$_components[$this->_id]['type'];
       }

       /**
        * Get the type of the dependency
        *
        * @return String
        */
       public function isMandatory()
       {
           $retval = null;

           if(!empty(self::$_components[$this->_id]['mandatory'])) {
               $retval = true;
           }

           return $retval;
       }

       public function install(&$errorMsg = null){
           $installSourceUrl = $this->getInstallSourceUrl();
           $installDestinationPathOrig = $this->getInstallDestination();
           if(!$installSourceUrl || !$installDestinationPathOrig) {
               $errorMsg .= "<em style=\"color:red\">Error:</em>Either the install source or destination path is missing<br/>\n";
           }
           else {
               $installDestinationPath = WIFIDOG_ABS_FILE_PATH . "lib/" .$installDestinationPathOrig;
               $installMethod = $this->getInstallMethod();
               switch($installMethod) {
                   case "svn":
                       self::execVerbose("svn co ".escapeshellarg($installSourceUrl)." ".escapeshellarg      ($installDestinationPath), $output, $return, $errorMsg);


                       break;

                   case "tarball":
                       $downloadPath = WIFIDOG_ABS_FILE_PATH . "tmp/";
                       chdir($downloadPath);
                       if(!empty(self::$_components[$this->_id]['filename'])) {
                           $filename = self::$_components[$this->_id]['filename'];
                       }
                       else {
                           $filename_array = preg_split("/\//", $installSourceUrl);
                           $filename = array_pop($filename_array);
                       }


                       if (!file_exists($downloadPath . $filename)){
                           $errorMsg .= "Downloading tarball ($installSourceUrl) : ";
                           //execVerbose("wget \"$phlickr_full_url\" 2>&1", $output, $return);
                           self::downloadFile($installSourceUrl, $downloadPath . $filename);

                           if (!file_exists($downloadPath . $filename)) { # Error occured, print output of wget
                               $errorMsg .= sprintf("<em style=\"color:red\">Error:</em> Unable to download $installSourceUrl to $destinationPath<br/>\n");
                               return false;
                           }
                           else {
                               $errorMsg .= "OK<br/>";
                           }
                       }
                       else {
                           $errorMsg .= "Tarball $filename already present<br/>\n";
                       }
                       chdir($installDestinationPath);
                       //pretty_print_r($installDestinationPath);
                       if(preg_match("/(.tgz$)|(.tar.gz$)/",$filename)) {
                           $errorMsg .= "Archive is in gzip format<br/>";
                           $params = "-zxf";
                       }
                       else if(preg_match("/\.bz2$/",$filename)) {
                           $errorMsg .= "Archive is in bz2 format<br/>";
                           $params = "-jxf";
                       }
                       else {
                           $errorMsg .= "Unable to determine the archive format from the filemname<br/>";
                           return;
                       }
                       $errorMsg .= "Uncompressing : ";
                       $execRetval = self::execVerbose("tar $params ".$downloadPath . $filename, $output, $return, $errorMsg);
                       if($execRetval==0) {
                           $errorMsg .= "OK<br/>";
                       }
                       else {
                           $errorMsg .= "<em style=\"color:red\">Decompression failed</em><br/>";
                           return;
                       }
                       break;
                   default:
                       $errorMsg .= "Unknown install method $installMethod<br/>";
               }//End switch
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
