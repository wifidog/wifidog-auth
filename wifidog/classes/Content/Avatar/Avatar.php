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
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load Picture content class
 */
require_once('classes/Content/Picture/Picture.php');

/**
 * Represents an Image
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 */
class Avatar extends Picture
{
    /**
     * Constructor
     * @param string $content_id Content id
     *
     * @return void     */
    protected function __construct($content_id)
    {
        parent :: __construct($content_id);
    }

    /**
     * Shows the administration interface for Avatar
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     */
    public function getAdminUI($subclass_admin_interface = null, $title=null)
    {
        // Init values
        $html = '';
		$html .= "<ul class='admin_element_list'>\n";
        // Show File admin UI + display the picture
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Picture preview")." : </div>\n";
        $html .= "<br>\n";

        $html .= "<img src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='by_upload' ". ($this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Upload a new file (Uploading a new one will replace any existing file)")." : </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />';
        $html .= '<input name="file_file_upload'.$this->getId().'" type="file" />';
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='remote' ". (!$this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Remote file via URL")." : </div>\n";
        $html .= "<div class='admin_element_data'>\n";

        if ($this->isLocalFile()) {
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50'/>";
        } else {
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50' value='".$this->getFileUrl()."'/>";
        }

        $html .= "</div>\n";
        $html .= "</li>\n";

        if (!$this->isLocalFile()) {
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("File URL")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= $this->getFileUrl();
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
		$html .= "</ul>\n";
        $html .= $subclass_admin_interface;
        return parent :: getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for RssAggregator
     *
     * @return void
     */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * @return string The HTML fragment for this interface
     */
    public function getUserUI()
    {
        // Init values
        $html = '';

        $html .= "<div class='user_ui_container ".get_class($this)."'>\n";

        $html .= "<img src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
        $html .= "</div>\n";

        return $html;
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

    /** When a content object is set as Simple, it means that is is used merely to contain it's own data.  No title, description or other metadata will be set or displayed, during display or administration
     * @return true or false */
    public function isSimpleContent() {
        return true;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


