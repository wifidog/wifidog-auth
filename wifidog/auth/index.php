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
   * @author Copyright (C) 2004 Benoit Grégoire et Philippe April
   */
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';

$auth_response = 0;
$db->ExecSqlUniqueRes("SELECT * FROM users,connections WHERE users.user_id=connections.user_id AND connections.token='{$_REQUEST['token']}' LIMIT 1", $info);
if ($info != null)
  {
    switch($_REQUEST['stage'])
      {
      case STAGE_LOGIN:
	if ($info['token_status'] == TOKEN_UNUSED) 
	  {
	    $auth_response = $info['account_status'];

	    /* Logging in implies that all other tokens should expire */
	    $sql = '';
	    $sql .= "UPDATE connections SET " .
	      "timestamp_out=NOW()," .
	      "token_status='" . TOKEN_USED . "' " .
	      "WHERE user_id = '{$info['user_id']}' " .
	      "AND token_status='" . TOKEN_INUSE . "';\n";
	    
	    $sql .= "UPDATE connections SET " .
	      "token_status='" . TOKEN_INUSE . "'," .
	      "user_mac='{$_REQUEST['mac']}'," .
	      "user_ip='{$_REQUEST['ip']}' " .
	      "WHERE conn_id='{$info['conn_id']}' LIMIT 1;\n";
	    
	    $sql .= "UPDATE users SET " .
	      "online_status='" . ONLINE_STATUS_ONLINE . "'" .
	      "WHERE user_id='{$info['user_id']}' LIMIT 1;\n";
	    $db->ExecSqlUpdate($sql);
	  }
	break;

      case STAGE_LOGOUT:
	$sql = '';
	$sql .= "UPDATE connections SET " .
	  "timestamp_out=NOW()," .
	  "token_status='" . TOKEN_USED . "' " .
	  "WHERE conn_id='{$info['conn_id']}' LIMIT 1;\n";
	
	$sql .= "UPDATE users SET " .
	  "online_status='" . ONLINE_STATUS_OFFLINE . "'" .
	  "WHERE user_id='{$info['user_id']}' LIMIT 1;\n";
	$db->ExecSqlUpdate($sql);
	break;
	
      case STAGE_COUNTERS:
	if ($info['token_status'] == TOKEN_INUSE) {
	  $auth_response = $info['account_status'];

	  /* This is for the 15 minutes validation period */
	  if (($info['account_status'] == ACCOUNT_STATUS_VALIDATION) && (time() >= ($info['reg_date'] + (60*15)))) 
	    {
	      $info['account_status'] = ACCOUNT_STATUS_VALIDATION_FAILED;
	      $db->ExecSqlUpdate("UPDATE users SET account_status='{$info['account_status']}' WHERE user_id='{$info['user_id']}'");
	    }

	  if ($_REQUEST['incoming'] && $_REQUEST['outgoing'])
	    {
	      if (($_REQUEST['incoming'] > $info['incoming']) ||
		  ($_REQUEST['outgoing'] > $info['outgoing'])) 
		{
		  $db->ExecSqlUpdate("UPDATE connections SET " .
				     "incoming='{$_REQUEST['incoming']}'," .
				     "outgoing='{$_REQUEST['outgoing']}' " .
				     "WHERE conn_id='{$info['conn_id']}' LIMIT 1"
				     );
		}
	    }
	}

	break;

      default:
	echo "Unknown stage";
	break;
      }// End switch
  }

echo "Auth: $auth_response\n";
?>
