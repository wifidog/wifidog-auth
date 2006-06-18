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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load File content class
 */
require_once('classes/Content/File/File.php');

/**
 * Represents an Image
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 */
class Picture extends File
{
    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @access protected
     */
    protected function __construct($content_id)
    {
        // Define globals
        global $db;

        // Init values
        $row = null;

        parent::__construct($content_id);

        $content_id = $db->escapeString($content_id);
        $sql = "SELECT * FROM content_file_image WHERE pictures_id='$content_id'";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row == null) {
            /*
             * Since the parent Content exists, the necessary data in
             * content_group had not yet been created
             */
            $sql = "INSERT INTO content_file_image (pictures_id) VALUES ('$content_id')";
            $db->execSqlUpdate($sql, false);

            $sql = "SELECT * FROM content_file_image WHERE pictures_id='$content_id'";
            $db->execSqlUniqueRes($sql, $row, false);

            if ($row == null) {
                throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
            }
        }

        $this->mBd = &$db;
        $this->pictures_row = $row;
    }

    /**
     * Gets the width of an image
     *
     * @return mixed (int or null) Width of image
     *
     * @access private
     */
    private function getWidth()
    {
        return $this->pictures_row['width'];
    }

    /**
     * Sets the width of an image
     *
     * @param int $width Width to be set
     *
     * @return bool True if width was a valid value and could be set
     *
     * @access private
     */
    private function setWidth($width)
    {
        // Init values
        $_retval = false;

        if (empty($width) || is_numeric($width)) {
            empty($width) ? $width = "NULL" : $width = $this->mBd->escapeString($width);

            $this->mBd->execSqlUpdate("UPDATE content_file_image SET width=" . $width . " WHERE pictures_id='" . $this->getId() . "'", false);
            $this->refresh();

            $_retval = true;
        }

        return $_retval;
    }

    /**
     * Gets the height of an image
     *
     * @return mixed (int or null) Height of image
     *
     * @access private
     */
    private function getHeight()
    {
        return $this->pictures_row['height'];
    }

    /**
     * Sets the width of an image
     *
     * @param int $height Height to be set
     *
     * @return bool True if height was a valid value and could be set
     *
     * @access private
     */
    function setHeight($height)
    {
        // Init values
        $_retval = false;

        if(empty($height) || is_numeric($height)) {
            empty($height) ? $height = "NULL" : $height = $this->mBd->escapeString($height) ;

            $this->mBd->execSqlUpdate("UPDATE content_file_image SET height=" . $height . " WHERE pictures_id='" . $this->getId() . "'", false);
            $this->refresh();

            $_retval = true;
        }

        return $_retval;
    }

    /**
     * Get destination URL (hyperlink) of Picture
     *
     * @return string URL of file
     */
    public function getHyperlinkUrl()
    {
        return $this->pictures_row['hyperlink_url'];
    }

    /**
     * Sets destination URL (hyperlink) of a Picture
     *
     * @param string $url
     *
     * @return void

     */
    private function setHyperlinkUrl($url)
    {
        if ($url == null) {
            $url = "NULL";
        } else {
            $url = "'".$this->mBd->escapeString($url)."'";
        }

        $this->mBd->execSqlUpdate("UPDATE content_file_image SET hyperlink_url = $url WHERE pictures_id='".$this->getId()."'", false);
        $this->refresh();
    }

    /**
     * Shows the administration interface for Picture.
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     *
     * @access public
     */
    public function getAdminUI($subclass_admin_interface = null, $title=null)
    {
        // Init values
        $html = '';
        $width = $this->getWidth();
        $height = $this->getHeight();
        $hyperlink_url = htmlspecialchars($this->getHyperlinkUrl(), ENT_QUOTES);


        $html .= "<ul class='admin_element_list'>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Hyperlink URL (leave empty if you don't need it)")." : </div>\n";
        $html .= "<input type='text' name='pictures_{$this->getId()}_hyperlink_url' value='{$hyperlink_url}'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Width (leave empty if you want to keep original width)")." : </div>\n";
        $html .= "<input type='text' name='pictures_{$this->getId()}_width' value='{$width}'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Height (leave empty if you want to keep original height)")." : </div>\n";
        $html .= "<input type='text' name='pictures_{$this->getId()}_height' value='{$height}'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        // Show File admin UI + display the picture
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Picture preview")." : </div>\n";

        if (empty($width)) {
            $width = "";
        } else {
            $width = "width='$width'";
        }

        if (empty($height)) {
            $height = "";
        } else {
            $height = "height='$height'";
        }

        $hyperlink_url = $this->getHyperlinkUrl();

        // Wrap around a hyperlink tag if a URL exists.
        if(!empty($hyperlink_url))
        		$html .= "<a href=\"".htmlspecialchars($hyperlink_url ,ENT_QUOTES)."\"><img src='".htmlspecialchars($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''></a>";
        	else
        		$html .= "<img src='".htmlspecialchars($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''>";

        $html .= "</div>\n";
        $html .= "</li>\n";
        $html .= "</ul>\n";

        $html .= $subclass_admin_interface;

        return parent::getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for Picture
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();

            $this->setHyperlinkUrl($_REQUEST["pictures_{$this->getId()}_hyperlink_url"]);
            $this->setWidth(intval($_REQUEST["pictures_{$this->getId()}_width"]));
            $this->setHeight(intval($_REQUEST["pictures_{$this->getId()}_height"]));
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * Anything that overrides this method should call the parent method with
     * it's output at the END of processing.
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI()
    {
        // Init values
        $html = '';
        $width = $this->getWidth();
        $height = $this->getHeight();
        $hyperlink_url = $this->getHyperlinkUrl();

        $html .= "<div class='user_ui_container ".get_class($this)."'>\n";

        if (empty($width)) {
            $width = "";
        } else {
            $width = "width='$width'";
        }

        if (empty($height)) {
            $height = "";
        } else {
            $height = "height='$height'";
        }

        // Wrap around a hyperlink tag if a URL exists.
        if(!empty($hyperlink_url))
        		$html .= "<a href=\"".htmlspecialchars($hyperlink_url ,ENT_QUOTES)."\"><img src='".htmlspecialchars($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''></a>";
        	else
        		$html .= "<img src='".htmlspecialchars($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''>";

        $html .= "</div>\n";

        return $html;
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
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


