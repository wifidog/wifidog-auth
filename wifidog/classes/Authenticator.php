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
/**@file Authenticator.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */

/** Abstract class to represent an authentication source */
abstract class Authenticator
{
	private $mAccountOrigin;

	function __construct($account_orgin)
	{
		$this->mAccountOrigin = $account_orgin;
	}

	public function getAccountOrigin()
	{
		return $this->mAccountOrigin;
	}

	/** Attempts to login a user against the authentication source.  If successfull, returns a User object */
	function login()
	{
	}

	/** Logs out the user 
	 * $conn_id:  The connection id for the connection to work on.  Optionnal.
	 *  If  it is not present, the behaviour depends if the network supports
	 * multiple logins.  If it does not, all connections associated with the
	 * current user will be destroyed.  If it does, only the connections
	 * tied to the current node will be destroyed */
	function logout($conn_id = null)
	{
		global $db;
		$conn_id = $db->escapeString($conn_id);
		if (!empty ($conn_id))
		{
			$db->ExecSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);

			$user = User :: getObject($info['user_id']);
			$network = $user->getNetwork();
			$splash_user_id = $network->getSplashOnlyUser()->getId();
			$this->acctStop($conn_id);
		}
		else
		{
			$user = User :: getCurrentUser();
			$network = $user->getNetwork();
			$splash_user_id = $network->getSplashOnlyUser()->getId();
			if ($splash_user_id != $user->getId() && $node = Node :: getCurrentNode())
			{
				//Try to destroy all connections tied to the current node
				$sql = "SELECT conn_id FROM connections WHERE user_id = '{$user->getId()}' AND node_id={$node->getId()} AND token_status='".TOKEN_INUSE."';\n";
			$conn_rows = null;
				$db->ExecSql($sql, $conn_rows, false);
			if($conn_rows)
			{
				foreach ($conn_rows as $conn_row)
				{
					$this->acctStop($conn_row['conn_id']);
				}
			}
			}
		}

		if ($splash_user_id != $user->getId() && $network->getMultipleLoginAllowed() == false)
		{
			/* The user isn't the splash_only user and the network config does not allow multiple logins.  
			 * Logging in with a new token implies that all other active tokens should expire */
			$sql = "SELECT conn_id FROM connections WHERE user_id = '{$user->getId()}' AND token_status='".TOKEN_INUSE."';\n";
			$conn_rows = null;
			$db->ExecSql($sql, $conn_rows, false);
			if($conn_rows)
			{
				foreach ($conn_rows as $conn_row)
			{
				$this->acctStop($conn_row['conn_id']);
			}
			}
		}
		global $session;
		$session->destroy();

	}

	/** Start accounting traffic for the user 
	 * $conn_id:  The connection id for the connection to work on */
	function acctStart($conn_id)
	{
		//$info['conn_id']
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);
		$network = Network :: getObject($info['network_id']);
		$splash_user_id = $network->getSplashOnlyUser()->getId();
		$auth_response = $info['account_status'];
		/* Login the user */
		$mac = $db->EscapeString($_REQUEST['mac']);
		$ip = $db->EscapeString($_REQUEST['ip']);
		$sql = "UPDATE connections SET "."token_status='".TOKEN_INUSE."',"."user_mac='$mac',"."user_ip='$ip',"."last_updated=NOW()"."WHERE conn_id='{$conn_id}';\n";
		$db->ExecSqlUpdate($sql, false);
		if ($splash_user_id != $info['user_id'] && $network->getMultipleLoginAllowed() == false)
		{
			/* The user isn't the splash_only user and the network config does not allow multiple logins.  
			 * Logging in with a new token implies that all other active tokens should expire */
			$token = $db->EscapeString($_REQUEST['token']);
			$sql = "SELECT * FROM connections WHERE user_id = '{$info['user_id']}' AND token_status='".TOKEN_INUSE."' AND token!='$token';\n";
			$conn_rows = array ();
			$db->ExecSql($sql, $conn_rows, true);
			foreach ($conn_rows as $conn_row)
			{
				$this->acctStop($conn_row['conn_id']);
			}
		}

		/* Delete all unused tokens for this user, so we don't fill the database with them */
		$sql = "DELETE FROM connections "."WHERE token_status='".TOKEN_UNUSED."' AND user_id = '{$info['user_id']}';\n";
		$db->ExecSqlUpdate($sql, false);
	}

	/** Update traffic counters
	 * $conn_id: The connection id for the connection to work on */
	function acctUpdate($conn_id, $incoming, $outgoing)
	{
		// Write traffic counters to database
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUpdate("UPDATE connections SET "."incoming='$incoming',"."outgoing='$outgoing',"."last_updated=NOW() "."WHERE conn_id='{$conn_id}'");
	}

	/** Final update and stop accounting
	 * $conn_id:  The connection id (the token id) for the connection to work on
	 * */
	function acctStop($conn_id)
	{
		// Stop traffic counters update
		global $db;
		$conn_id = $db->escapeString($conn_id);
		$db->ExecSqlUpdate("UPDATE connections SET "."timestamp_out=NOW(),"."token_status='".TOKEN_USED."' "."WHERE conn_id='{$conn_id}';\n", false);
	}

	/**
	 * Property method that tells if the class allows registration
	 */
	function isRegistrationPermitted()
	{
		return false;
	}

} // End class
?>