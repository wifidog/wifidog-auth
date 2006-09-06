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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: MainUI.php 1030 2006-05-09 20:01:17Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Theme packs contain stylesheets and graphical elements to customize the
 * general look of the system.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 */
class ThemePack {
    /**
     * ID of ThemePack
     *

     */
    private $_id;

    /**
     * Name of ThemePack
     *

     */
    private $_name;

    /**
     * Description of ThemePack
     *

     */
    private $_description;

    /**
     * Constructor
     *
     * @param string $themePackId The id of the theme pack (actually, the
     *                            folder name without the path)
     *
     * @return void

     */
    private function __construct($themePackId) {
        $handle = @ opendir(WIFIDOG_ABS_FILE_PATH . NETWORK_THEME_PACKS_DIR . $themePackId.'/');

        if (!$handle) {
            throw new exception(sprintf(_("Theme pack %s cannot be found in %s"), $themePackId, WIFIDOG_ABS_FILE_PATH . NETWORK_THEME_PACKS_DIR . $themePackId . '/'));
        }

        $this->_id = $themePackId;
        $this->_name = file_get_contents(WIFIDOG_ABS_FILE_PATH . NETWORK_THEME_PACKS_DIR . $this->_id . '/name.txt');

        if ($this->_name == null) {
            $this->_name = sprintf(_("%s (Theme did not include a name.txt file)"), $this->_id);
        }

        $this->_description = file_get_contents(WIFIDOG_ABS_FILE_PATH . NETWORK_THEME_PACKS_DIR . $this->_id . '/description.txt');

        if ($this->_description == null) {
            $this->_description = sprintf(_("%s (Theme did not include a description.txt file)"), $this->_name);
        }
    }

    /**
     * Get an interface to pick a theme pack
     *
     * If there is only one network available, no interface is actually shown
     *
     * @param string $userPrefix           An identifier provided by the
     *                                     programmer to recognise it's
     *                                     generated html form
     * @param object $preSelectedThemePack Theme object: The theme to be pre-
     *                                     selected in the form object
     *
     * @return string HTML markup

     */
    public static function getSelectUI($userPrefix, $preSelectedThemePack = null) {
        $html = '';
        $name = $userPrefix;
        $html .= _("Theme pack:")." \n";

        if ($preSelectedThemePack) {
            $selected_id = $preSelectedThemePack->getId();
        } else {
            $selected_id = null;
        }

        if ($handle = @ opendir(WIFIDOG_ABS_FILE_PATH.NETWORK_THEME_PACKS_DIR)) {
            $tab = array ();
            $i = 0;

            while (false !== ($directory = readdir($handle))) {
                if ($directory != '.' && $directory != '..' && $directory != '.svn' && is_dir(WIFIDOG_ABS_FILE_PATH.NETWORK_THEME_PACKS_DIR.$directory.'/')) {
                    $theme_pack = new self($directory);
                    $tab[$i][0] = $theme_pack->getId();
                    $tab[$i][1] = $theme_pack->getName();
                    $tab[$i][2] = $theme_pack->getDescription();
                    $i ++;
                }
            }
            closedir($handle);
        } else {
            throw new exception(_("Unable to open the network theme packs directory"));
        }

        //pretty_print_r($tab);
        if (count($tab) > 0) {
            $html .= FormSelectGenerator :: generateFromArray($tab, $selected_id, $name, null, true);
        } else {
            $html .= sprintf(_("No network theme packs available in %s"), WIFIDOG_ABS_FILE_PATH.NETWORK_THEME_PACKS_DIR);
            $html .= "<input type='hidden' name='$name' value=''>";
        }

        return $html;
    }

    /**
     * Get an instance of the object
     *
     * @param object $id The object id
     *
     * @return object The Content object, or null if there was an error (an
     *                exception is also thrown)
     *

     */
    static public function getObject($id) {
        return new self($id);
    }

    /**
     * Retreives the id of the object
     *
     * @return string The id
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Retreives the name of the ThemePack
     *
     * @return string Name of ThemePack
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Retreives the description of the ThemePack
     *
     * @return string Description of ThemePack
     */
    public function getDescription() {
        return $this->_description;
    }

    /**
     * Retreives the url of this theme's stylesheet
     *
     * @return string URL of this theme's stylesheet
     */
    public function getStylesheetUrl() {
        return BASE_URL_PATH . NETWORK_THEME_PACKS_DIR . $this->_id . '/stylesheet.css';
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
