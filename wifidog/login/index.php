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
 * gw_id is GatewayID
 * url is the original url requested but redirected here by the hotspot wifidog
 *
 * http://auth.wirelesstoronto.ca:80/login?gw_address=207.50.119.2&gw_port=2060&gw_id=215&url=http://hotmail.com
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2004-2007 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../include/common.php');

require_once('classes/Security.php');
require_once('classes/Node.php');
require_once('classes/User.php');
require_once('classes/Network.php');
require_once('classes/Authenticator.php');
require_once('classes/MainUI.php');
$smarty = SmartyWifidog::getObject();
$session = Session::getObject();
$db = AbstractDb::getObject();
// Init values
$username = null;
$password = null;
$gw_address = null;
$gw_port = null;
$gw_id = null;
$mac = null;
$logout = null;

/*
 * General request parameter processing section
 */


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

if (isset($_REQUEST["form_signup"])) {
    MainUI::redirect(BASE_URL_PATH . "signup.php");
    exit;
}

if (isset($_REQUEST["mac"])) {
    $session->set(SESS_USER_MAC_VAR, $_REQUEST['mac']);
    $mac = $_REQUEST['mac'];
}


/*
 * Store original URL typed by user
 */
if (!empty($_REQUEST['url'])) {
    $session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

/*
 * Start general request parameter processing section
 */
if (!empty($gw_id)) {
    try {
        $node = Node::getObjectByGatewayId($gw_id);
    }

    catch (Exception $e) {
        $returnedNodeIsNew = false;
        $node = Node::processStealOrCreateNewUI($gw_id, $returnedNodeIsNew);

        if(!$node){
            $ui = MainUI::getObject();
            $ui->addContent('main_area_middle', '<p class=errormsg>'.$e->getMessage()."</p>\n", 1);
            $stealNodeForm=null;
            $stealNodeForm.="<form action='' method='POST'>\n";
            $stealNodeForm.=Node::getStealOrCreateNewUI($gw_id);
            $stealNodeForm.="</form>\n";
            $ui->addContent('main_area_middle',$stealNodeForm,2);
            $ui->display();
            exit;
        }
        else {
            if($returnedNodeIsNew) {
                $url = BASE_URL_PATH."admin/generic_object_admin.php?object_class=Node&action=edit&object_id=".$node->getId();
                header("Location: {$url}");
                exit;
            }
        }
    }
    if($node)
    {
        $session->set(SESS_NODE_ID_VAR, $node->getId());
    }
    $network = $node->getNetwork();
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
        $token = $user->generateConnectionToken($mac);
        User::setCurrentUser($user);
        header("Location: http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token);
    } else {
        // Virtual login, redirect to the auth server homepage
        header("Location: " . BASE_SSL_PATH);
    }
}

/*
 * Normal login process
 */
if (!empty($_REQUEST["login_form_submit"])) {
    // Init values
    $errmsg = '';
    $user = User::getCurrentUser();
    if (!$user) {
        //Normally, we already have a user logged-in (processed by process_login_out.php).  But we try again, if only to display the error
        Authenticator::processLoginUI($errmsg);
    }
//echo "DEBUG: user: "; echo $user->getUsername();
    if ($user != null) {
        if (!empty($gw_address) && !empty($gw_port)) {
            // Login from a gateway, redirect to the gateway to activate the token
            $token = $user->generateConnectionToken($mac);
            if(!$token)
            {
                throw new exception(sprintf(_("Unable to generate token for user %s"),$user->getUsername()));
            }
            else
            {
                header("Location: http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token);
            }
        } else {
            // Virtual login, redirect to the auth server homepage
            header("Location: " . BASE_SSL_PATH);
        }

        exit;
    }
}

/*
 * Start logout process section
 *
 * Once logged out, we display the login page
 */
if ((!empty($logout) && $logout) && ($user = User::getCurrentUser()) != null  && !$user->isSplashOnlyUser()) {
    $network->getAuthenticator()->logout();
}

/**
 * Start login interface section
 */

/*
 * Tool content
 */

// Set details about node
$smarty->assign('gw_address', $gw_address);
$smarty->assign('gw_port', $gw_port);
$smarty->assign('gw_id', $gw_id);
$smarty->assign('mac', $mac);

// Get the login form
$html = "";
$html .= "<form name='login_form'  id='login_form' action='".BASE_SSL_PATH."login/index.php' method='post'>\n";
$html .= "<input type='hidden' name='form_request' value='login'>\n";
if ($gw_address != null)
$html .= "<input type='hidden' name='gw_address' value='{$gw_address}'>\n";
if ($gw_port != null)
$html .= "<input type='hidden' name='gw_port' value='{$gw_port}'>\n";
if ($gw_id != null)
$html .= "<input type='hidden' name='gw_id' value='{$gw_id}'>\n";
if ($mac != null)
$html .= "<input type='hidden' name='mac' value='{$mac}'>\n";
$html .= Authenticator::getLoginUI();
$html .= "</form>\n";
$html .= "<div id='login_help'>\n";
$html .= "<h1>"._("I'm having difficulties:")."</h1>\n";
$html .= "<ul>\n";
$html .= "<li><a href='".BASE_URL_PATH."lost_username.php'>"._("I Forgot my username")."</a></li>\n";
$html .= "<li><a href='".BASE_URL_PATH."lost_password.php'>"._("I Forgot my password")."</a></li>\n";
$html .= "<li><a href='".BASE_URL_PATH."resend_validation.php'>"._("Re-send the validation email")."</a></li>\n";
$html .= "</ul>\n";
$html .= "</div>\n";

$ui = MainUI::getObject();
if($node) {
    $ui->setTitle(sprintf(_("%s login page for %s"), $network->getName(), $node->getName()));
} else {
    $ui->setTitle(_("Offsite login page"));
}
$ui->setPageName('login');
$ui->shrinkLeftArea();
if($node){
    $name = $node->getName();
}
else {
    $name = $network->getName();
}
$welcome_msg = sprintf("<span>%s</span> <em>%s</em>",_("Welcome to"), $name);
$ui->addContent('page_header', "<div class='welcome_msg'><div class='welcome_msg_inner'>$welcome_msg</div></div>");
$ui->addContent('main_area_top', $html);

/*
 * Main content (login form)
 */

// Get all network content and node "login" content
// Get all the parent objects of the node
if ($node) {
    $parents = HotspotGraph::getAllParents($node);
} else {
    $parents = array($network->getHgeId());
}

$first = $db->escapeString(array_shift($parents));
$sql_from = "(SELECT content_id, display_area, display_order, subscribe_timestamp 
			FROM hotspot_graph_element_has_content 
			WHERE hotspot_graph_element_id='$first' AND display_page='login')";

// Get the contents for all elements parents of and including the node, but exclude user subscribed content if user is known
foreach($parents as $parentid) {
    $parent_id = $db->escapeString($parentid);
    $sql_from .= " UNION (SELECT content_id, display_area, display_order, subscribe_timestamp 
			FROM hotspot_graph_element_has_content hgehc 
			WHERE hotspot_graph_element_id='$parent_id' AND display_page='login')";
}
$sql = "SELECT * FROM ($sql_from) AS content_everywhere ORDER BY display_area, display_order, subscribe_timestamp DESC";
$db->execSql($sql, $content_rows, false);
if ($content_rows) {
    foreach ($content_rows as $content_row) {
        $content = Content :: getObject($content_row['content_id']);
        if ($content->isDisplayableAt($node)) {
            $ui->addContent($content_row['display_area'], $content, $content_row['display_order']);
        }
    }
}
$showMoreLink = false;


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