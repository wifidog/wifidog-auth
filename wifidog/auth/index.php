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
  /**@file index.php
   * This is the main auth handler, be very carefull while editing this file.
   * @author Copyright (C) 2004 Benoit Grégoire et Philippe April
   */
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';

$auth_response = ACCOUNT_STATUS_DENIED;
$auth_message = '';

$token = $db->EscapeString($_REQUEST['token']);
$db->ExecSqlUniqueRes("SELECT *, CASE WHEN ((NOW() - reg_date) > interval '".VALIDATION_GRACE_TIME." minutes') THEN true ELSE false END AS validation_grace_time_expired FROM users,connections WHERE users.user_id=connections.user_id AND connections.token='$token'", $info, false);
if ($info != null)
  {
    if ($_REQUEST['stage']== STAGE_LOGIN)
      {
	if ($info['token_status'] == TOKEN_UNUSED) 
	  {
	  	/* This is for the 15 minutes validation period, the exact same code is also present in when the stage is counters.  If you update this one don't forget to update the other one! */
		if (($info['account_status'] == ACCOUNT_STATUS_VALIDATION) && ($info['validation_grace_time_expired']=='t')) 
		  {
		    $auth_response = ACCOUNT_STATUS_VALIDATION_FAILED;
		    $auth_message .= "| The validation grace period which began at ".$info['reg_date']." has now expired. ";
		  }
		else
		  {


	    $auth_response = $info['account_status'];
	    /* Login the user */
	    $mac = $db->EscapeString($_REQUEST['mac']);
	    $ip = $db->EscapeString($_REQUEST['ip']);
	    $sql = "UPDATE connections SET " .
	      "token_status='" . TOKEN_INUSE . "'," .
	      "user_mac='$mac'," .
	      "user_ip='$ip'," .
	      "last_updated=NOW()" .
	      "WHERE conn_id='{$info['conn_id']}';\n";
	    $db->ExecSqlUpdate($sql, false);
	    
	    /* Logging in with a new token implies that all other active tokens should expire */
	    $sql = "UPDATE connections SET " .
	      "timestamp_out=NOW(), token_status='" . TOKEN_USED . "' " .
	      "WHERE user_id = '{$info['user_id']}' AND token_status='" . TOKEN_INUSE . "' AND token!='$token';\n";
	    $db->ExecSqlUpdate($sql, false);
	    /* Delete all unused tokens for this user, so we don't fill the database with them */
	    $sql = "DELETE FROM connections "
	      . "WHERE token_status='" . TOKEN_UNUSED . "' AND user_id = '{$info['user_id']}';\n";
	    $db->ExecSqlUpdate($sql, false);
	    		  }
	  }
	else
	  {
	    $auth_message .= "| Tried to login with a token that wasn't TOKEN_UNUSED. ";
	  }
      }
    else if($_REQUEST['stage']==STAGE_LOGOUT || $_REQUEST['stage']==STAGE_COUNTERS)
      {
	if($_REQUEST['stage']==STAGE_LOGOUT)
	  {
	    $db->ExecSqlUpdate("UPDATE connections SET " .
			       "timestamp_out=NOW()," .
			       "token_status='" . TOKEN_USED . "' " .
			       "WHERE conn_id='{$info['conn_id']}';\n");
	    $auth_message .= "| User is now logged out. ";
	  }
	
	if( $_REQUEST['stage']==STAGE_COUNTERS)
	  {
	    if ($info['token_status'] == TOKEN_INUSE)
	      {
		/* This is for the 15 minutes validation period, the exact same code is also present in when the stage is login.  If you update this one don't forget to update the other one! */
		if (($info['account_status'] == ACCOUNT_STATUS_VALIDATION) && ($info['validation_grace_time_expired']=='t')) 
		  {
		    $auth_response = ACCOUNT_STATUS_VALIDATION_FAILED;
		    $auth_message .= "| The validation grace period which began at ".$info['reg_date']." has now expired. ";
		  }
		else
		  {
		    $auth_response = $info['account_status'];
		  }
	      }

	  }
	
	if (!empty($_REQUEST['incoming']) || !empty($_REQUEST['outgoing']))
	  {
	    $incoming = $db->EscapeString($_REQUEST['incoming']);
	    $outgoing = $db->EscapeString($_REQUEST['outgoing']);

	    if (($incoming >= $info['incoming']) &&
		($outgoing >= $info['outgoing'])) 
	      {
		$db->ExecSqlUpdate("UPDATE connections SET " .
				   "incoming='$incoming'," .
				   "outgoing='$outgoing'," .
				   "last_updated=NOW() " .
				   "WHERE conn_id='{$info['conn_id']}'"
				   );
		    $auth_message .= "| Updated counters. ";
	      }
	    else
	      {
		    $auth_message .= "| Warning:  Incoming or outgoing counter is smaller than what is stored in the database; counters not updated. ";

	      }
	  }
	else
	  {
	    $auth_message .= "| Incoming or outgoing counter is missing; counters not updated. ";
	  }
      }
    else
      {
	$auth_message .= "| Error: Unknown stage. ";
	$auth_response = ACCOUNT_STATUS_ERROR;
      }
  }
else
  {
    $auth_message .= "| Error: couldn't find the requested token. ";
    $auth_response = ACCOUNT_STATUS_ERROR;
  }

echo "Auth: $auth_response\n";
echo "Messages: $auth_message\n"
?>