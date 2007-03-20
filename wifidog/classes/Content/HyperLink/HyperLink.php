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
require_once('classes/Content/SimpleString/SimpleString.php');
require_once('classes/HyperLinkUtils.php');

/**
 * Represents a simple Langstring (no title, description, etc.)
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class HyperLink extends SimpleString
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
    public function getAdminUI($subclass_admin_interface = null, $title = null, $type_interface = "SMALL") {
        // Init values.
        $html = '';
        $html .= $subclass_admin_interface;
        $url = $this->getString();
        if(!HyperLinkUtils::validateURL($url))
        {
            $html .= "<p class='warningmsg'>"._("Warning: the URL is invalid!")."</p>\n";
        }
        return parent :: getAdminUI($html, $title, $type_interface);
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
        $url = $this->getString();
        if(HyperLinkUtils::validateURL($url))
        {
            $html = "<a href='$url'>$url</a>\n";
        }
        else
        {
            $html = $url." "._("(invalid URL)")."\n";
        }
        /* Handle hyperlink clicktrough logging */
        $html = $this->replaceHyperLinks($html);
        $this->setUserUIMainDisplayContent($html);
        return Content :: getUserUI();
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


