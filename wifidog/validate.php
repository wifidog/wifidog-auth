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
require_once('classes/Node.php');
require_once('classes/MainUI.php');

try {
    if (!isset($_REQUEST["token"]))
        throw new Exception(_('No token specified!'));

    if (!isset($_REQUEST["user_id"]))
        throw new Exception(_('No user ID specified!'));

    $validated_user = User::getObject($_REQUEST['user_id']);

    if ($db->escapeString($_REQUEST['token']) != $validated_user->getValidationToken())
        throw new Exception(_('The validation token does not match the one in the database.'));

    if ($validated_user->getAccountStatus() == ACCOUNT_STATUS_ALLOWED)
        throw new Exception(_('Your account has already been activated.'));

    // This user wants to validate his account, the token is OK and he's not trying to pass the same token more than once
    // Activate his account and let him in NOW
    $validated_user->SetAccountStatus(ACCOUNT_STATUS_ALLOWED);
    User::setCurrentUser($validated_user);

    // Try to current physical node:  if possible it will regenerate the session accordingly with the connection token.
    $current_user_node = Node::getCurrentRealNode();
    if($current_user_node)
            $gw_id = $session->set(SESS_GW_ID_VAR, $current_user_node->getId());

    // Show activation message
    $smarty->assign('message', _("Your account has been succesfully activated!\n\nYou may now browse to a remote Internet address and take advantage of the free Internet access!\n\nIf you get prompted for a login, enter the username and password you have just created."));
} catch (Exception $e) {
    $smarty->assign('message', $e->getMessage());
}

$ui = new MainUI();
$ui->appendContent('main_area_middle', $smarty->fetch("templates/sites/validate.tpl"));
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
