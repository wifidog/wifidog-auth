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
 * @subpackage Security
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/User.php');

/**
 * Security class
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 */
class Security
{
    /**
     * This functions ensures that code called after executing the function
     * won't be run, when the user has no admin priviliges
     *
     * @return void Halts execution if user has no admin priviliges
     *
     * @static
     * @access public
     */
    public static function requireAdmin()
    {
        $_currentUser = User::getCurrentUser();

        if (!$_currentUser || ($_currentUser && !User::getCurrentUser()->isSuperAdmin())) {
            echo '<p class="error">' . _("You do not have administrator privileges") . '!</p>';
            exit;
        }
    }

    /**
     * This functions ensures that code called after executing the function
     * won't be run, when the user has no admin priviliges or is owner of a
     * specific node
     *
     * @param string $node_id ID of node to be checked
     *
     * @return mixed Halts execution if user has no admin priviliges or returns
     *               true
     *
     * @static
     * @access public
     */
    public static function requireOwner($node_id)
    {
        $_currentUser = User::getCurrentUser();

        // If the user is an admin let him pass
        if ($_currentUser && User::getCurrentUser()->isSuperAdmin()) {
            return true;
        }

        $_node = Node::getObject($node_id);

        if (!$_node->isOwner($_currentUser)) {
            echo '<p class="error">' . _("You do not have owner privileges") . '!</p>';
            exit;
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

