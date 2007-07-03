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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/Cache.php');
require_once ('classes/HtmlSafe.php');
require_once ('classes/LocaleList.php');

/**
 * Représente un Langstring en particulier, ne créez pas un objet langstrings
 * si vous n'en avez pas spécifiquement besoin
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class Langstring extends Content {
    protected $allowed_html_tags;
    /**
     * Constructor
     *
     * @param string $content_id Content id
     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);
        $db = AbstractDb::getObject();
	    /**
    	 * HTML allowed to be used
     	*/
   		$this->allowed_html_tags = "<a><br><b><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><strong><u><ul><li><br/><hr><div></div>";
        $this->mBd = &$db;
    }

    /** Indicate that the content is suitable to store plain text.
     * @return true or false */
    public function isTextualContent() {
        return true;
    }

    /**
     * Returns the first available string in the user's language, faling that in the
     * same major language (first part of the locale), failing that the first available
     * string
     * @param bool verbose : Should the function verbose when a string is empty ?
     * @return UTF-8 string
     */
    public function getString($verbose = true) {
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
            $_cachedData = $_cacheLanguage->getCachedData();
            if ($_cachedData) {
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
            $sql .= " as score FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '{$this->id}' AND value!='' ORDER BY score LIMIT 1";
            $this->mBd->execSqlUniqueRes($sql, $row, false);

            if ($row == null) {
                if($verbose == true)
                $retval = sprintf(_("(Empty %s)"), get_class($this));
                else
                $retval = "";
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
     */
    public function addString($string, $locale=null, $allow_empty_string = false) {
        // Init values
        $retval = false;
        $id = 'NULL';
        $idSQL = $id;

        if ($locale) {
            $language = new Locale($locale);
            $id = $language->GetId();
            $idSQL = "'" . $id . "'";
        }

        if ($allow_empty_string || ($string != null && $string != '')) {
            $string = $this->mBd->escapeString($string);
            $this->mBd->execSqlUpdate("INSERT INTO content_langstring_entries (langstring_entries_id, langstrings_id, locales_id, value) VALUES ('" . get_guid() . "', '$this->id', $idSQL , '$string')", FALSE);

            // Create new cache object.
            $_cache = new Cache('langstrings_' . $this->id . '_substring_' . $id . '_string', $this->id);

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
     * This method contains the interface to add an additional element to a
     * content object.  (For example, a new string in a Langstring)
     * It is called when getNewContentUI has only a single possible object type.
     * It may also be called by the object getAdminUI to avoid code duplication.
     *
     * @param string $contentId      The id of the (possibly not yet created) content object.
     *
     * @param string $userData=null Array of contextual data optionally sent by displayAdminUI(),
     *  and only understood by the class (or subclasses) where getNewUI() is defined.
     *  The function must still function if none of it is present.
     *
     * @return HTML markup or false.  False means that this object does not support this interface.
     */
    public static function getNewUI($contentId, $userData=null) {
        $html = '';
        !empty($userData['excludeArray'])?$excludeArray=$userData['excludeArray']:$excludeArray=null;
        !empty($userData['typeInterface'])?$typeInterface=$userData['typeInterface']:$typeInterface='LARGE';
        $html .= "<div class='admin_element_data'>\n";
        $locale = LocaleList :: GetDefault();
        $html .= _("Language") . ": " . LocaleList::GenererFormSelect($locale, "langstrings_" . $contentId . "_substring_new_language", null, TRUE, $excludeArray);
        $new_substring_name = "langstrings_" . $contentId . "_substring_new_string";

        if ($typeInterface == 'LARGE') {
            $html .= "<textarea name='$new_substring_name' class='textarea' cols='60' rows='3'></textarea>\n";
        } else {
            $html .= "<input type='text' name='$new_substring_name' class='input_text' size='80' value=''>\n";
        }

        $html .= "</div>\n";

        $html .= "<div class='admin_element_tools'>\n";
        $new_substring_submit_name = "langstrings_" . $contentId . "_add_new_entry";
        $html .= "<input type='submit' class='submit' name='$new_substring_submit_name' value='" . _("Add new string") . "'>";
        $html .= "</div>\n";
        return $html;
    }

    /**
     *
     *
     * @param string $contentId  The id of the (possibly not yet created) content object.
     *
     * @param string $checkOnly  If true, only check if there is data to be processed.
     * 	Will be used to decide if an object is to be created.  If there is
     * processNewUI will typically be called again with $chechOnly=false
     *
     * @return true if there was data to be processed, false otherwise

     */
    public static function processNewUI($contentId, $checkOnly=false) {
        $retval = false;
        //Ajouter nouvelles chaîne(s) si champ non vide ou si l'usager a appuyé sur le bouton ajouter
        $new_substring_name = "langstrings_" . $contentId . "_substring_new_string";
        $new_substring_submit_name = "langstrings_" . $contentId . "_add_new_entry";

        if ((isset ($_REQUEST[$new_substring_submit_name]) && $_REQUEST[$new_substring_submit_name] == true) || !empty ($_REQUEST[$new_substring_name])) {
            $retval = true;
            $generateur_form_select = new FormSelectGenerator();
            $language = $generateur_form_select->getResult("langstrings_" . $contentId . "_substring_new_language", null);
            if (empty ($language)) {
                $language = null;
            }
            if(!$checkOnly) {
                $langstring = self::getObject($contentId);
                $langstring->addString($_REQUEST[$new_substring_name], $language, true);
            }
        }
        return $retval;
    }

    /**
     * Retreives the admin interface of this object. Anything that overrides
     * this method should call the parent method with it's output at the END of
     * processing.
     * @param string $subclass_admin_interface HTML content of the interface
     * element of a children.
     * @param string $type_interface SIMPLE pour éditer un seul champ, COMPLETE
     *                               pour voir toutes les chaînes, LARGE pour
     *                               avoir un textarea.
     * @return string The HTML fragment for this interface.
     */
    public function getAdminUI($subclass_admin_interface = null, $title = null, $type_interface = "LARGE") {
        // Init values.
        $html = '';
        $html .= $subclass_admin_interface;
        $result = "";
        //$variantsCounter = 0;
        //$_hideNewContent = false;
        if (!empty ($this->allowed_html_tags)) {
            $html .= "<div class='admin_section_hint'>" . _("Only these HTML tags are allowed : ") . htmlentities($this->allowed_html_tags) . "</div>";
        }
        $html .= "<ul class='admin_element_list'>\n";
        $sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
        $this->mBd->execSql($sql, $result, false); //echo "type_interface: $type_interface\n";

        $exclude_array = array ();
        if ($result != null) {
            while (list ($key, $value) = each($result)) {
                $exclude_array[$value['locales_id']] = $value['locales_id'];
                //                The next lines are a preview of a new suggested input mode
                //                ==========================================================
                //
                //                // Increase variants counter
                //                $variantsCounter++;
                //
                //                // Hide new content input
                //                $_hideNewContent = true;
                //
                //                $html .= "<li class='admin_element_item_container'>\n";
                //                $html .= "<div class='admin_element_data'>\n";
                //
                //                if ($type_interface == 'LARGE') {
                //                    $html .= "<textarea name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' cols='60' rows='3'>".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."</textarea>\n";
                //                } else {
                //                    $html .= "<input type='text' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' size='44' value='".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."'>\n";
                //                }
                //
                //                $html .= "<div class='admin_element_data' id='langstrings_".$this->id."_substring_$value[langstring_entries_id]_language_section' style='display: none;'>\n";
                //                $html .= LocaleList::GenererFormSelect("$value[locales_id]", "langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", null, TRUE);
                //                $html .= "</div>\n";
                //
                //                $html .= "</div>\n";
                //                $html .= "<div class='admin_element_tools'>\n";
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

                $html .= "<li class='admin_element_item_container'>\n";
                $html .= "<div class='admin_element_data'>\n";

                $html .= _("Language") . ": " . LocaleList::GenererFormSelect("$value[locales_id]", "langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_language", null, TRUE);

                if ($type_interface == 'LARGE') {
                    $html .= "<textarea name='langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_string' class='textarea' cols='60' rows='3'>" . htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8') . "</textarea>\n";
                } else {
                    $html .= "<input type='text' size='80' class='input_text' name='langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_string' value='" . htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8') . "'>\n";
                }

                $html .= "</div>\n";
                $html .= "<div class='admin_element_tools'>\n";
                $name = "langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_erase";
                $html .= "<input type='submit' class='submit' name='$name' value='" . _("Delete string") . "'>";
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
        //        $html .= "<li class='admin_element_item_container' id='langstrings_".$this->id."_add_new_entry_view'" . ($_hideNewContent ? " style='display: none;'" : "") . ">\n";
        //        $html .= "<div class='admin_element_data'>\n";
        //
        //        $new_substring_name = "langstrings_".$this->id."_substring_new_string";
        //
        //        if ($type_interface == 'LARGE') {
        //            $html .= "<textarea name='$new_substring_name' cols='60' rows='3'></textarea>\n";
        //        } else {
        //            $html .= "<input type='text' name='$new_substring_name' size='44' value=''>\n";
        //        }
        //
        //        $html .= "<div class='admin_element_data' id='langstrings_".$this->id."_substring_new_language_section'>\n";
        //        $html .= "<img src='" . BASE_SSL_PATH . "images/icons/language.gif' id='langstrings_".$this->id."_substring_new_language_section_image' class='admin_section_button' alt='"._("Choose language")."' title='"._("Choose language")."'>";
        //        $html .= LocaleList::GenererFormSelect($locale, "langstrings_".$this->id."_substring_new_language", null, TRUE);
        //        $html .= "</div>\n";
        //
        //        $html .= "</div>\n";
        //        $html .= "<div class='admin_element_tools'>\n";
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
        $html .= "<li class='admin_element_item_container'>\n";
        $userData['excludeArray'] = $exclude_array;
        $userData['typeInterface'] = $type_interface;
        $html .= self::getNewUI($this->id, $userData);
        $html .= "</li>\n";
        $html .= "</ul>\n";

        return parent :: getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for Langstring
     *
     * @return void
     */
    public function processAdminUI() {
        // Init values.
        $result = null;

        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();
            $generateur_form_select = new FormSelectGenerator();
            $sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id'";
            $this->mBd->execSql($sql, $result, false);

            if ($result != null) {
                while (list ($key, $value) = each($result)) {
                    $language = $generateur_form_select->getResult("langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_language", null);

                    if (empty ($language)) {
                        $language = '';
                        $languageSQL = 'NULL';
                    } else {
                        $languageSQL = "'" . $language . "'";
                    }

                    if (!empty ($_REQUEST["langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_erase"]) && $_REQUEST["langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_erase"] == true) {
                        $this->mBd->execSqlUpdate("DELETE FROM content_langstring_entries WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);

                        // Create new cache object.
                        $_cache = new Cache('langstrings_' . $this->id . '_substring_' . $language . '_string', $this->id);

                        // Check if caching has been enabled.
                        if ($_cache->isCachingEnabled) {
                            // Remove old cached data.
                            $_cache->eraseCachedData();
                        }
                    } else {
                        // Strip HTML tags !
                        $string = $_REQUEST["langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_string"];
                        $string = $this->mBd->escapeString(strip_tags($string, $this->allowed_html_tags));

                        // If PEAR::HTML_Safe is available strips down all potentially dangerous content
                        $_HtmlSafe = new HtmlSafe();

                        if ($_HtmlSafe->isHtmlSafeEnabled) {
                            // Add "embed" and "object" to the default set of dangerous tags
                            $_HtmlSafe->setDeleteTags(array (
                            "embed",
                            "object"
                            ), true);

                            // Strip HTML
                            $string = $_HtmlSafe->parseHtml($string);
                        }
                        if ($value['value'] != $string || $language!=$value['locales_id']) {
                            $this->mBd->execSqlUpdate("UPDATE content_langstring_entries SET locales_id = $languageSQL , value = '$string' WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);
                            $this->touch();
                            // Create new cache object.
                            $_cache = new Cache('langstrings_' . $this->id . '_substring_' . $language . '_string', $this->id);

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
            }

            //Nouvelles chaîne(s)
            self::processNewUI($this->id, false);


        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * Anything that overrides this method should call the parent method with
     * it's output at the END of processing.
     * @return string The HTML fragment for this interface
     */
    public function getUserUI() {
        // Init values
        $html = '';
        $html .= $this->getString();
        /* Handle hyperlink clicktrough logging */
        $html = $this->replaceHyperLinks($html);
        $this->setUserUIMainDisplayContent($html);
        return parent :: getUserUI();
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

     */
    private function refresh() {
        $this->__construct($this->id);
    }

    /**
     * Deletes a Langstring object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     * @internal Persistent content will not be deleted
     */
    public function delete(& $errmsg) {
        // Init values.
        $_retval = false;

        if ($this->isPersistent()) {
            $errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
        } else {
            $db = AbstractDb :: getObject();

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