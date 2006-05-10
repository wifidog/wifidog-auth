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
 * Changes password of user
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load common include file
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('classes/MainUI.php');
require_once('include/common_interface.php');
require_once('classes/User.php');

$smarty->assign('error', '');

$smarty->assign('username', '');
$smarty->assign('oldpassword', '');
$smarty->assign('newpassword', '');
$smarty->assign('newpassword_again', '');

$user = User::getCurrentUser();
if ($user) {
	User::assignSmartyValues($smarty, $user);

	if ($user->isSuperAdmin() && isset($_REQUEST['username'])) {
		$smarty->assign('username', $_REQUEST['username']);
		$username = $_REQUEST['username'];
	}
	else
		$username = $user->getUsername();

	// $user->getEmail();
	// $user->getRealName();
	// $user->getWebsiteURL();
	// $user->isSuperAdmin();
	// $user->isOwner();
}
else {
	$smarty->assign('error', _("You must login before you can change your password."));
}

if ($user && isset($_REQUEST["form_request"])) {
    try {
        // If the source is present and that it's in our, save it to a var for later use
        $account_origin = Network::getObject($_REQUEST['auth_source']);

        if (!$account_origin || !$_REQUEST["username"] || !$_REQUEST["oldpassword"] || !$_REQUEST["newpassword"] || !$_REQUEST["newpassword_again"])
            throw new Exception(_('You MUST fill in all the fields.'));

        $current_password = $db->escapeString(trim($_REQUEST['oldpassword']));
        $new_password = $db->escapeString(trim($_REQUEST['newpassword']));

        if(empty($account_origin))
            throw new Exception(_("Sorry, this network does not exist !"));

        if ($_REQUEST["newpassword"] != $_REQUEST["newpassword_again"])
            throw new Exception(_("Passwords do not match."));

        // Warning for now, password change only works for local users, registered through our signup process.
		if ($username == $user->getUsername()) {
			$victim = $user;

			if ($victim->getPasswordHash() != User::passwordHash($current_password))
				throw new Exception(_("Wrong password."));

			$victim->setPassword($new_password);
			$smarty->assign("message", _("Your password has been changed succesfully."));
		}
		else {
			if ($user->isSuperAdmin()) {
				$username =  $db->escapeString(trim($_REQUEST['username']));
				$victim = User::getUserByUsernameAndOrigin($username, $account_origin);

				if (!$victim)
					throw new Exception(sprintf(_('Sorry, user %s does not exist !'), $username));

				if ($victim->getPasswordHash() != User::passwordHash($current_password)
					&& $user->getPasswordHash() != User::passwordHash($current_password))
					throw new Exception(_("Wrong password."));

				$victim->setPassword($new_password);
				$smarty->assign("message", sprintf(_('The password for %s has been successfully changed.'), $username));
			}
			else {
				throw new Exception(_('Sorry, invalid change password request !'));
			}
		}

        $ui = new MainUI();
        $ui->addContent('main_area_middle', $smarty->fetch("templates/sites/validate.tpl"));
        $ui->display();

        exit;
    } catch (Exception $e) {
        $smarty->assign("error", $e->getMessage());
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
$ui->addContent('main_area_middle', $smarty->fetch("templates/change_password.html"));
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
