<?php
error_reporting(E_ALL);
require_once BASEPATH.'config.php';
require_once BASEPATH.'classes/AbstractDb.php';

global $db;
$db = new AbstractDb();

/* Gettext support */
if(!function_exists ('gettext'))
  {
    define('GETTEXT_AVAILABLE', false);
    /* Redefine the gettext functions if gettext isn't installed */
    function gettext($string)
    {
      return $string;
    }
    function _($string)
    {
      return $string;
    }
  }
else
  {
    define('GETTEXT_AVAILABLE', true);
  }

/* NEVER edit these, as they mush match the C code of the gateway */
define('ACCOUNT_STATUS_ERROR',		-1);
define('ACCOUNT_STATUS_DENIED',		0);
define('ACCOUNT_STATUS_ALLOWED',	1);
define('ACCOUNT_STATUS_VALIDATION',	5);
define('ACCOUNT_STATUS_VALIDATION_FAILED',	6);
define('ACCOUNT_STATUS_LOCKED',		254);

$account_status_to_text[ACCOUNT_STATUS_ERROR] = "Error";
$account_status_to_text[ACCOUNT_STATUS_DENIED] = "Denied";
$account_status_to_text[ACCOUNT_STATUS_ALLOWED] = "Allowed";
$account_status_to_text[ACCOUNT_STATUS_VALIDATION] = "Validation";
$account_status_to_text[ACCOUNT_STATUS_VALIDATION_FAILED] = "Validation Failed";
$account_status_to_text[ACCOUNT_STATUS_LOCKED] = "Locked";

define('TOKEN_UNUSED',		'UNUSED');
define('TOKEN_INUSE',		'INUSE');
define('TOKEN_USED',		'USED');

$token_to_text[TOKEN_UNUSED] = "Unused";
$token_to_text[TOKEN_INUSE] = "In use";
$token_to_text[TOKEN_USED] = "Used";

define('STAGE_LOGIN',	"login");
define('STAGE_LOGOUT',	"logout");
define('STAGE_COUNTERS',"counters");

define('ONLINE_STATUS_ONLINE',	1);
define('ONLINE_STATUS_OFFLINE',	2);

/* This section deals with PATHs */
if(empty($_REQUEST['gw_id']))
  {
    define('CURRENT_NODE_ID', DEFAULT_NODE_ID);
  }
else
  {
    define('CURRENT_NODE_ID', trim($_REQUEST['gw_id']));
  }

define('DEFAULT_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.DEFAULT_NODE_ID.'/');
define('DEFAULT_CONTENT_PHP_RELATIVE_PATH', BASEPATH.LOCAL_CONTENT_REL_PATH.DEFAULT_NODE_ID.'/');

define('NODE_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');
define('NODE_CONTENT_PHP_RELATIVE_PATH', BASEPATH.LOCAL_CONTENT_REL_PATH.CURRENT_NODE_ID.'/');

if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.STYLESHEET_NAME))
  {	
    define('STYLESHEET_URL',NODE_CONTENT_URL.STYLESHEET_NAME);
  }	
else
  {
    define('STYLESHEET_URL',DEFAULT_CONTENT_URL.STYLESHEET_NAME);
  }	

define('COMMON_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.'common/');
    
function gentoken()
{
  return md5(uniqid(rand(),1));
}

/** Cleanup dangling tokens and connections from the database, left if a gateway crashed, etc. */
function garbage_collect()
{
  global $db;

  // 10 minutes
  $expiration = time() - 60*10;
  $db -> ExecSqlUpdate ("UPDATE connections SET token_status='" . TOKEN_USED . "' WHERE UNIX_TIMESTAMP(last_updated) < $expiration");


  $db -> ExecSql("SELECT user_id FROM users WHERE online_status='" . ONLINE_STATUS_ONLINE . "'", $users);
  if($users!=null)
    {
      foreach ($users as $user)
      {
	$db -> ExecSqlUniqueRes("SELECT COUNT(*) FROM connections WHERE user_id='{$user['user_id']}' AND token_status='" . TOKEN_INUSE . "'",$count_row, false);
	if ($count_row['COUNT(*)'] != 1)
	  {
	    $db -> ExecSqlUpdate("UPDATE users SET online_status='" . ONLINE_STATUS_OFFLINE . "' WHERE user_id='{$user['user_id']}'", false);
	  }
      }
    }
}

/** Get the url from the local content_specific folder if the file exists, and from the default content folder otherwise */
function find_local_content_url($filename)
{
  if (is_file(NODE_CONTENT_URL.$filename))
    {
      return NODE_CONTENT_URL.$filename;
    }
  else
    {
      return DEFAULT_CONTENT_URL.$filename;
    }
}
?>
