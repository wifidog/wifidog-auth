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
define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/User.php';

if (isset($_REQUEST['submit'])) {
    if (!$_REQUEST['username'] && !$_REQUEST['email']) {
        $smarty->assign("error", _("Please specify a username or email address"));
    } else {
        $username = $db->EscapeString($_REQUEST['username']);
        $email = $db->EscapeString($_REQUEST['email']);
        // If the source is present and that it's in our AUTH_SOURCE_ARRAY, save it to a var for later use
		$_REQUEST['auth_source'] && in_array($_REQUEST['auth_source'], array_keys($AUTH_SOURCE_ARRAY)) && $account_origin = $_REQUEST['auth_source'];

        try {
        	if(empty($account_origin))
				throw new Exception(_("Sorry, this network does not exist !"));
				
        	// Get a list of users associated with either a username of an e-mail
            $username && $user = User::getUserByUsernameAndOrigin($username, $account_origin);
            $email && $user = User::getUserByEmailAndOrigin($email, $account_origin);
            
            // In the case that both previous function calls failed to return a users list
            // Throw an exception
            if($user != null)
	            $user->sendLostPasswordEmail();
	        else
	        	throw new Exception(_("This username or email could not be found in our database"));
            	
            $smarty->assign('message', _('A new password has been emailed to you.'));
            $smarty->display('templates/validate.html');
            exit;
        } catch (Exception $e) {
            $smarty->assign("error", $e->getMessage());
        }
    }
}

// Add the auth servers list to smarty variables
$sources = array ();
// Preserve keys
foreach (array_keys($AUTH_SOURCE_ARRAY) as $auth_source_key)
	if ($AUTH_SOURCE_ARRAY[$auth_source_key]['authenticator']->isRegistrationPermitted())
		$sources[$auth_source_key] = $AUTH_SOURCE_ARRAY[$auth_source_key];

isset ($sources) && $smarty->assign('auth_sources', $sources);
// Pass the account_origin along, if it's set
isset ($_REQUEST["auth_source"]) && $smarty->assign('selected_auth_source', $_REQUEST["auth_source"]);

$smarty->display("templates/lost_password.html");
?>
