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

if (!empty ($_REQUEST['url'])) {
	$session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

if (!empty ($_REQUEST['username']) && !empty ($_REQUEST['password']) && !empty ($_REQUEST['auth_source'])) {
	$errmsg = '';
	$username = $db->EscapeString($_REQUEST['username']);
	$auth_source = $db->EscapeString($_REQUEST['auth_source']);

	// Authenticating the user through the sselected auth source.
	$user = $AUTH_SOURCE_ARRAY[$auth_source]['authenticator']->login($_REQUEST['username'], $_REQUEST['password'], $errmsg);

	if ($user != null) {
		if (isset ($_REQUEST['gw_address']) && isset ($_REQUEST['gw_port']) && ($token = $user->generateConnectionToken())) {
			header("Location: http://".$_REQUEST['gw_address'].":".$_REQUEST['gw_port']."/wifidog/auth?token=$token");
		} else {
			/* Virtual login */
			header("Location: ".BASE_NON_SSL_PATH);
		}
		exit;
	} else {
		$smarty->assign("error", $errmsg);
	}
} else {

	$smarty->assign("error", _('Your must specify your username and password'));
}

// Add the auth servers list to smarty variables
isset ($AUTH_SOURCE_ARRAY) && $smarty->assign('auth_sources', $AUTH_SOURCE_ARRAY);
// Pass the account_origin along, if it's set
isset ($_REQUEST["auth_source"]) && $smarty->assign('auth_source', $_REQUEST["auth_source"]);

if (isset ($_REQUEST['gw_id'])) {
	$smarty->assign("gw_id", $_REQUEST['gw_id']);

	try {
		$node = Node :: getNode($db->EscapeString(CURRENT_NODE_ID));
		$smarty->assign('hotspot_name', $node->getName());
	} catch (Exception $e) {
		$smarty->assign("error", $e->getMessage());
		$smarty->display("templates/generic_error.html");
		exit;
	}
} else {
	/* Gateway ID is not set... Virtual login */
	$smarty->display("templates/login_virtual.html");
	exit;
}

isset ($_REQUEST["username"]) && $smarty->assign('username', $_REQUEST["username"]);
isset ($_REQUEST["gw_address"]) && $smarty->assign('gw_address', $_REQUEST['gw_address']);
isset ($_REQUEST["gw_port"]) && $smarty->assign('gw_port', $_REQUEST['gw_port']);
isset ($_REQUEST["gw_id"]) && $smarty->assign('gw_id', $_REQUEST['gw_id']);

isset ($_REQUEST["gw_address"]) && $session->set(SESS_GW_ADDRESS_VAR, $_REQUEST['gw_address']);
isset ($_REQUEST["gw_port"]) && $session->set(SESS_GW_PORT_VAR, $_REQUEST['gw_port']);
isset ($_REQUEST["gw_id"]) && $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);

$smarty->display("templates/".LOGIN_PAGE_NAME);
?>

