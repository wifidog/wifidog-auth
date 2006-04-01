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
 * Login page
 *
 * The login page will both display the login page, and process login and logout
 * request.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../include/common.php');

require_once('include/common_interface.php');
require_once('classes/Security.php');
require_once('classes/Node.php');
require_once('classes/User.php');
require_once('classes/Network.php');
require_once('classes/MainUI.php');

// Init values
$continueToAdmin = false;
$username = null;
$password = null;
$gw_address = null;
$gw_port = null;
$gw_id = null;
$logout = null;

/*
 * General request parameter processing section
 */

if (isset($_REQUEST["username"])) {
    $username = $_REQUEST["username"];
}

if (isset($_REQUEST["password"])) {
    $password = $_REQUEST["password"];
}

if (isset($_REQUEST["gw_address"])) {
    $gw_address = $_REQUEST['gw_address'];
    $session->set(SESS_GW_ADDRESS_VAR, $_REQUEST['gw_address']);
}

if (isset($_REQUEST["gw_port"])) {
    $gw_port = $_REQUEST['gw_port'];
    $session->set(SESS_GW_PORT_VAR, $_REQUEST['gw_port']);
}

if (isset($_REQUEST["gw_id"])) {
    $gw_id = $_REQUEST['gw_id'];
    $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);
}

if (isset($_REQUEST["logout"])) {
    $logout = $_REQUEST['logout'];
}

/*
 * Store original URL typed by user
 */
if (!empty($_REQUEST['url'])) {
    $session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

// Check if user wanted to enter the administration interface
if (!empty($_REQUEST['origin']) && $_REQUEST['origin'] == "admin") {
    $continueToAdmin = true;
}

/*
 * Start general request parameter processing section
 */
if (!empty($gw_id)) {
    try {
        $node = Node::getObject($gw_id);
        $hotspot_name = $node->getName();
        $network = $node->getNetwork();
    }

    catch (Exception $e) {
        $ui = new MainUI();
        $ui->displayError($e->getMessage());
        exit;
    }
} else {
    // Gateway ID is not set ... virtual login
    $network = Network::getCurrentNetwork();
    $node = null;
}

/**
 * Start login process section.
 *
 * If  successfull, the browser is redirected to another page
 */

/*
 * If this is a splash-only node, skip the login interface and log-in using
 * the splash_only user
 */
if (!empty($node) && $node->isSplashOnly()) {
    if (!empty($gw_address) && !empty($gw_port)) {
        // Login from a gateway, redirect to the gateway to activate the token
        $user = $network->getSplashOnlyUser();
        $token = $user->generateConnectionToken();
        User::setCurrentUser($user);
        header("Location: http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token);
    } else {
        // Virtual login, redirect to the auth server homepage
        header("Location: " . BASE_SSL_PATH . ($continueToAdmin ? "admin/" : ""));
    }
}

/*
 * Normal login process
 */
if (!empty($username) && !empty($password)) {
    // Init values
    $errmsg = '';
    // Authenticating the user through the selected auth source.
    $network = Network::processSelectNetworkUI('auth_source');

    $user = $network->getAuthenticator()->login($username, $password, $errmsg);

    if ($user != null) {
        if (!empty($gw_address) && !empty($gw_port)) {
            // Login from a gateway, redirect to the gateway to activate the token
            $token = $user->generateConnectionToken();

            header("Location: http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token);
        } else {
            // Virtual login, redirect to the auth server homepage
            header("Location: " . BASE_SSL_PATH . ($continueToAdmin ? "admin/" : ""));
        }

        exit;
    } else {
        $error = $errmsg;
    }
} else {
    /*
     * Note that this is executed even when we have just arrived at the login
     * page, so  the user is reminded to supply a username and password
     */
    $error = _('Your must specify your username and password');
}

/*
 * Start logout process section
 *
 * Once logged out, we display the login page
 */
if ((!empty($logout) && $logout) && ($user = User::getCurrentUser()) != null) {
    $network->getAuthenticator()->logout();
}

/**
 * Start login interface section
 */
 
// Init ALL smarty values
$smarty->assign('node', null);
$smarty->assign('gwAddress', null);
$smarty->assign('gwPort', null);
$smarty->assign('gwId', null);
$smarty->assign('origin', null);
$smarty->assign('selectNetworkUI', null);
$smarty->assign('username', null);
$smarty->assign('error', null);

/*
 * Tool content
 */

// Set details about node
$smarty->assign('node', $node);
$smarty->assign('gw_address', $gw_address);
$smarty->assign('gw_port', $gw_port);
$smarty->assign('gw_id', $gw_id);

// Check if user wanted to enter the administration interface
if (!empty($_REQUEST['origin'])) {
    $smarty->assign('origin', $_REQUEST['origin']);
}

// Set network selector
$smarty->assign('selectNetworkUI', Network::getSelectNetworkUI('auth_source'));

// Set user details
$smarty->assign('username', !empty($username) ? $username : "");

// Set error message
$smarty->assign('error', !empty($error) ? $error : null);

// Compile HTML code
$html = $smarty->fetch("templates/sites/login.tpl");




$ui = new MainUI();
if($node)
{
$ui->setTitle(sprintf(_("%s login page for %s"),$network->getName(), $node->getName()));
}
else
{
    $ui->setTitle(_("Offsite login page"));
}
$ui->setPageName('login');
$ui->appendContent('left_area_middle', $html);
/*
 * Main content
 */
// Get all network content
$content_rows = null;
$network_id = $db->escapeString($network->getId());
    $sql = "SELECT content_id, display_area FROM network_has_content WHERE network_id='$network_id'  AND display_page='login' ORDER BY display_area, display_order, subscribe_timestamp DESC";
$db->execSql($sql, $content_rows, false);
if ($content_rows) {
    foreach ($content_rows as $content_row) {
        $content = Content :: getObject($content_row['content_id']);
        if ($content->isDisplayableAt($node)) {
            $ui->appendContent($content_row['display_area'], $content->getUserUI());
        }
    }
}

if($node)
{
// Get all node content
$content_rows = null;
$node_id = $db->escapeString($node->getId());
    $sql = "SELECT content_id, display_area FROM node_has_content WHERE node_id='$node_id'  AND display_page='login' ORDER BY display_area, display_order, subscribe_timestamp DESC";
$db->execSql($sql, $content_rows, false);
$showMoreLink = false;
if ($content_rows) {
    foreach ($content_rows as $content_row) {
        $content = Content :: getObject($content_row['content_id']);
        if ($content->isDisplayableAt($node)) {
            $ui->appendContent($content_row['display_area'], $content->getUserUI());
        }
    }
}
}

/*
 * Render output
 */
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */