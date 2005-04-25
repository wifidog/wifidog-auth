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

if (!empty ($_REQUEST['url']))
{
	$session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

if (!empty ($_REQUEST['username']) && !empty ($_REQUEST['password']) && !empty ($_REQUEST['auth_source']))
{
	$errmsg = '';
	$username = $db->EscapeString($_REQUEST['username']);

	// Authenticating the user through the selected auth source.
$network = Network::processSelectNetworkUI('auth_source');

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

$node=null;
if (!empty ($_REQUEST['gw_id']))
{
	$gw_id=$_REQUEST['gw_id'];

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
	$html .= '<h3>'._("This is the 'virtual login' page you can use to get the credentials which will then give you access to management functions on the network without being at a hotspot.").'</h3>'."\n";
}
else
{
	$html .= '<h3>'._("Welcome! Hotspot:")." $hotspot_name</h3>\n";
}

$html .= '<h3>'._("Please log-in or").'<br><a href="'.BASE_SSL_PATH.'signup.php">'._("Sign-up, it's free!").'</a></h3>'."\n";

$html .= '<form name="login_form" method="post">'."\n";
if($node!=null)
{
	$html .= '<input type="hidden" name="gw_address" value="'.$gw_address.'">'."\n";
$html .= '<input type="hidden" name="gw_port" value="'.$gw_port.'">'."\n";
$html .= '<input type="hidden" name="gw_id" value="'.$gw_id.'">'."\n";
}
$html .= '<table>'."\n";
$html .= Network::getSelectNetworkUI('auth_source');
$html .= '<tr>'."\n";
$html .= '<td>'._("Username (or email)").':</td>'."\n";
$html .= '<td><input type="text" name="username" value="'.$username.'" size="20"></td>'."\n";
$html .= '</tr>'."\n";
$html .= '<tr>'."\n";
$html .= '<td>'._("Password").':</td>'."\n";
$html .= '<td><input type="password" name="password" size="20"></td>'."\n";
$html .= '</tr>'."\n";
$html .= '<tr>'."\n";
$html .= '<td></td>'."\n";
$html .= '<td><input class="submit" type="submit" name="submit" value="'._("Login").'"></td>'."\n";
$html .= '</tr>'."\n";
$html .= '</table>'."\n";
$html .= '</form>'."\n";

$html .= '<h3>'._("I already have an account, but").':</h3>'."\n";
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

require_once BASEPATH.'classes/MainUI.php';
$ui = new MainUI();
$ui->setMainContent($html);
$ui->display();
?>



