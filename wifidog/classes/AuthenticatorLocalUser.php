<?php


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
/**@file AuthenticatorLocalUser.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */

require_once BASEPATH.'classes/Authenticator.php';
require_once BASEPATH.'classes/User.php';

/** Internal wifidog user database authentication source */
class AuthenticatorLocalUser extends Authenticator
{

	function __construct($account_orgin)
	{
		parent :: __construct($account_orgin);
	}

	/**
	 * Callback function used to discriminate Local User account origins
	 * @param $account_origin : array
	 * @return boolean : true if the parameter refers to a Local User account
	 * origin
	 */
	private static function isLocalUserAccountOrigin($account_origin)
	{
		return get_class($account_origin['authenticator']) == "AuthenticatorLocalUser";
	}

	/**
	 * Returns in array containing only Local User account origins
	 * @return array
	 */
	public static function getAllLocalUserAccountOrigins()
	{
		global $AUTH_SOURCE_ARRAY;
		return array_filter($AUTH_SOURCE_ARRAY, array("self", "isLocalUserAccountOrigin"));
	}

	/** Attempts to login a user against the authentication source.  If successfull, returns a User object
	 * @param username:  A valid identifying token for the source.  Not necessarily unique.  For local user, bots username and email are valid.
	 * @param password:  Clear text password.
	 * @retval The actual User object if sogin was successfull, false otherwise.
	 */
	function login($username, $password, & $errmsg = null)
	{
		global $db;
		$security = new Security();
		$retval = false;
		$username = $db->EscapeString($username);
		$password = $db->EscapeString($password);
		/**
         * utf8_decode is used for backward compatibility with old passwords
         * containing special characters. 
         * Conversion from UTF-8 to ISO-8859-1 is done to match the MD5 hash
         */
		$password_hash = User :: passwordHash(utf8_decode($_REQUEST['password']));

		$sql = "SELECT user_id FROM users WHERE (username='$username' OR email='$username') AND account_origin='".$this->getAccountOrigin()."' AND pass='$password_hash'";
		$db->ExecSqlUniqueRes($sql, $user_info, false);

		if ($user_info != null)
		{
			$user = new User($user_info['user_id']);
			if ($user->isUserValid($errmsg))
			{
				$retval = & $user;
				$security->login($user->getId(), $password_hash);
				$errmsg = _("Login successfull");
			}
			else
			{
				$retval = false;
				//Reason for refusal is already in $errmsg
			}
		}
		else
		{
			$user_info = null;
			/* This is only used to discriminate if the problem was a non-existent user of a wrong password. */
			$db->ExecSqlUniqueRes("SELECT * FROM users WHERE (username='$username' OR email='$username') AND account_origin='".$this->getAccountOrigin()."'", $user_info, false);
			if ($user_info == null)
			{
				$errmsg = _('Unknown username or email');
			}
			else
			{
				$errmsg = _('Incorrect password (Maybe you have CAPS LOCK on?)');
			}
			$retval = false;
		}
		return $retval;
	}

	/** Logs out the user */
	function logout($info, & $errmsg = null)
	{
		$this->acctStop($info);
		return true;
	}

	/** Start accounting traffic for the user */
	function acctStart($info, & $errmsg = null)
	{
		parent :: acctStart($info);
		return true;
	}

	/** Update traffic counters */
	function acctUpdate($info, $incoming, $outgoing, & $errmsg = null)
	{
			// Just call the generic counters update
	parent :: acctUpdate($info, $incoming, $outgoing);
		return true;
	}

	/** Final update and stop accounting */
	function acctStop($info, & $errmsg = null)
	{
		parent :: acctStop($info);
		return true;
	}
	
	function isRegistrationPermitted()
	{
		return true;
	}

} // End class
?>
