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
$node = null;
$continueToAdmin = false;
$username = null;
$password = null;
$auth_source = null;
$gw_address = null;
$gw_port = null;
$gw_id = null;
$logout = null;

/*
 * General request parameter processing section
 */

if (isset($_REQUEST["username"])) {
    $username = $db->escapeString($_REQUEST["username"]);
}

if (isset($_REQUEST["password"])) {
    $password = $db->escapeString($_REQUEST["password"]);
}

if (isset($_REQUEST["auth_source"])) {
    $auth_source = $db->escapeString($_REQUEST["auth_source"]);
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
if (!empty($_REQUEST['origin']) && $_REQUEST['origin'] = "admin") {
    $continueToAdmin = true;
}

/*
 * Start general request parameter processing section
 */
if (!is_null($gw_id)) {
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
if ($node && $node->isSplashOnly()) {
    if (!is_null($gw_address) && !is_null($gw_port)) {
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
if (!is_null($username) && !is_null($password) && !is_null($auth_source)) {
    // Init values
    $errmsg = '';

    // Authenticating the user through the selected auth source.
    $network = Network::processSelectNetworkUI('auth_source');

    $user = $network->getAuthenticator()->login($username, $password, $errmsg);

    if ($user != null) {
        if (!is_null($gw_address) && !is_null($gw_port)) {
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
if ((!is_null($logout) && $logout) && ($user = User::getCurrentUser()) != null) {
    $network->getAuthenticator()->logout();
}

/**
 * Start login interface section
 */

// Init ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Init ALL smarty values
$smarty->assign('node', null);
$smarty->assign('gwAddress', null);
$smarty->assign('gwPort', null);
$smarty->assign('gwId', null);
$smarty->assign('selectNetworkUI', null);
$smarty->assign('username', null);
$smarty->assign('error', null);
$smarty->assign('hotspot_homepage_url', null);
$smarty->assign('hotspot_name', null);
$smarty->assign('contents', false);
$smarty->assign('contentArray', array());

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);

// Set details about node
$smarty->assign('node', $node);
$smarty->assign('gw_address', $gw_address);
$smarty->assign('gw_port', $gw_port);
$smarty->assign('gw_id', $gw_id);

// Set network selector
$smarty->assign('selectNetworkUI', Network::getSelectNetworkUI('auth_source'));

// Set user details
$smarty->assign('username', !is_null($username) ? $username : "");

// Set error message
$smarty->assign('error', !is_null($error) ? $error : null);

// Compile HTML code
$html = $smarty->fetch("templates/sites/login.tpl");

/*
 * Main content
 */

// Init values
$contentArray = array();

// Reset ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Set section of Smarty template
$smarty->assign('sectionMAINCONTENT', true);

/*
 * Node section
 */

// Get all node content
if (!is_null($node)) {
    // Set all information about current node
    $smarty->assign('hotspot_homepage_url', $node->getHomePageURL());
    $smarty->assign('hotspot_name', $node->getName());

    $contents = $node->getAllContent(true, null, 'login_page');

    if ($contents) {
        foreach ($contents as $content) {
            $contentArray[] = array('isDisplayableAt' => $content->isDisplayableAt($node), 'userUI' => $content->getUserUI());
        }

        // Set all content of current node
        $smarty->assign('contents', true);
        $smarty->assign('contentArray', $contentArray);
    }
}

// Compile HTML code
$html_body = $smarty->fetch("templates/sites/login.tpl");

/*
 * Render output
 */
$ui = new MainUI();
$ui->setToolContent($html);
$ui->setMainContent($html_body);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>