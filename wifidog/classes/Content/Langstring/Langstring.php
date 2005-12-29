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
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire, Technologies Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once('classes/Cache.php');
require_once('classes/LocaleList.php');


/**
 * Représente un Langstring en particulier, ne créez pas un objet langstrings
 * si vous n'en avez pas spécifiquement besoin
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire, Technologies Coeus inc.
 */
class Langstring extends Content {
    /**
     * HTML allowed to be used
     */
    const ALLOWED_HTML_TAGS = "<a><br><b><h1><h2><h3><h4><i><img><li><ol><p><strong><u><ul><li>";

    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @access public
     */
    public function __construct($content_id)
    {
        // Define globals
        global $db;

        parent::__construct($content_id);
        $this->mBd = &$db;
    }

    /**
     * Retourne la première chaîne disponible dans la langue par défaut de
     * l'usager (si disponible), sinon dans la même langue majeure, sinon
     * la première chaîne disponible
     *
     * @return string Chaîne UTF-8 retournée
     *
     * @access public
     */
    public function getString()
    {
        // Init values
        $retval = null;
        $row = null;
        $_useCache = false;
        $_cachedData = null;

        // Create new cache objects
        $_cacheLanguage = new Cache('langstrings_' . $this->id . '_substring_' . substr(Locale :: getCurrentLocale()->getId(), 0, 2) . '_string', $this->id);
        $_cache = new Cache('langstrings_' . $this->id . '_substring__string', $this->id);

        // Check if caching has been enabled.
        if ($_cacheLanguage->isCachingEnabled) {
            if ($_cachedData = $_cacheLanguage->getCachedData()) {
                // Return cached data.
                $_useCache = true;
                $retval = $_cachedData;
            } else {
                // Language specific cached data has not been found.
                // Try to get language independent cached data.
                if ($_cachedData = $_cache->getCachedData()) {
                    // Return cached data.
                    $_useCache = true;
                    $retval = $_cachedData;
                }
            }
        }

        if (!$_useCache) {
            //Get user's prefered language
            $sql = "SELECT value, locales_id, \n";
            $sql .= Locale :: getSqlCaseStringSelect(Locale :: getCurrentLocale()->getId());
            $sql .= " as score FROM langstring_entries WHERE langstring_entries.langstrings_id = '{$this->id}' AND value!='' ORDER BY score LIMIT 1";
            $this->mBd->execSqlUniqueRes($sql, $row, false);

            if ($row == null) {
                $retval = "(Langstring vide)";
            } else {
                $retval = $row['value'];

                // Check if caching has been enabled.
                if ($_cache->isCachingEnabled) {
                    // Save data into cache, because it wasn't saved into cache before.
                    $_cache->saveCachedData($retval);
                }
            }
        }

        return $retval;
    }

    /**
     * Ajoute une chaîne de caractère au Langstring
     *
     * @param string $string             La chaîne de caractère à ajouter.  Si la chaîne
     *                                   est vide ('') ou null, la fonction retourne sans
     *                                   toucher à la base de donnée
     * @param string $locale             La langue régionale de la chaîne ajoutée, exemple:
     *                                   'fr_CA', peut être NULL
     * @param bool   $allow_empty_string Allow to store an empty string
     *
     * @return bool True si une chaîne a été ajoutée à la base de donnée,
     * false autrement.
     *
     * @access public
     */
    public function addString($string, $locale, $allow_empty_string = false)
    {
        // Init values
        $retval = false;
        $id = 'NULL';
        $idSQL = $id;

        if ($locale) {
            $language = new Locale($locale);
            $id = $language->GetId();
            $idSQL = "'".$id."'";
        }

        if ($allow_empty_string || ($string != null && $string != '')) {
            $string = $this->mBd->escapeString($string);
            $this->mBd->execSqlUpdate("INSERT INTO langstring_entries (langstring_entries_id, langstrings_id, locales_id, value) VALUES ('".get_guid()."', '$this->id', $idSQL , '$string')", FALSE);

            // Create new cache object.
            $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $id . '_string', $this->id);

            // Check if caching has been enabled.
            if ($_cache->isCachingEnabled) {
                // Remove old cached data.
                $_cache->eraseCachedData();

                // Save data into cache.
                $_cache->saveCachedData($string);
            }

            $retval = true;
        }

        return $retval;
    }

    /**
     * Updates the string associated with the locale
     *
     * @param string $string La chaîne de caractère à ajouter. Si la chaîne
     *                       est vide ('') ou null, la fonction retourne
     *                       sans toucher à la base de donnée
     * @param string $locale La langue régionale de la chaîne ajoutée,
     *                       exemple: 'fr_CA', peut être NULL
     *
     * @return bool True si une chaîne a été ajoutée à la base de donnée, false autrement.
     *
     * @access public
     */
    public function UpdateString($string, $locale)
    {
        // Init values
        $retval = false;
        $id = 'NULL';
        $row = null;

        if ($locale) {
            $language = new Locale($locale);
            $id = $language->GetId();
            $idSQL = "'" . $id . "'";
        }

        if ($string != null && $string != '') {
            $string = $this->mBd->escapeString($string);
            // If the update returns 0 ( no update ), try inserting the record
            $this->mBd->execSqlUniqueRes("SELECT * FROM langstring_entries WHERE locales_id = $idSQL AND langstrings_id = '$this->id'", $row, false);

            if ($row != null) {
                $this->mBd->execSqlUpdate("UPDATE langstring_entries SET value = '$string' WHERE langstrings_id = '$this->id' AND locales_id = $idSQL", false);

                // Create new cache object.
                $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $id . '_string', $this->id);

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

            $retval = true;
        }

        return $retval;
    }

    /**
     * Affiche l'interface d'administration de l'objet
     *
     * @param string $type_interface SIMPLE pour éditer un seul champ, COMPLETE
     *                               pour voir toutes les chaînes, LARGE pour
     *                               avoir un textarea.
     * @param int    $num_nouveau    Nombre de champ à afficher pour entrer de
     *                               nouvelles chaîne en une seule opération
     *
     * @return string HTML code of administration interface
     *
     * @access public
     */
    public function getAdminUI($type_interface = "LARGE", $num_nouveau = 1)
    {
        // Init values.
        $html = '';
        $result = "";
        //$variantsCounter = 0;
        //$_hideNewContent = false;

        $html .= "<div class='admin_class'>Langstring (".get_class($this)." instance)</div>\n";
        $html .= "<div class='admin_section_container'>\n";

        $html .= _("Only these HTML tags are allowed : ").htmlentities(self :: ALLOWED_HTML_TAGS);

        $liste_languages = new LocaleList();
        $sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
        $this->mBd->execSql($sql, $result, FALSE); //echo "type_interface: $type_interface\n";

        $html .= "<ul class='admin_section_list'>\n";

        if ($result != null) {
            while (list ($key, $value) = each($result)) {
//                The next lines are a preview of a new suggested input mode
//                ==========================================================
//
//                // Increase variants counter
//                $variantsCounter++;
//
//                // Hide new content input
//                $_hideNewContent = true;
//
//                $html .= "<li class='admin_section_list_item'>\n";
//                $html .= "<div class='admin_section_data'>\n";
//
//                if ($type_interface == 'LARGE') {
//                    $html .= "<textarea name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' cols='60' rows='3'>".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."</textarea>\n";
//                } else {
//                    $html .= "<input type='text' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' size='44' value='".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."'>\n";
//                }
//
//                $html .= "<div class='admin_section_data' id='langstrings_".$this->id."_substring_$value[langstring_entries_id]_language_section' style='display: none;'>\n";
//                $html .= $liste_languages->GenererFormSelect("$value[locales_id]", "langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
//                $html .= "</div>\n";
//
//                $html .= "</div>\n";
//                $html .= "<div class='admin_section_tools'>\n";
//                $name = "langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase";
//
//                // Choose language button
//                $html .= "<a href='javascript:showHideView(\"langstrings_".$this->id."_substring_$value[langstring_entries_id]_language_section\", \"langstrings_".$this->id."_substring_$value[langstring_entries_id]_language_section_image\");'><img src='" . BASE_SSL_PATH . "images/icons/language.gif' id='langstrings_".$this->id."_substring_$value[langstring_entries_id]_language_section_image' class='admin_section_button' alt='"._("Choose language")."' title='"._("Choose language")."'></a>";
//
//                // Add string button
//                if (count($result) == $variantsCounter) {
//                    // This is the last string variant - show "add string" button.
//                    $html .= "<a href='javascript:showHideView(\"langstrings_".$this->id."_add_new_entry_view\", \"langstrings_".$this->id."_add_new_entry_image\");'><img src='" . BASE_SSL_PATH . "images/icons/add.gif' id='langstrings_".$this->id."_add_new_entry_image' class='admin_section_button' alt='"._("Add new string")."' title='"._("Add new string")."'></a>";
//                } else {
//                    $html .= "<img src='" . BASE_SSL_PATH . "images/icons/add.gif' id='langstrings_".$this->id."_add_new_entry_image' class='admin_section_button_disabled' alt='"._("Add new string")."' title='"._("Add new string")."'>";
//                }
//
//                // Delete string button
//                $html .= "<input type='image' name='$name' class='admin_section_button' src='" . BASE_SSL_PATH . "images/icons/delete.gif' alt='"._("Delete string")."' title='"._("Delete string")."'>";
//
//                $html .= "</div>\n";
//                $html .= "</li>\n";

                $html .= "<li class='admin_section_list_item'>\n";
                $html .= "<div class='admin_section_data'>\n";

                $html .= $liste_languages->GenererFormSelect("$value[locales_id]", "langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin', TRUE);

                if ($type_interface == 'LARGE') {
                    $html .= "<textarea name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' cols='60' rows='3'>".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."</textarea>\n";
                } else {
                    $html .= "<input type='text' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' size='44' value='".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."'>\n";
                }

                $html .= "</div>\n";
                $html .= "<div class='admin_section_tools'>\n";

                $name = "langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase";

                $html .= "<input type='submit' name='$name' value='"._("Delete string")."'>";
                $html .= "</div>\n";
                $html .= "</li>\n";
            }
        }

//        The next lines are a preview of a new suggested input mode
//        ==========================================================
//
//        //Nouvelles chaîne
//        $locale = LocaleList :: GetDefault();
//
//        $html .= "<li class='admin_section_list_item' id='langstrings_".$this->id."_add_new_entry_view'" . ($_hideNewContent ? " style='display: none;'" : "") . ">\n";
//        $html .= "<div class='admin_section_data'>\n";
//
//        $new_substring_name = "langstrings_".$this->id."_substring_new_string";
//
//        if ($type_interface == 'LARGE') {
//            $html .= "<textarea name='$new_substring_name' cols='60' rows='3'></textarea>\n";
//        } else {
//            $html .= "<input type='text' name='$new_substring_name' size='44' value=''>\n";
//        }
//
//        $html .= "<div class='admin_section_data' id='langstrings_".$this->id."_substring_new_language_section'>\n";
//        $html .= "<img src='" . BASE_SSL_PATH . "images/icons/language.gif' id='langstrings_".$this->id."_substring_new_language_section_image' class='admin_section_button' alt='"._("Choose language")."' title='"._("Choose language")."'>";
//        $html .= $liste_languages->GenererFormSelect($locale, "langstrings_".$this->id."_substring_new_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
//        $html .= "</div>\n";
//
//        $html .= "</div>\n";
//        $html .= "<div class='admin_section_tools'>\n";
//
//        $new_substring_submit_name = "langstrings_".$this->id."_add_new_entry";
//
//        // Add string button
//        $html .= "<input type='image' name='$new_substring_submit_name' class='admin_section_button' src='" . BASE_SSL_PATH . "images/icons/add.gif' alt='"._("Add new string")."' title='"._("Add new string")."'>";
//
//        $html .= "</div>\n";
//        $html .= "</li>\n";
//
//        $html .= "</ul>\n";
//        $html .= "</div>\n";

        //Nouvelles chaîne
        $locale = LocaleList :: GetDefault();
        $html .= "<li class='admin_section_list_item'>\n";
        $html .= "<div class='admin_section_data'>\n";

        $html .= $liste_languages->GenererFormSelect($locale, "langstrings_".$this->id."_substring_new_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
        $new_substring_name = "langstrings_".$this->id."_substring_new_string";

        if ($type_interface == 'LARGE') {
            $html .= "<textarea name='$new_substring_name' cols='60' rows='3'></textarea>\n";
        } else {
            $html .= "<input type='text' name='$new_substring_name' size='44' value=''>\n";
        }

        $html .= "</div>\n";
        $html .= "<div class='admin_section_tools'>\n";

        $new_substring_submit_name = "langstrings_".$this->id."_add_new_entry";

        $html .= "<input type='submit' name='$new_substring_submit_name' value='"._("Add new string")."'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "</ul>\n";
        $html .= "</div>\n";

        return parent :: getAdminUI($html);
    }

    /**
     * Processes the input of the administration interface for Langstring
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        // Init values.
        $result = null;

        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();
            $generateur_form_select = new FormSelectGenerator();
            $sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id'";
            $this->mBd->execSql($sql, $result, FALSE);

            if ($result != null) {
                while (list ($key, $value) = each($result)) {
                    $language = $generateur_form_select->getResult("langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin');

                    if (empty ($language)) {
                        $language = '';
                        $languageSQL = 'NULL';
                    } else {
                        $languageSQL = "'".$language."'";
                    }

                    if (!empty ($_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase"]) && $_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase"] == true) {
                        $this->mBd->execSqlUpdate("DELETE FROM langstring_entries WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);

                        // Create new cache object.
                        $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $language . '_string', $this->id);

                        // Check if caching has been enabled.
                        if ($_cache->isCachingEnabled) {
                            // Remove old cached data.
                            $_cache->eraseCachedData();
                        }
                    } else {
                        // Strip HTML tags !
                        $string = $_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_string"];
                        $string = $this->mBd->escapeString(strip_tags($string, self :: ALLOWED_HTML_TAGS));
                        $this->mBd->execSqlUpdate("UPDATE langstring_entries SET locales_id = $languageSQL , value = '$string' WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);

                        // Create new cache object.
                        $_cache = new Cache('langstrings_' . $this->id . '_substring_' .  $language . '_string', $this->id);

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

            //Ajouter nouvelles chaîne(s) si champ non vide ou si l'usager a appuyé sur le bouton ajouter
            $new_substring_name = "langstrings_".$this->id."_substring_new_string";
            $new_substring_submit_name = "langstrings_".$this->id."_add_new_entry";

            if ((isset ($_REQUEST[$new_substring_submit_name]) && $_REQUEST[$new_substring_submit_name] == true) || !empty ($_REQUEST[$new_substring_name])) {
                $language = $generateur_form_select->getResult("langstrings_".$this->id."_substring_new_language", 'Langstring::AfficherInterfaceAdmin');

                if (empty ($language)) {
                    $language = null;
                }

                $this->addString($_REQUEST[$new_substring_name], $language, true);
            }
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * Anything that overrides this method should call the parent method with
     * it's output at the END of processing.
     *
     * @param string $subclass_admin_interface HTML content of the interface
     *                                         element of a children
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI($subclass_user_interface = null)
    {
        // Init values
        $html = '';

        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>Langstring (".get_class($this)." instance)</div>\n";
        $html .= "<div class='langstring'>\n";
        $html .= $this->getString();
        $html .= $subclass_user_interface;
        $html .= "</div>\n";
        $html .= "</div>\n";

        return parent::getUserUI($html);
    }

    /**
     * Reloads the object from the database.
     *
     * Should normally be called after a set operation.
     *
     * This function is private because calling it from a subclass will call
     * the constructor from the wrong scope
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
     * Deletes a Langstring object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     *
     * @access public
     * @internal Persistent content will not be deleted
     */
    public function delete(&$errmsg)
    {
        // Init values.
        $_retval = false;

        if ($this->isPersistent()) {
            $errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
        } else {
            global $db;

            if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
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

?>
