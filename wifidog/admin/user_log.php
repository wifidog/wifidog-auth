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
  /**@file
   * @author Copyright (C) 2004 Philippe April.
   */
  
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/SmartyWifidog.php';

$smarty = new SmartyWifidog;
$smarty -> SetTemplateDir('templates/');

$total = array();
$total['incoming'] = 0;
$total['outgoing'] = 0;

if (!empty($_REQUEST['user_id']))
  {
    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$_REQUEST[user_id]'",$userinfo,false);
    if (!$userinfo)
      {
	echo "<p class=warning>"._("Error: Unable to locate $_REQUEST[user_id] in the database.")."</p>\n";
	exit;
      }
    else
      {
	$userinfo['account_status_description'] = $account_status_to_text[$userinfo['account_status']]; 
	$smarty->assign("userinfo", $userinfo);
	
	$db->ExecSql("SELECT * FROM connections WHERE user_id='{$_REQUEST['user_id']}' ORDER BY timestamp_in", $connection_array, false);
	if ($connection_array)
	  {
	    foreach($connection_array as $connection)
	    {
	      $total['incoming'] += $connection['incoming'];
              $total['outgoing'] += $connection['outgoing'];
	      $connection['token_status_description'] = $token_to_text[$connection['token_status']];
	      $smarty->append("connections", $connection);
	    }
	    $smarty->assign("total", $total);
	  } 
	else
	  {
	    //No connections from user yet
	  }
	
	
      }
    $smarty->display("user_stats.html");
  }
else
  {
    $db->ExecSql("SELECT user_id FROM users ORDER BY user_id",$users_res);
    if ($users_res)
      {
	$users = array();
	foreach ($users_res as $row)
	{
	  $users[$row['user_id']] = $row['user_id'];
	}
	$smarty->assign("users_array", $users);
      }
    else 
      {
		echo "<p class=warning>"._('Internal error.')." 3</p>\n";
	exit;
      }

    $smarty->display("main.html");
  }
?>
