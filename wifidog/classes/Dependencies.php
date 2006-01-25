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
	private static $_components = array ("ImageGraph" => array ("name" => "PEAR::Image_Graph", "file" => "Image/Graph.php", "localLib" => false), "Phlickr" => array ("name" => "PEAR::Phlickr", "file" => "Phlickr/Api.php", "localLib" => false), "Cache" => array ("name" => "PEAR::Cache_Lite", "file" => "Cache/Lite.php", "localLib" => false), "FCKeditor" => array ("name" => "FCKeditor", "file" => "lib/FCKeditor/fckeditor.php", "localLib" => true), "Smarty" => array ("name" => "Smarty", "file" => "lib/smarty/Smarty.class.php", "localLib" => true));

	/**
	 * Checks if a file exists, including checking in the include path
	 *
	 * This function comes from the Aidan's repository
	 * Thanks : http://aidan.dotgeek.org/repos/ v/function.file_exists_incpath.php
	 *
	 * Here's an example of how to use the function:
	 * <code>
	 * Dependencies::file_exists_incpath($path_to_file);
	 * </code>
	 *
	 * @param string $file Path or name of a file
	 *
	 * @return boolean Returns whether the file has been found or not.
	 *
	 * @access public
	 * @static
	 */
	public static function file_exists_incpath($file)
	{
		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as $path)
		{
			$fullpath = $path.DIRECTORY_SEPARATOR.$file;
			if (file_exists($fullpath))
				return $fullpath;
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
	 * @access public
	 * @static
	 */
	static public function check($component, & $errmsg)
	{
		// Init values
		$_returnValue = false;

		// Check, if the requested component can be found.
		if (isset (self :: $_components[$component]))
		{
			if (self :: $_components[$component]["localLib"])
			{
                $filepath = WIFIDOG_ABS_FILE_PATH.self :: $_components[$component]["file"]; 
				if (file_exists($filepath))
				{
					// The component has been found.
					$_returnValue = true;
				}
				else
				{
					// The component has NOT been found. Return error message.
					require_once ('Locale.php');

					$errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self :: $_components[$component]["name"], $filepath);
				}
			}
			else
			{
				// We need to use a custom file_exists to also check in the include path
				if (self :: file_exists_incpath(self :: $_components[$component]["file"]))
				{
					// The component has been found.
					$_returnValue = true;
				}
				else
				{
					// The component has NOT been found. Return error message.
					require_once ('Locale.php');
                    $errmsg = sprintf(_("Component %s is not installed (not found in %s)"), self :: $_components[$component]["name"], get_include_path());
                    
				}
			}
		}
		else
		{
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