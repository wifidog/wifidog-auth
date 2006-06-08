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
 * An IFrame can integrate external HTML content from a given URL.
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 */
class IFrame extends Content
{
    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @return void
     *
     * @access public
     */
    public function __construct($content_id)
    {
        // Define globals
        global $db;

        // Init value
        $row = null;

        parent::__construct($content_id);
        $this->mDb = &$db;

        $content_id = $db->escapeString($content_id);
        $sql = "SELECT * FROM content_iframe WHERE iframes_id='$content_id'";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row == null) {
            /*
             * Since the parent Content exists, the necessary data in
             * content_group had not yet been created
             */
            $sql = "INSERT INTO content_iframe (iframes_id) VALUES ('$content_id')";
            $db->execSqlUpdate($sql, false);

            $sql = "SELECT * FROM content_iframe WHERE iframes_id='$content_id'";
            $db->execSqlUniqueRes($sql, $row, false);

            if ($row == null) {
                throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
            }
        }

        $this->iframe_row = $row;
    }

    /**
     * Return the IFrame URL
     *
     * @return mixed (string or null) IFrame URL if it has been set
     *
     * @access private
     */
    protected function getUrl()
    {
        return $this->iframe_row['url'];
    }

    /**
     * Sets the IFrame URL
     *
     * @param string $url IFrame URL
     *
     * @return void
     *
     * @access private
     */
    protected function setUrl($url)
    {
        $url = $this->mDb->escapeString($url);
        $this->mDb->execSqlUpdate("UPDATE content_iframe SET url = '{$url}' WHERE iframes_id = '{$this->getId()}';");
    }

    /**
     * This function is there for displayUserUi will work fine with the
     * IFrameRest object.
     *
     * DO NOT DELETE IT.
     *
     * @return mixed (string or null) IFrame URL if it has been set
     *
     * @access private
     */
    private function getGeneratedUrl()
    {
        return $this->getUrl();;
    }

    /**
     * Gets the width of an IFrame
     *
     * @return mixed (int or null) Width of IFrame
     *
     * @access private
     */
    private function getWidth()
    {
        return $this->iframe_row['width'];
    }

    /**
     * Sets the width of an IFrame
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

        if (empty ($width) || is_numeric($width)) {
            if (empty ($width)) {
                $width = "NULL";
            } else {
                $width = $this->mDb->escapeString($width);
            }

            $this->mDb->execSqlUpdate("UPDATE content_iframe SET width=" . $width . " WHERE iframes_id='" . $this->getId() . "'", false);
            $this->refresh();

            $_retval = true;
        }

        return $_retval;
    }

    /**
     * Gets the height of an IFrame
     *
     * @return mixed (int or null) Height of IFrame
     *
     * @access private
     */
    private function getHeight()
    {
        return $this->iframe_row['height'];
    }

    /**
     * Sets the width of an IFrame
     *
     * @param int $height Height to be set
     *
     * @return bool True if height was a valid value and could be set
     *
     * @access private
     */
    private function setHeight($height)
    {
        // Init values
        $_retval = false;

        if (empty ($height) || is_numeric($height))
        {
            if (empty ($height)) {
                $height = "NULL";
            } else {
                $height = $this->mDb->escapeString($height);
            }

            $this->mDb->execSqlUpdate("UPDATE content_iframe SET height =".$height." WHERE iframes_id='".$this->getId()."'", false);
            $this->refresh();

            $_retval = true;
        }

        return $_retval;
    }

    /**
     * Shows the administration interface for IFrame
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
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("Width (suggested width is 600 (pixels))")." : </div>\n";
        $name = "iframe_".$this->id."_width";
        $html .= "<input type='text' name='{$name}' value='{$this->getWidth()}'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "iframe_".$this->id."_height";
        $html .= "<div class='admin_element_label'>"._("Height (suggested width is 400 (pixels))")." : </div>\n";
        $html .= "<input type='text' name='{$name}' value='{$this->getHeight()}'>";
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= "<div class='admin_element_label'>"._("HTML content URL")." : </div>\n";
        $name = "iframe_".$this->id."_url";
        $html .= "<input type='text' size=80 name='$name' value='".$this->getUrl()."'\n";
        $html .= "</div>\n";
        $html .= "</li>\n";
 		$html .= "</ul>\n";

        $html .= $subclass_admin_interface;

        return parent::getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for IFrame
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent::processAdminUI();

            // If the URL is not empty
            $name = "iframe_".$this->id."_url";

            if (!empty ($_REQUEST[$name])) {
                $this->setUrl($_REQUEST[$name]);
            } else {
                $this->setUrl("");
            }

            $name = "iframe_".$this->id."_width";
            $this->setWidth(intval($_REQUEST[$name]));
            $name = "iframe_".$this->id."_height";
            $this->setHeight(intval($_REQUEST[$name]));
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
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI($subclass_user_interface = null)
    {
        // Init values
        $html = '';

        $html .= "<div class='user_ui_container ".get_class($this)."'>\n";
        $html .= "<iframe width='{$this->getWidth()}' height='{$this->getHeight()}' frameborder='1' src='{$this->getGeneratedUrl()}'>"._("Your browser does not support IFrames.")."</iframe>\n";
        $html .= $subclass_user_interface;
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
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


