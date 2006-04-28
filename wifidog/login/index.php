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
 * Hotspots redirect newly active PCs to this page with this url:
 * Refering to the wifidog.conf installed on the hotspot:
 *
 * {PROTOCOL}://{HOSTNAME}:{PORT}{PATH}login?gw_address=A&gw_port=P&gw_id=I&url=URL
 *
 * PROTOCOL, HOSTNAME, PORT and PATH refer to the settings for the hotspot's
 * currently selected authserver.
 * PROTOCOL http or https if SSLAvailable is yes for the hotspot's current authserver
 * HOSTNAME Hostname of the current authserver
 * PORT is HTTPPort or SSLPort of the current authserver
 * PATH is the Path of the current authserver.  PATH starts and ends with a /
 *
 * gw_address is GatewayAddress from the config file
 * gw_port is GatewayPort
 * gw_id is GatewayID, is node id
 * url is the original url requested but redirected here by the hotspot wifidog
 *
 * http://auth.wirelesstoronto.ca:80/login?gw_address=207.50.119.2&gw_port=2060&gw_id=215&url=http://hotmail.com
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
// require_once('lib/magpie/rss_fetch.inc'); // Added so RSS code which depends on the Magpie RSS functions will work. //

// Init values
$continueToAdmin = false;
$username = null;
$password = null;
$gw_address = null;
$gw_port = null;
$gw_id = null;
$logout = null;

$create_a_free_account = _("Create a free account");

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

if (isset($_REQUEST["form_signup"]) && $_REQUEST["form_signup"] == $create_a_free_account) {
	MainUI::redirect(BASE_URL_PATH . "signup.php");
	exit;
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
    }

    catch (Exception $e) {
        $ui = new MainUI();
        $ui->displayError($e->getMessage());
        exit;
    }

	$network = $node->getNetwork();
} else {
    // Gateway ID is not set ... virtual login
    $network = Network::getCurrentNetwork();
    $node = Node::getObject(DEFAULT_NODE_ID);
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
if (isset ($_REQUEST["form_request"]) && $_REQUEST["form_request"] == "login") {
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
	}
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
 
/*
 * Tool content
 */

// Set details about node
$smarty->assign('node', $node);
$smarty->assign('gw_address', $gw_address);
$smarty->assign('gw_port', $gw_port);
$smarty->assign('gw_id', $gw_id);

// Check if user wanted to enter the administration interface
$smarty->assign('origin', empty($_REQUEST['origin']) ? null : $_REQUEST['origin']);

// Set network selector
$smarty->assign('selectNetworkUI', Network::getSelectNetworkUI('auth_source'));

// Set user details
$smarty->assign('username', !empty($username) ? $username : "");

// Set error message
$smarty->assign('error', !empty($error) ? $error : null);

$smarty->assign('create_a_free_account', $create_a_free_account);

// Compile HTML code
$html = $smarty->fetch("templates/sites/login.tpl");

$ui = new MainUI();
if($node) {
	$ui->setTitle(sprintf(_("%s login page for %s"), $network->getName(), $node->getName()));
} else {
    $ui->setTitle(_("Offsite login page"));
}
$ui->setPageName('login');
$ui->shrinkLeftArea();
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

$ui->appendContent('main_area_middle',
	"\n<h1>" . sprintf(_("Welcome to the %s Hotspot network."), $network->getName()) . "</h1>\n" .

	"<p>" .
	_("Please use the login/signup form on the left to activate your connection with the internet.") .
	"</p>\n".

	"<p>" .
	_("If you already have an account please use that to login.") . " " .
	_("Thanks to the hospitality of your proprietor, this is a surfing free zone.") . " " .
	_("Once you login you are welcome to use the internet as long as you like.") .
	"</p>\n" .

	"<p>" .
	sprintf(_("If you are new to %s, please use the form on the left to create a new account."), $network->getName()) . " " .
	_("There will be no charge for this service.") .
	"</p>\n"
);

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

?>
