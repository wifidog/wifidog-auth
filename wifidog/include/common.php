<?php
error_reporting(E_ALL);
require_once BASEPATH.'config.php';
require_once BASEPATH.'classes/AbstractDb.php';
require_once BASEPATH.'classes/Session.php';

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

/* This section deals with sessions */

define('SESS_USERNAME_VAR', 'SESS_USERNAME');
define('SESS_PASSWORD_HASH_VAR', 'SESS_PASSWORD_HASH');
define('SESS_ORIGINAL_URL_VAR', 'SESS_ORIGINAL_URL');
define('SESS_LANGUAGE_VAR', 'SESS_LANGUAGE');

/* Languages and sessions */
$lang_ids = array(
        "fr",
        "en"
    );
$lang_names = array(
        "Fran&ccedil;ais",
        "English"
    );

/* End */

/* This section deals with PATHs */
define('BASE_NON_SSL_PATH', 'http://' . $_SERVER['SERVER_NAME'] . SYSTEM_PATH);

if(SSL_AVAILABLE)
  {
    define('BASE_SSL_PATH', 'https://' . $_SERVER['SERVER_NAME'] . SYSTEM_PATH);
  }
else
  {
    define('BASE_SSL_PATH', BASE_NON_SSL_PATH);
  }
  
  /* If we actually ARE in SSL mode, make all URLS http:// to avoid security warnings. */
if(isset($_SERVER['HTTPS']))
  {
  define('BASE_URL_PATH', BASE_SSL_PATH); 
  }
  else
  {
  define('BASE_URL_PATH', BASE_NON_SSL_PATH);
  }
  
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

define('COMMON_CONTENT_URL', BASE_URL_PATH.LOCAL_CONTENT_REL_PATH.'common/');
    
/** Convert a password hash form a NoCat passwd file into the same format as get_password_hash().
* @return The 32 character hash.
*/
function convert_nocat_password_hash($hash)
{
 return $hash . '==';
}

function iso8601_date($unix_timestamp) {
   $tzd = date('O',$unix_timestamp);
   $tzd = substr(chunk_split($tzd, 3, ':'),0,6);
   $date = date('Y-m-d\TH:i:s', $unix_timestamp) . $tzd;
   return $date; 
}

/** Cleanup dangling tokens and connections from the database, left if a gateway crashed, etc. */
function garbage_collect()
{
  global $db;

  // 10 minutes
  $expiration = time() - 60*10;
  $expiration=iso8601_date($expiration);
  $db -> ExecSqlUpdate ("UPDATE connections SET token_status='" . TOKEN_USED . "' WHERE last_updated < '$expiration' AND token_status = '".TOKEN_INUSE."'", false);
}

/** Get the url from the local content_specific folder if the file exists, and from the default content folder otherwise */
function find_local_content_url($filename)
{
//echo "find_local_content_url():  Looking for:                  ".NODE_CONTENT_PHP_RELATIVE_PATH.$filename."<br>\n";
  if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.$filename))
    {
       $retval = NODE_CONTENT_URL.$filename;
    }
  else
    {
      $retval = DEFAULT_CONTENT_URL.$filename;
    }
    //echo "find_local_content_url():  Returned:                  $retval<br>\n";
    return $retval;
}
?>
