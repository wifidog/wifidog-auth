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
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/User.php';

if (!empty($_REQUEST['url'])) {
    $session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}

if (!empty($_REQUEST['username']) && !empty($_REQUEST['password'])) {
    $security = new Security();
    $username = $db->EscapeString($_REQUEST['username']);
    $password_hash = User::passwordHash($_REQUEST['password']);
    $db->ExecSqlUniqueRes("SELECT *, CASE WHEN ((NOW() - reg_date) > interval '".VALIDATION_GRACE_TIME." minutes') THEN true ELSE false END AS validation_grace_time_expired FROM users WHERE (user_id='$username' OR email='$username') AND pass='$password_hash'", $user_info, false);

    if ($user_info != null) {
	    if (($user_info['account_status'] == ACCOUNT_STATUS_VALIDATION) && ($user_info['validation_grace_time_expired']=='t')) {
	        $validation_grace_time = VALIDATION_GRACE_TIME;
	        $smarty->assign("error",  _("Sorry, your $validation_grace_time minutes grace period to retrieve your email and validate your account has now expired. ($validation_grace_time min grace period started on $user_info[reg_date]).  You will have to connect to the internet and validate your account from another location."));
	    } else {
	        $token = User::generateToken();
	        if ($_SERVER['REMOTE_ADDR']) {
		        $node_ip = $db->EscapeString($_SERVER['REMOTE_ADDR']);
	        }
	        if (isset($_REQUEST['gw_id']) && $_REQUEST['gw_id']) {
                $node_id = $db->EscapeString($_REQUEST['gw_id']);
	            $db->ExecSqlUpdate("INSERT INTO connections (user_id, token, token_status, timestamp_in, node_id, node_ip, last_updated) VALUES ('{$user_info['user_id']}', '$token', '" . TOKEN_UNUSED . "', NOW(), '$node_id', '$node_ip', NOW())");
	        }

	        $security->login($username, $password_hash);
            if (isset($_REQUEST['gw_address']) && isset($_REQUEST['gw_port'])) {
	            header("Location: http://" . $_REQUEST['gw_address'] . ":" . $_REQUEST['gw_port'] . "/wifidog/auth?token=$token");
            } else {
                /* Virtual login */
	            header("Location: ".BASE_NON_SSL_PATH);
            }
            exit;
	    }
    } else {
	    $user_info = null;
	    /* This is only used to discriminate if the problem was a non-existent user of a wrong password. */
        $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username' OR email='$username'", $user_info, false);
	    if ($user_info == null) {
	        $smarty->assign("error",  _('Unknown username or email'));
	    } else {
	        $smarty->assign("error",  _('Incorrect password (Maybe you have CAPS LOCK on?)'));
	    }
    }
}

if (isset($_REQUEST['gw_id'])) {
    $smarty->assign("gw_id", $_REQUEST['gw_id']);

    $node = Node::getNode($db->EscapeString(CURRENT_NODE_ID));
    if ($node == null) {
        $smarty->display("templates/message_unknown_hotspot.html");
        exit;
    } else {
    	$smarty->assign('hotspot_name', $node->getName());
    }
} else {
    /* Gateway ID is not set... Virtual login */
    $smarty->display("templates/login_virtual.html");
    exit;
}

isset($_REQUEST["username"]) && $smarty->assign('username', $_REQUEST["username"]);
isset($_REQUEST["gw_address"]) && $smarty->assign('gw_address', $_REQUEST['gw_address']);
isset($_REQUEST["gw_port"]) && $smarty->assign('gw_port', $_REQUEST['gw_port']);
isset($_REQUEST["gw_id"]) && $smarty->assign('gw_id', $_REQUEST['gw_id']);

$smarty->display("templates/".LOGIN_PAGE_NAME);
?>
