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
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Content/ContentGroup/ContentGroup.php');
require_once('classes/User.php');

/**
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 */
class PatternLanguage extends ContentGroup
{

    /**
     * Constructor
     *
     * @param string $content_id Content Id
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);

        /*
         * A Pattern language can NEVER be expandable
         */
        $this->setIsExpandable(false);
    }

    /**
     * Get all pattern language objects
     *
     * @return function
     *
     * @access public
     * @static
     */
    public static function getAllContent()
    {
       return parent::getAllContent("PatternLanguage");
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
     * @return The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI($subclass_user_interface = null)
    {
        // Init values
        $html = '';

        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>PatternLanguage (".get_class($this)." instance)</div>\n";

        // Check if the user has already subscribed to Pattern language
        $current_user = User::getCurrentUser();

        if($current_user == null || $this->isUserSubscribed($current_user) == false) {
            // hyperlink to all users narrative
            $html .= "<ul class='pattern_language_menu'>";
            $html .= "<li><a class='pattern_language_big_links' href='/content/PatternLanguage/subscription.php'>"._("Subscribe to Pattern Language")."</a></li>";
            $html .= "<li><a class='pattern_language_big_links' href='/content/PatternLanguage/archives.php'>"._("Read narratives archives")."</a></li>";
            $html .= "</ul>";

            // Until subscription is done DO NOT log this !
            $this->setLoggingStatus(false);

            // Tell the content group not to display elements until subscription is done
            $parent_output = parent :: getUserUI($html, true);
        } else {
            /*
             * The user is subscribed to the pattern language show an element!
             * hyperlink to user's narrative
             */
            $html .= "<ul class='pattern_language_menu'>";
            $html .= "<li><a href='/content/PatternLanguage/narrative.php'>"._("Read my narrative")."</a></li>";
            $html .= "<li><a href='/content/PatternLanguage/archives.php'>"._("Read narratives archives")."</a></li>";
            $html .= "<li><a href='/content/PatternLanguage/subscription.php'>"._("Unsubscribe")."</a></li>";
            $html .= "</ul>";

            // Display the random pattern
            $parent_output = parent :: getUserUI($html);
        }

        $html .= $subclass_user_interface;
        $html .= "</div>\n";

        return $parent_output;
    }

    /**
     * Display the narrative
     *
     * @param string $user The user who's narrative you want to grab
     *
     * @return the archive page HTML
     *
     * @access public
     */
    public function displayNarrative(User $user)
    {
        // Define globals
        global $db;

        // Init values
        $html = "";
        $rows = null;

        /**
         * @internal Debug values user_id = 8a90b1ea56cf27a0c61f9304da73bcd5
         * @internal PL: 3a3ea73dd2e2d03729e62b95d2574fc6
         */

        $sql = "SELECT * FROM (SELECT DISTINCT ON (content_group_element_id) content_group_element_id, first_display_timestamp FROM content_display_log AS cdl JOIN content_group_element AS cge ON (cdl.content_id = cge.content_group_element_id) JOIN content ON (content.content_id = cge.content_group_id) where user_id = '{$user->getId()}' AND cge.content_group_id = '{$this->getId()}' AND content.content_type = 'PatternLanguage') AS patterns ORDER BY first_display_timestamp";
        $db->execSql($sql, $rows, false);

        if ($rows) {
            foreach($rows as $row) {
                $cge = Content::getObject($row['content_group_element_id']);
                $cge->setLoggingStatus(false);
                $html .= $cge->getUserUI()."<p>";
            }
        }

        return $html;
    }

    /**
     * Get the list of all narratives
     *
     * @return the archive page HTML
     *
     * @access public
     */
    public function getNarrativeList()
    {
        // Define globals
        global $db;

        // Init values
        $narratives = array();
        $rows = null;

        $sql = "SELECT DISTINCT user_id FROM content_display_log AS cdl JOIN content_group_element AS cge ON (cdl.content_id = cge.content_group_element_id) JOIN content ON (content.content_id = cge.content_group_id) WHERE content_type = 'PatternLanguage'";
        $db->execSql($sql , $rows, false);

        if ($rows) {
            foreach($rows as $row) {
                $narratives[] = User::getObject($row['user_id']);
            }
        }

        return $narratives;
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

?>
