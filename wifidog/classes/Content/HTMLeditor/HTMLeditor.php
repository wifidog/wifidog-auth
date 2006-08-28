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
class HTMLeditor extends Langstring
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
	 		$html .= "<ul class='admin_element_list'>\n";

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
            return parent::processAdminUI();
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


