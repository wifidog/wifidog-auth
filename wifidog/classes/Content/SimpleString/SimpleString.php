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
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/LocaleList.php');
require_once('classes/Locale.php');

/**
 * Represents a simple Langstring (no title, description, etc.)
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class SimpleString extends Langstring
{
    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @return void     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);
   		$this->allowed_html_tags = "";
        /*
         * A SimpleString is NEVER persistent
         */
        parent::setIsPersistent(false);
    }
    /** When a content object is set as Simple, it means that is is used merely to contain it's own data.  No title, description or other metadata will be set or displayed, during display or administration
     * @return true or false */
    public function isSimpleContent() {
        return true;
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
        $liste_languages = new LocaleList();
        $sql = "SELECT * FROM content_langstring_entries WHERE content_langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
        $this->mBd->execSql($sql, $result, false); //echo "type_interface: $type_interface\n";

        $exclude_array = array ();
        if ($result != null) {
            while (list ($key, $value) = each($result)) {
                $exclude_array[$value['locales_id']] = $value['locales_id'];
 
                $html .= "<li class='admin_element_item_container'>\n";
                $html .= "<div class='admin_element_data'>\n";
                $name = "langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_language";
                $html .= "<input type='hidden' name='$name' value=''>\n";
                if ($type_interface == 'LARGE') {
                    $html .= "<textarea name='langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_string' class='textarea' cols='60' rows='3'>" . htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8') . "</textarea>\n";
                } else {
                    $html .= "<input type='text' class='input_text' name='langstrings_" . $this->id . "_substring_$value[langstring_entries_id]_string' size='44' value='" . htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8') . "'>\n";
                }

                $html .= "</div>\n";
                $html .= "</li>\n";
            }
        }

        //Nouvelles chaîne
        if($result == null) {
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_data'>\n";
            $name = "langstrings_" . $this->id . "_substring_new_language";
            $html .= "<input type='hidden' name='$name' value=''>\n";
            $new_substring_name = "langstrings_" . $this->id . "_substring_new_string";

            if ($type_interface == 'LARGE') {
                $html .= "<textarea name='$new_substring_name' class='textarea' cols='60' rows='3'></textarea>\n";
            } else {
                $html .= "<input type='text' name='$new_substring_name' class='input_text' size='44' value=''>\n";
            }

            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        $html .= "</ul>\n";

        return Content :: getAdminUI($html, $title);
    }
    
    /**
     * A short string representation of the content
     *
     * @return string Returns the content
     */
    public function __toString($verbose = true)
    {
        return strip_tags($this->getString($verbose));
    }

    /**
     * Reloads the object from the database.
     *
     * Should normally be called after a set operation.
     *
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope
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


