<?php
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/SmartyWifidog.php';

$smarty = new SmartyWifidog;
$smarty -> SetTemplateDir('templates/');

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
	      $connection['token_status_description'] = $token_to_text[$connection['token_status']];
	      $smarty->append("connections", $connection);
	    }
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
