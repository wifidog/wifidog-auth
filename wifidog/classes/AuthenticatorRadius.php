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
/**@file AuthenticatorRadius.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>, Technologies Coeus inc.
* @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>,Technologies Coeus inc.
* */

/**
 * Portions of this code are based on PEAR RADIUS Auth class examples provided
 * Copyright (c) 2003, Michael Bretterklieber <michael@bretterklieber.com> 
 * All rights reserved.
 */
require_once BASEPATH.'classes/Authenticator.php';
require_once BASEPATH.'classes/User.php';
// Including PEAR RADIUS and CHAP MD5 interface classes
require_once 'Auth/RADIUS.php';
require_once 'Crypt/CHAP.php';

/** Internal wifidog user database authentication source */
class AuthenticatorRadius extends Authenticator
{
	private $mRadius_hostname;
	private $mRadius_auth_port;
	private $mRadius_acct_port;
	private $mRadius_secret_key;
	private $mRadius_encryption_method;

	/**
	 * AuthenticatorRadius constructor
	 * Example:  new AuthenticatorRadius(IDRC_ACCOUNT_ORIGIN, "192.168.0.11",
	 * 1812, 1813, "secret_key", "CHAP_MD5");
	 * @param $account_orgin : The origin of the account
	 * @param $host : hostname of the RADIUS server
	 * @param $auth_port : Authentication port of the RADIUS server
	 * @param $acct_port : Accounting port of the RADIUS server
	 * @param $secret_key : The secret key between between this client and the
	 * server
	 * @param $encryption_method : The encryption method choosen for the
	 * requests
	 */
	function __construct($account_orgin, $host = "localhost", $auth_port = 1812, $acct_port = 1813, $secret_key = "", $encryption_method = "CHAP_MD5")
	{
		parent :: __construct($account_orgin);

		// Store RADIUS server parameters
		// Defaults to localhost:0 with MD5 CHAP encryption
		// Setting port to 0 will get default RADIUS Auth service port ( either 1645 or 1812 )
		$this->mRadius_hostname = $host;
		$this->mRadius_auth_port = $auth_port;
		$this->mRadius_acct_port = $acct_port;
		$this->mRadius_secret_key = $secret_key;
		$this->mRadius_encryption_method = $encryption_method;
	}

	/** Attempts to login a user against the authentication source.  If successfull, returns a User object
	 * @param username:  A valid identifying token for the source.  Not necessarily unique.  For local user, bots username and email are valid.
	 * @param password:  Clear text password.
	 * @retval The actual User object if login was successfull, false otherwise.
	 */
	function login($username, $password, & $errmsg = null)
	{
		global $db;
		$security = new Security();
		$retval = false;
		$username = $db->EscapeString($username);
		$password = $db->EscapeString($password);

		/*
		 * Supported encryption methods are :
		 * 
		 * CHAP_MD5 :Challenge-Handshake Authentication Protocol with MD5
		 * MSCHAPv1 and MSCHAPv2 : Microsoft's CHAP implementation
		 */
		switch ($this->mRadius_encryption_method)
		{
			case "PAP" :
			case "CHAP_MD5" :
			case "MSCHAPv1" :
			case "MSCHAPv2" :
				// Instanciate PEAR class
				$classname = 'Auth_RADIUS_'.$this->mRadius_encryption_method;
				$radius_server = new $classname ($username, $password);
				$radius_server->addServer($this->mRadius_hostname, $this->mRadius_auth_port, $this->mRadius_secret_key);
				break;
			default :
				// Invalid encryption method
				$errmsg = _("Invalid RADIUS encryption method.");
				return false;
		}

		// Instructing PEAR RADIUS class auth parameters
		$radius_server->username = $username;
		// Depending on the auth method, generate challenge response
		switch ($this->mRadius_encryption_method)
		{
			case 'CHAP_MD5' :
			case 'MSCHAPv1' :
				$classname = $this->mRadius_encryption_method == 'MSCHAPv1' ? 'Crypt_CHAP_MSv1' : 'Crypt_CHAP_MD5';
				$crypt_class = new $classname;
				$crypt_class->password = $password;
				$radius_server->challenge = $crypt_class->challenge;
				$radius_server->chapid = $crypt_class->chapid;
				$radius_server->response = $crypt_class->challengeResponse();
				$radius_server->flags = 1;
				break;
			case 'MSCHAPv2' :
				$crypt_class = new Crypt_CHAP_MSv2;
				$crypt_class->username = $username;
				$crypt_class->password = $password;
				$radius_server->challenge = $crypt_class->authChallenge;
				$radius_server->peerChallenge = $crypt_class->peerChallenge;
				$radius_server->chapid = $crypt_class->chapid;
				$radius_server->response = $crypt_class->challengeResponse();
				break;
			default :
				$radius_server->password = $password;
				break;
		}

		if (!$radius_server->start())
		{
			$errmsg = _("Could not initiate PEAR RADIUS Auth class : ".$radius_server->getError());
			return false;
		}

		// Send the authentication request to the RADIUS server
		$result = $radius_server->send();

		if (PEAR :: isError($result))
		{
			$errmsg = _("Failed to send authentication request to the RADIUS server. : ".$result->getMessage());
			return false;
		}
		else
			if ($result === true)
			{
				// RADIUS authentication succeeded !
				// Now checking for local copy of this user
				$sql = "SELECT user_id, pass FROM users WHERE (username='$username') AND account_origin='".$this->getAccountOrigin()."'";
				$db->ExecSqlUniqueRes($sql, $user_info, false);

				if ($user_info != null)
				{
					$user = new User($user_info['user_id']);
					if ($user->isUserValid($errmsg))
					{
						$retval = $user;
						User::setCurrentUser($user);
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
					// This user has been succcessfully authenticated through remote RADIUS, but it's not yet in our local database
					// Creating the user with a Global Unique ID, empty email and password
					// Local database password hashing is based on an empty string ( we do not store remote passwords )
					$user = User :: createUser(get_guid(), $username, $this->getAccountOrigin(), "", "");
					$retval = & $user;
					// Validate the user right away !
					$user->setAccountStatus(ACCOUNT_STATUS_ALLOWED);
					User::setCurrentUser($user);
					$errmsg = _("Login successfull");
				}
				return $retval;
			}
			else
			{
				$errmsg = _("The RADIUS server rejected this username/password combination.");
				return false;
			}

		$radius_server->close();
	}

	/** Start accounting traffic for the user 
	 * $conn_id:  The connection id for the connection to work on */
	function acctStart($conn_id, & $errmsg = null)
	{
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);
		
		// RADIUS accounting start
		$radius_acct = new Auth_RADIUS_Acct_Start;
		$radius_acct->addServer($this->mRadius_hostname, $this->mRadius_acct_port, $this->mRadius_secret_key);
		// Specify the user for which accounting will be done
		$radius_acct->username = $info['username'];
		// Specify the way the user has been authenticated ( via RADIUS, the class did it )
		$radius_acct->authentic = RADIUS_AUTH_RADIUS;
		// Set the session ID to the generated token
		$radius_acct->session_id = $info['token'];

		$status = $radius_acct->start();
		if (PEAR :: isError($status))
			return false;

		$result = $radius_acct->send();
		if (PEAR :: isError($result))
		{
			$errmsg = "Could not send accounting request to RADIUS server.";
			return false;
		}
		else
			if ($result !== true)
			{
				$radius_acct->close();
				$errmsg = "Accounting request rejected by RADIUS server.";
				return false;
			}

		$radius_acct->close();

		// Run generic accounting ( local traffic counters ) only if RADIUS went OK
		parent :: acctStart($info);
		return true;
	}

	/** Update traffic counters
	 * $conn_id: The connection id for the connection to work on */
	function acctUpdate($conn_id, $incoming, $outgoing, & $errmsg = null)
	{
		// Call generic traffic updater ( local database )
		parent :: acctUpdate($conn_id, $incoming, $outgoing);
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);
		
		// RADIUS accounting ping
		// Session is completely based on Database time
		$session_time = strtotime($info['now']) - strtotime($info['timestamp_in']);

		$radius_acct = new Auth_RADIUS_Acct_Update;
		$radius_acct->addServer($this->mRadius_hostname, $this->mRadius_acct_port, $this->mRadius_secret_key);
		// Specify the user for which accounting will be done
		$radius_acct->username = $info['username'];
		$racct->session_time = $session_time;
		// Set the session ID to the generated token
		$radius_acct->session_id = $info['token'];

		$status = $radius_acct->start();
		if (PEAR :: isError($status))
			return false;

		// Send traffic data along with the request
		$radius_acct->putAttribute(RADIUS_ACCT_INPUT_PACKETS, $incoming);
		$radius_acct->putAttribute(RADIUS_ACCT_OUTPUT_PACKETS, $outgoing);

		$result = $radius_acct->send();
		if (PEAR :: isError($result))
		{
			$errmsg = "Could not send accounting request to RADIUS server.";
			return false;
		}
		else
			if ($result !== true)
			{
				$radius_acct->close();
				$errmsg = "Accounting request rejected by RADIUS server.";
				return false;
			}

		$radius_acct->close();
		return true;
	}

	/** Final update and stop accounting
	 * $conn_id:  The connection id (the token id) for the connection to work on
	 * */
	function acctStop($conn_id, & $errmsg = null)
	{
		parent :: acctStop($conn_id);
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);

		// RADIUS accounting stop
		// Session is completely based on Database time
		$session_time = strtotime($info['now']) - strtotime($info['timestamp_in']);

		$radius_acct = new Auth_RADIUS_Acct_Stop;
		$radius_acct->addServer($this->mRadius_hostname, $this->mRadius_acct_port, $this->mRadius_secret_key);
		// Specify the user for which accounting will be done
		$radius_acct->username = $info['username'];
		$racct->session_time = $session_time;
		// Set the session ID to the generated token
		$radius_acct->session_id = $info['token'];

		$status = $radius_acct->start();
		if (PEAR :: isError($status))
		{
			$errmsg = "Could not initiate PEAR RADIUS class.";
			return false;
		}

		// Cause of session termination
		$radius_acct->putAttribute(RADIUS_ACCT_TERMINATE_CAUSE, RADIUS_TERM_SESSION_TIMEOUT);
		$result = $radius_acct->send();

		if (PEAR :: isError($result))
		{
			$errmsg = "Could not send accounting request to RADIUS server.";
			return false;
		}
		else
			if ($result !== true)
			{
				$radius_acct->close();
				$errmsg = "Accounting request rejected by RADIUS server.";
				return false;
			}

		$radius_acct->close();
		return true;
	}

} // End class
?>