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
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Max Horváth, Horvath Web Consulting
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
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Max Horváth, Horvath Web Consulting
 */
class HTMLeditor extends Langstring
{
    /**
     * Defines if the FCKeditor library has been installed
     *

     */
    protected $_FCKeditorAvailable = false;

    /**
     * Constructor.
     *
     * @param int $content_id ID of content.
     *
     * @return void     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);
		$this->allowed_html_tags = "<p><div><pre><address><h1><h2><h3><h4><h5><h6><br><b><strong><i><em><u><span><ol><ul><li><a><img><embed><table><tbody><thead><th><tr><td><hr>";

        // Check FCKeditor support
        if (Dependencies::check("FCKeditor")) {
            // Load FCKeditor class
            require_once('lib/FCKeditor/fckeditor.php');
            $this->_FCKeditorAvailable = true;
        }
    }

    /** Indicate that the content is suitable to store plain text.
     * @return true or false */
    public function isTextualContent() {
    		return false;
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
        $locale = LocaleList::GetDefault();
        !empty($userData['typeInterface'])?$typeInterface=$userData['typeInterface']:$typeInterface=null;
        $html .= "<div class='admin_element_data'>\n";

        $html .= _("Language") . ": " . LocaleList::GenererFormSelect($locale, "langstrings_" . $contentId . "_substring_new_language", null, TRUE);

		if (Dependencies::check("FCKeditor")) {
            // Load FCKeditor class
            require_once('lib/FCKeditor/fckeditor.php');

	        $_FCKeditor = new FCKeditor('langstrings_' . $contentId . '_substring_new_string');
	        $_FCKeditor->BasePath = SYSTEM_PATH . "lib/FCKeditor/";
	        $_FCKeditor->Config["CustomConfigurationsPath"] = BASE_URL_PATH . "js/HTMLeditor.js";
	        $_FCKeditor->Config["AutoDetectLanguage"] = false;
	        $_FCKeditor->Config["DefaultLanguage"] = substr(Locale::getCurrentLocale()->getId(), 0, 2);
	        $_FCKeditor->Config["StylesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/css/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";
	        $_FCKeditor->Config["TemplatesXmlPath"] = BASE_URL_PATH . "templates/HTMLeditor/templates/" . substr(Locale::getCurrentLocale()->getId(), 0, 2) . ".xml";
	        $_FCKeditor->ToolbarSet = "WiFiDOG";

	        $_FCKeditor->Value = "";

	        if ($typeInterface == 'LARGE') {
	            $_FCKeditor->Height = 400;
	        } else {
	            $_FCKeditor->Height = 200;
	        }
	        $_FCKeditor->Width = 386;

	        $html .= $_FCKeditor->CreateHtml();
		}
		else {
			$html .= "<textarea name='langstrings_{$contentId}_substring_new_string' class='textarea' cols='60' rows='3'></textarea>";
		}

        $html .= "</div>\n";
        $html .= "<div class='admin_element_tools'>\n";

        $html .= "<input type='submit' class='submit' name='langstrings_" . $contentId . "_add_new_entry' value='" . _("Add new string") . "'>";
        $html .= "</div>\n";
        return $html;
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
    public function getAdminUI($subclass_admin_interface = null, $title = null, $type_interface = "LARGE")
    {
        if ($this->_FCKeditorAvailable) {
            // Init values
            $_result = null;
            $html = '';
            $html .= $subclass_admin_interface;
	 		$html .= "<ul class='admin_element_list'>\n";

            $html .= "<li class='admin_element_item_container content_html_editor'>\n";

            $html .= "<ul class='admin_element_list'>\n";

            $_sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
            $this->mBd->execSql($_sql, $_result, FALSE);

            // Show existing content
            if ($_result != null) {
                while (list($_key, $_value) = each($_result)) {
                    $html .= "<li class='admin_element_item_container'>\n";
                    $html .= "<div class='admin_element_data'>\n";
                    $html .= _("Language") . ": " . LocaleList::GenererFormSelect($_value["locales_id"], "langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_language", null, TRUE);

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

                    $html .= $_FCKeditor->CreateHtml();

                    $html .= "</div>\n";
                    $html .= "<div class='admin_element_tools'>\n";

                    $_name = "langstrings_" . $this->id . "_substring_" . $_value["langstring_entries_id"] . "_erase";
                    $html .= "<input type='submit' class='submit' name='$_name' value='" . _("Delete string") . "'>";

                    $html .= "</div>\n";
                    $html .= "</li>\n";
                }
            }

            // Editor for new content
            $html .= "<li class='admin_element_item_container'>\n";
        $userData['typeInterface'] = $type_interface;
        $html .= self::getNewUI($this->id, $userData);
            $html .= "</li>\n";

            $html .= "</ul>\n";
            $html .= "</li>\n";
            $html .= "</ul>\n";
        } else {
            $html = '';
            $html .= _("FCKeditor is not installed");
        }

        return Content :: getAdminUI($html, $title);
    }


    /**
     * Reloads the object from the database. Should normally be called after
     * a set operation. This function is private because calling it from a
     * subclass will call the constructor from the wrong scope.
     *
     * @return void
     */
    private function refresh()
    {
        $this->__construct($this->id);
    }
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
