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
 * Resends the password
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/User.php');
require_once('classes/MainUI.php');

if (isset($_REQUEST['submit'])) {
    if (!$_REQUEST['username'] && !$_REQUEST['email']) {
        $smarty->assign("error", _("Please specify a username or email address"));
    } else {
        $username = $db->escapeString($_REQUEST['username']);
        $email = $db->escapeString($_REQUEST['email']);

        $account_origin = Network::getObject($_REQUEST['auth_source']);

        try {
            if(empty($account_origin))
                throw new Exception(_("Sorry, this network does not exist !"));

            // Get a list of users associated with either a username of an e-mail
            $username && $user = User::getUserByUsernameAndOrigin($username, $account_origin);
            $email && $user = User::getUserByEmailAndOrigin($email, $account_origin);

            // In the case that both previous function calls failed to return a users list
            // Throw an exception
            if($user != null)
                $user->sendLostPasswordEmail();
            else
                throw new Exception(_("This username or email could not be found in our database"));

            $smarty->assign('message', _('A new password has been emailed to you.'));
            //$smarty->display('templates/validate.html');
            $ui = new MainUI();
            $ui->setMainContent($smarty->fetch("templates/validate.html"));
            $ui->display();
            exit;
        } catch (Exception $e) {
            $smarty->assign("error", $e->getMessage());
        }
    }
}

// Add the auth servers list to smarty variables
$sources = array ();
// Preserve keys
$network_array=Network::getAllNetworks();
foreach ($network_array as $network)
    if ($network->getAuthenticator()->isRegistrationPermitted())
        $sources[$network->getId()] = $network->getName();

isset ($sources) && $smarty->assign('auth_sources', $sources);
// Pass the account_origin along, if it's set
isset ($_REQUEST["auth_source"]) && $smarty->assign('selected_auth_source', $_REQUEST["auth_source"]);

$ui = new MainUI();
$smarty->assign('SelectNetworkUI', Network::getSelectNetworkUI('auth_source'));
$ui->setMainContent($smarty->fetch("templates/lost_password.html"));
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
