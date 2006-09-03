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
 * @subpackage ContentClasses
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 * @todo       Add CSS styles for editors.
 */

/**
 * Load required classes
 */
require_once('classes/Content/Langstring/Langstring.php');

/**
 * FCKeditor implementation
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 */
class HTMLeditor extends Content
{

    /**
     * HTML allowed to be used
     */
    const ALLOWED_HTML_TAGS = "<p><div><pre><address><h1><h2><h3><h4><h5><h6><br><b><strong><i><em><u><span><ol><ul><li><a><img><embed><table><tbody><thead><th><tr><td><hr>";

    /**
     * Defines if the FCKeditor library has been installed
     *
     * @var bool
     * @access private
     */
    private $_FCKeditorAvailable = false;

    /**
     * Constructor.
     *
     * @param int $content_id ID of content.
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);

        // Check FCKeditor support
        if (Dependencies::check("FCKeditor")) {
            // Define globals
            global $db;

            // Load FCKeditor class
            require_once('lib/FCKeditor/fckeditor.php');

            $this->mBd = &$db;

            $this->_FCKeditorAvailable = true;
        }
    }

    /**
     * Return string in the language requested by the user.
     *
     * @return string UTF-8 string of content.
     *
     * @access private
     */
    private function getString()
    {
        // Init values
        $_retval = null;
        $_row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects
        $_cacheLanguage = new Cache('langstrings_' . $this->id . '_substring_' . substr(Locale::getCurrentLocale()->getId(), 0, 2) . '_string', $this->id);
        $_cache = new Cache('langstrings_' . $this->id . '_substring__string', $this->id);

        // Check if caching has been enabled.
        if ($_cacheLanguage->isCachingEnabled) {
            $_cachedData = $_cacheLanguage->getCachedData();

            if ($_cachedData) {
                // Return cached data.
                $_useCache = true;
                $_retval = $_cachedData;
            } else {
                // Language specific cached data has not been found.
                // Try to get language independent cached data.
                $_cachedData = $_cache->getCachedData();

                if ($_cachedData) {
                    // Return cached data.
                    $_useCache = true;
                    $_retval = $_cachedData;
                }
            }
        }

        if (!$_useCache) {
            // Get string in the prefered language of the user
            $_sql = "SELECT value, locales_id, \n";
            $_sql .= Locale::getSqlCaseStringSelect(Locale::getCurrentLocale()->getId());
            $_sql .= " as score FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '{$this->id}' AND value!='' ORDER BY score LIMIT 1";
            $this->mBd->execSqlUniqueRes($_sql, $_row, false);

            if ($_row == null) {
                // String has not been found
                $_retval = "(Empty string)";
            } else {
                // String has been found
                $_retval = $_row['value'];

                // Check if caching has been enabled.
                if ($_cache->isCachingEnabled) {
                    // Save data into cache, because it wasn't saved into cache before.
                    $_cache->saveCachedData($_retval);
                }
            }
        }

        return $_retval;
    }

    /**
     * Adds the string associated with the locale.
     *
     * @param string $string             String to be added
     * @param string $locale             Locale of string (i.e. 'fr_CA') - can
     *                                   be NULL
     * @param bool   $allow_empty_string Defines if string may be empty
     *
     * @return bool True if string has been added, otherwise false.
     *
     * @access private
     */
    private function addString($string, $locale, $allow_empty_string = false)
    {
        // Init values
        $_retval = false;
        $_id = 'NULL';
        $_idSQL = $_id;

        if ($locale) {
            // Set locale of string
            $_language = new Locale($locale);

            $_id = $_language->GetId();
            $_idSQL = "'" . $_id . "'";
        }

        if ($allow_empty_string || ($string != null && $string != '')) {
            // Save string in database
            $string = $this->mBd->escapeString($string);
            $this->mBd->execSqlUpdate("INSERT INTO content_langstring_entries (langstring_entries_id, langstrings_id, locales_id, value) VALUES ('" . get_guid() . "', '$this->id', $_idSQL , '$string')", FALSE);

            // Create new cache object.
            $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $_id . '_string', $this->id);

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Remove old cached data.
                $_cache->eraseCachedData();

                // Save data into cache.
                $_cache->saveCachedData($string);
            }

            $_retval = true;
        }

        return $_retval;
    }

    /**
     * Updates the string associated with the locale.
     *
     * @param string $string String to be updated.
     * @param string $locale Locale of string (i.e. 'fr_CA') - can be NULL.
     *
     * @return bool True if string has been updated, otherwise false.
     *
     * @access private
     */
    private function UpdateString($string, $locale)
    {
        // Init values
        $_retval = false;
        $_id = 'NULL';
        $_row = null;

        if ($locale) {
            // Set locale of string
            $_language = new Locale($locale);

            $_id = $_language->GetId();
            $_idSQL = "'" . $_id . "'";
        }

        if ($string != null && $string != '') {
            $string = $this->mBd->escapeString($string);

            // If the update returns 0 (no update), try inserting the record
            $this->mBd->execSqlUniqueRes("SELECT * FROM content_langstring_entries WHERE locales_id = $_idSQL AND langstrings_id = '$this->id'", $_row, false);

            if ($_row != null) {
                $this->mBd->execSqlUpdate("UPDATE content_langstring_entries SET value = '$string' WHERE langstrings_id = '$this->id' AND locales_id = $_idSQL", false);

                // Create new cache object.
                $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $_id . '_string', $this->id);

                // Check if caching has been enabled.
                if ($_cache->isCachingEnabled) {
                    // Remove old cached data.
                    $_cache->eraseCachedData();

                    // Save data into cache.
                    $_cache->saveCachedData($string);
                }
            } else {
                $this->addString($string, $locale);
            }

            $_retval = true;
        }
        return $_retval;
    }

    /**
     * Shows the administration interface for HTMLeditor.
     *
     * @param string $type_interface SIMPLE for a small HTML editor, LARGE
     *                               for a larger HTML editor (default).
     * @param int    $num_nouveau    Number of new HTML editors to be created.
     *
     * @return string HTML code for the administration interface.
     *
     * @access public
     */
    public function getAdminUI($type_interface = 'LARGE')
    {
        if ($this->_FCKeditorAvailable) {
            // Init values
            $_result = null;
            $_html = '';
            $_languages = new LocaleList();
	 		$_html .= "<ul class='admin_element_list'>\n";

            $_html .= "<li class='admin_element_item_container content_html_editor'>\n";

            $_html .= "<ul class='admin_element_list'>\n";

            $_sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
            $this->mBd->execSql($_sql, $_result, FALSE);

            // Show existing content
            if ($_result != null) {
                while (list($_key, $_value) = each($_result)) {
                    $_html .= "<li class='admin_element_item_container'>\n";
                    $_html .= "<div class='admin_element_data'>\n";
                    $_html .= _("Language") . ": " . $_languages->GenererFormSelect($_value["locales_id"], "langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_language", 'Langstring::AfficherInterfaceAdmin', TRUE);

                    $_FCKeditor = new FCKeditor('langstrings_' . $this->id . '_substring_' . $_value["langstring_entries_id"] . '_string');
                    $_FCKeditor->BasePath = SYSTEM_PATH . "lib/FCKeditor/";
                    $_FCKeditor->Config["CustomConfigurationsPath"] = BASE_URL_PATH . "js/HTMLeditor.js";
                    $_FCKeditor->Config["AutoDetectLanguage"] = false;
                    $_FCKeditor->Config["DefaultLanguage"] = substr(Locale::getCurrentLocale()->getId(), 0, 2);
                    $_FCKeditor->Config["StylesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/css/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";
                    $_FCKeditor->Config["TemplatesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/templates/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";

                    $_FCKeditor->ToolbarSet = "WiFiDOG";

                    $_FCKeditor->Value = $_value['value'];

                    if ($type_interface == 'LARGE') {
                        $_FCKeditor->Height = 400;
                    } else {
                        $_FCKeditor->Height = 200;
                    }
                    $_FCKeditor->Width = 386;

                    $_html .= $_FCKeditor->CreateHtml();

                    $_html .= "</div>\n";
                    $_html .= "<div class='admin_element_tools'>\n";

                    $_name = "langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_erase";
                    $_html .= "<input type='submit' class='submit' name='$_name' value='" . _("Delete string") . "'>";

                    $_html .= "</div>\n";
                    $_html .= "</li>\n";
                }
            }

            // Editor for new content
            $_locale = LocaleList::GetDefault();

            $_html .= "<li class='admin_element_item_container'>\n";
            $_html .= "<div class='admin_element_data'>\n";

            $_html .= _("Language") . ": " . $_languages->GenererFormSelect($_locale, "langstrings_" . $this->id . "_substring_new_language", 'Langstring::AfficherInterfaceAdmin', TRUE);

            $_FCKeditor = new FCKeditor('langstrings_' . $this->id . '_substring_new_string');
            $_FCKeditor->BasePath = SYSTEM_PATH . "lib/FCKeditor/";
            $_FCKeditor->Config["CustomConfigurationsPath"] = BASE_URL_PATH . "js/HTMLeditor.js";
            $_FCKeditor->Config["AutoDetectLanguage"] = false;
            $_FCKeditor->Config["DefaultLanguage"] = substr(Locale::getCurrentLocale()->getId(), 0, 2);
            $_FCKeditor->Config["StylesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/css/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";
            $_FCKeditor->Config["TemplatesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/templates/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";
            $_FCKeditor->ToolbarSet = "WiFiDOG";

            $_FCKeditor->Value = "";

            if ($type_interface == 'LARGE') {
                $_FCKeditor->Height = 400;
            } else {
                $_FCKeditor->Height = 200;
            }
            $_FCKeditor->Width = 386;

            $_html .= $_FCKeditor->CreateHtml();

            $_html .= "</div>\n";
            $_html .= "<div class='admin_element_tools'>\n";

            $_html .= "<input type='submit' class='submit' name='langstrings_" . $this->id . "_add_new_entry' value='" . _("Add new string") . "'>";
            $_html .= "</div>\n";
            $_html .= "</li>\n";

            $_html .= "</ul>\n";
            $_html .= "</li>\n";
            $_html .= "</ul>\n";
        } else {
            $_html = '';
            $_html .= _("FCKeditor is not installed");
        }

        return parent :: getAdminUI($_html);
    }

    /**
     * Processes the input of the administration interface for HTMLeditor
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        // Check FCKeditor support
        if ($this->_FCKeditorAvailable) {
            // Init values
            $_result = null;

            if ($this->isOwner(User::getCurrentUser()) || User::getCurrentUser()->isSuperAdmin()) {
                parent::processAdminUI();

                $_form_select = new FormSelectGenerator();

                $_sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id'";
                $this->mBd->execSql($_sql, $_result, FALSE);

                if ($_result != null) {
                    while (list($_key, $_value) = each($_result)) {
                        $_language = $_form_select->getResult("langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_language", 'Langstring::AfficherInterfaceAdmin');

                        if (empty ($_language)) {
                            $_language = '';
                            $_languageSQL = 'NULL';
                        } else {
                            $_languageSQL = "'" . $_language . "'";
                        }

                        if (!empty ($_REQUEST["langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_erase"]) && $_REQUEST["langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_erase"] == true) {
                            $this->mBd->execSqlUpdate("DELETE FROM content_langstring_entries WHERE langstrings_id = '$this->id' AND langstring_entries_id='" . $_value["langstring_entries_id"] . "'", FALSE);

                            // Create new cache object.
                            $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $_language . '_string', $this->id);

                            // Check if caching has been enabled.
                            if ($_cache->isCachingEnabled) {
                                // Remove old cached data.
                                $_cache->eraseCachedData();
                            }
                        } else {
                            // Strip HTML tags!
                            $string = $_REQUEST["langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_string"];
                            $string = $this->mBd->escapeString(strip_tags($string, self::ALLOWED_HTML_TAGS));

                            // If PEAR::HTML_Safe is available strips down all potentially dangerous content
                            $_HtmlSafe = new HtmlSafe();

                            if ($_HtmlSafe->isHtmlSafeEnabled) {
                                $string = $_HtmlSafe->parseHtml($string);
                            }

                            $this->mBd->execSqlUpdate("UPDATE content_langstring_entries SET locales_id = " . $_languageSQL . " , value = '$string' WHERE langstrings_id = '$this->id' AND langstring_entries_id='" . $_value["langstring_entries_id"] . "'", FALSE);

                            // Create new cache object.
                            $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $_language . '_string', $this->id);

                            // Check if caching has been enabled.
                            if ($_cache->isCachingEnabled) {
                                // Remove old cached data.
                                $_cache->eraseCachedData();

                                // Save data into cache.
                                $_cache->saveCachedData($string);
                            }
                        }
                    }
                }

                $_new_substring_name = "langstrings_" . $this->id . "_substring_new_string";
                $_new_substring_submit_name = "langstrings_" . $this->id . "_add_new_entry";
                if ((isset ($_REQUEST[$_new_substring_submit_name]) && $_REQUEST[$_new_substring_submit_name] == true) || !empty ($_REQUEST[$_new_substring_name])) {
                    $_language = $_form_select->getResult("langstrings_" . $this->id . "_substring_new_language", 'Langstring::AfficherInterfaceAdmin');

                    if (empty($_language)) {
                        $_language = null;
                    }

                    $this->addString($_REQUEST[$_new_substring_name], $_language, true);
                }
            }
        }
    }

    /**
     * Retreives the user interface of this object. Anything that overrides
     * this method should call the parent method with it's output at the
     * END of processing.
     *
     * @param string $subclass_admin_interface HTML content of the interface
     *                                         element of a children.
     *
     * @return string The HTML fragment for this interface.
     *
     * @access public
     */
    public function getUserUI($subclass_user_interface = null)
    {
        // Init values
        $_html = "";

        $_html .= "<div class='user_ui_container ".get_class($this)."'>\n";
        $_html .= "<div class='langstring'>\n";

        // Check FCKeditor support
        if ($this->_FCKeditorAvailable) {
            $_html .= $this->getString();
        } else {
            $_html .= _("FCKeditor is not installed");
        }

        $_html .= $subclass_user_interface;
        $_html .= "</div>\n";
        $_html .= "</div>\n";

        return parent::getUserUI($_html);
    }

    /**
     * Reloads the object from the database. Should normally be called after
     * a set operation. This function is private because calling it from a
     * subclass will call the constructor from the wrong scope.
     *
     * @return void
     *
     * @access private
     */
    private function refresh()
    {
        $this->__construct($this->id);
    }

    /**
     * Deletes a HTMLeditor object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     *
     * @access public
     * @internal Persistent content will not be deleted
     */
    public function delete(& $errmsg) {
        // Init values.
        $_retval = false;

        if ($this->isPersistent()) {
            $errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
        } else {
            global $db;

            if ($this->isOwner(User::getCurrentUser()) || User::getCurrentUser()->isSuperAdmin()) {
                $_sql = "DELETE FROM content WHERE content_id='$this->id'";
                $db->execSqlUpdate($_sql, false);
                $_retval = true;

                // Create new cache object.
                $_cache = new Cache('all', $this->id);

                // Check if caching has been enabled.
                if ($_cache->isCachingEnabled) {
                    // Remove old cached data.
                    $_cache->eraseCachedGroupData();
                }
            } else {
                $errmsg = _("Access denied (not owner of content)");
            }
        }

        return $_retval;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
