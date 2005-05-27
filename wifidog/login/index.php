<?php
// $Id$
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file
 * Login page
 * @author Copyright (C) 2004 Benoit Grégoire et Philippe April
 */
define('BASEPATH', '../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/Network.php';

// Logout process
if ((!empty ($_REQUEST['logout']) && $_REQUEST['logout'] == true) && ($user = User::getCurrentUser()) != null)
{
    $user->logout();
}

// Store original URL typed by user.
//TODO: manage this...
if (!empty ($_REQUEST['url']))
{
	$session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

// Actual login process
if (!empty ($_REQUEST['username']) && !empty ($_REQUEST['password']) && !empty ($_REQUEST['auth_source']))
{
	$errmsg = '';
	$username = $db->EscapeString($_REQUEST['username']);

	// Authenticating the user through the selected auth source.
	$network = Network :: processSelectNetworkUI('auth_source');
    
	$user = $network->getAuthenticator()->login($_REQUEST['username'], $_REQUEST['password'], $errmsg);
	if ($user != null)
	{
		if (isset ($_REQUEST['gw_address']) && isset ($_REQUEST['gw_port']) && ($token = $user->generateConnectionToken()))
		{
			header("Location: http://".$_REQUEST['gw_address'].":".$_REQUEST['gw_port']."/wifidog/auth?token=$token");
		}
		else
		{
			/* Virtual login */
			header("Location: ".BASE_NON_SSL_PATH);
		}
		exit;
	}
	else
	{
		$error = $errmsg;
	}
}
else
{
	$error = _('Your must specify your username and password');
}

// Add the auth servers list to smarty variables
isset ($AUTH_SOURCE_ARRAY) && $smarty->assign('auth_sources', $AUTH_SOURCE_ARRAY);
// Pass the account_origin along, if it's set
isset ($_REQUEST["auth_source"]) && $smarty->assign('selected_auth_source', $_REQUEST["auth_source"]);

$node = null;
if (!empty ($_REQUEST['gw_id']))
{
	$gw_id = $_REQUEST['gw_id'];

	try
	{
		$node = Node :: getObject($_REQUEST['gw_id']);
		$hotspot_name = $node->getName();
	}
	catch (Exception $e)
	{
		$smarty->assign("error", $e->getMessage());
		$smarty->display("templates/generic_error.html");
		exit;
	}
}
else
{
	/* Gateway ID is not set... Virtual login */
}

isset ($_REQUEST["username"]) && $username = $_REQUEST["username"];
isset ($_REQUEST["gw_address"]) && $gw_address = $_REQUEST['gw_address'];
isset ($_REQUEST["gw_port"]) && $gw_port = $_REQUEST['gw_port'];
isset ($_REQUEST["gw_id"]) && $gw_id = $_REQUEST['gw_id'];

isset ($_REQUEST["gw_address"]) && $session->set(SESS_GW_ADDRESS_VAR, $_REQUEST['gw_address']);
isset ($_REQUEST["gw_port"]) && $session->set(SESS_GW_PORT_VAR, $_REQUEST['gw_port']);
isset ($_REQUEST["gw_id"]) && $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);

$html = '';
$html .= '<div id="form">'."\n";
if (empty ($_REQUEST['gw_id']))
{
	$html .= '<h1>'._("Virtual login").'</h1'."\n";
}
else
{
	$html .= '<h1>'._("Welcome! Hotspot:")." $hotspot_name</h1>\n";
}

$html .= '<h1>'._("Sign up : ").'</h1>';
$html .= '<p class="indent">'."\n";
$html .= '<a href="'.BASE_SSL_PATH.'signup.php">'._("Get an account here.").'</a><br><a href="'.BASE_SSL_PATH.'faq.php">'._("Why is this service free ?").'</a>'."\n";
$html .= '</p>';

$html .= '<form name="login_form" method="post">'."\n";
if ($node != null)
{
	$html .= '<input type="hidden" name="gw_address" value="'.$gw_address.'">'."\n";
	$html .= '<input type="hidden" name="gw_port" value="'.$gw_port.'">'."\n";
	$html .= '<input type="hidden" name="gw_id" value="'.$gw_id.'">'."\n";
}
$html .= '<h1>'._("Log in : ").'</h1>';
$html .= '<p class="indent">'."\n";
$html .= Network::getSelectNetworkUI('auth_source')."<br>\n";
$html .= _("Username (or email)").'<br>'."\n";
$html .= '<input type="text" name="username" value="'.$username.'" size="20"><br>'."\n";
$html .= _("Password").':<br>'."\n";
$html .= '<input type="password" name="password" size="20"><br>'."\n";
$html .= '<input class="submit" type="submit" name="submit" value="'._("Login").'"><br>'."\n";
;
$html .= '</form>'."\n";
$html .= '</p>';

$html .= '<h1>'._("I already have an account, but").':</h1>'."\n";
$html .= '<ul>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'lost_username.php">'._("I Forgot my username").'</a><br>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'lost_password.php">'._("I Forgot my password").'</a>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'resend_validation.php">'._("Re-send the validation email").'</a><br>'."\n";
if ($error)
{
	$class = 'class="help"';
}
else
{
	$class = '';
}
$html .= '<li><a href="'.BASE_SSL_PATH.'faq.php">'._("I have trouble connecting and I would like some help").'</a><br>'."\n";
$html .= '</ul>'."\n";
$html .= '</div>'."\n";

if ($error)
{
	$html .= '<div id="help">'."\n";
	$html .= "$error\n";
	$html .= '</div>'."\n";
}

// HTML body
$hotspot_network_name = HOTSPOT_NETWORK_NAME;
$hotspot_network_url = HOTSPOT_NETWORK_URL;
$network_logo_banner_url = COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

$html_body = "<div class='login_body'>";
$html_body .= "<a href='{$hotspot_network_url}'><img src='{$network_logo_banner_url}' alt='{$hotspot_network_name} logo' border='0'></a><p>\n";
if(!empty($node))
{
    $hotspot_logo_url = find_local_content_url(HOTSPOT_LOGO_NAME);
    $html_body .= "<h1>{$hotspot_name}</h1>";
    $html_body .= "<a href='{$node->getHomePageURL()}'><img src='{$hotspot_logo_url}' alt='{$hotspot_name}'></a>\n";
}
$html_body .= "</div>";

require_once BASEPATH.'classes/MainUI.php';
$ui = new MainUI();
$ui->setToolContent($html);
$ui->setMainContent($html_body);
$ui->display();
?>