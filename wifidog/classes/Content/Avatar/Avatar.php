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
 * @subpackage Content classes
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx <francois.proulx@gmail.com> - Technologies
 * Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/Content/Picture/Picture.php';
require_once BASEPATH.'classes/Content/File/File.php';

error_reporting(E_ALL);

/** Represents an Image
 */
class Avatar extends Picture
{
    /**Constructeur
    @param $content_id Content id
    */
    function __construct($content_id)
    {
        parent :: __construct($content_id);
        $this->setIsTrivialContent(true);
    }

    /**Affiche l'interface d'administration de l'objet */
    function getAdminUI($subclass_admin_interface = null)
    {
        $html = '';
        $html .= "<div class='admin_class'>Avatar (".get_class($this)." instance)</div>\n";

        // Show File admin UI + display the picture
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= "<div class='admin_section_title'>"._("Picture preview")." : </div>\n";
        $html .= "<br>\n";

        $html .= "<img src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
        $html .= "</div>\n";
        $html .= "</div>\n";

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='by_upload' ". ($this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Upload a new file (Uploading a new one will replace any existing file)")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />';
        $html .= '<input name="file_file_upload'.$this->getId().'" type="file" />';
        $html .= "</div>\n";
        $html .= "</div>\n";

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='remote' ". (!$this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Remote file via URL")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        if ($this->isLocalFile())
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50'/>";
        else
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50' value='".$this->getFileUrl()."'/>";
        $html .= "</div>\n";
        $html .= "</div>\n";

        if (!$this->isLocalFile())
        {
            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("File URL")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= $this->getFileUrl();
            $html .= "</div>\n";
            $html .= "</div>\n";
        }

        $html .= $subclass_admin_interface;
        #return parent :: getAdminUI($html);
        return $html;
    }

    function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
        {
                parent :: processAdminUI();
        }
    }

    /** Retrieves the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @return The HTML fragment for this interface */
    public function getUserUI()
    {
        $html = '';
        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>Picture (".get_class($this)." instance)</div>\n";

        $html .= "<img src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
        $html .= "</div>\n";
        return $html;
    }

    /** Reloads the object from the database.  Should normally be called after a set operation.
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope */
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

?>
