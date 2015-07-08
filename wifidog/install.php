<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * WiFiDog Authentication Server installation and configuration script
 *
 * @package    WiFiDogAuthServer
 * @author     Pascal Leclerc <isf@plec.ca>, Robin Jones and Benoit Grégoire
 * @copyright  2005-2006 Pascal Leclerc, 2006-2007 Technologies Coeus inc., 2007 Robin Jones
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once ('include/path_defines_base.php');
empty ($_REQUEST['page']) ? $page = 'Welcome' : $page = $_REQUEST['page']; # The page to be loaded
empty ($_REQUEST['action']) ? $action = '' : $action = $_REQUEST['action']; # The action to be done (in page)
empty ($_REQUEST['debug']) ? $debug = 0 : $debug = $_REQUEST['debug']; # Use for MySQL debugging
empty ($_REQUEST['config']) ? $config = '' : $config = $_REQUEST['config']; # Store data to be saved in config.php

# Security : Minimal access validation is use by asking user to retreive a random password in a local file. This prevent remote user to access unprotected installation script. It's dummy, easy to implement and better than nothing.

# Random password generator
$temp_dir = function_exists('sys_get_temp_dir') ? sys_get_temp_dir ( ) : '/tmp';
$password_file = $temp_dir . '/dog_cookie.txt';
$random_password = null;
if (!file_exists($password_file)) {
    srand(date("s"));
    $possible_charactors = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $password = "";
    while (strlen($random_password) < 8) {
        $random_password .= substr($possible_charactors, rand() % (strlen($possible_charactors)), 1);
    }
    $fd = fopen($password_file, 'w');
    fwrite($fd, $random_password."\n");
    fclose($fd);
}

#Read password file
$fd = fopen($password_file, "rb");
$password = trim(fread($fd, filesize($password_file)));
fclose($fd);

$auth = false;

if ($page != 'Welcome') {
    if (isset ($_SERVER['PHP_AUTH_PW'])) {
        #echo "PHP_AUTH_USER=(" . $_SERVER['PHP_AUTH_USER'] . ") PHP_AUTH_PW=(" . $_SERVER['PHP_AUTH_PW'] . ")"; # DEBUG
        if ($password == $_SERVER['PHP_AUTH_PW'])
        $auth = true;
    }
}
else
$auth = true;

if (!$auth) { 	# Ask user for the password
    header('WWW-Authenticate: Basic realm="Private"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Restricted Access - Authorisation Required!";
    exit;
}
# End of Security validation


/************************************************************************************
 * Begin Dynamic HTML Page Header
 ************************************************************************************/
print<<<EndHTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML>
<HEAD>
  <TITLE>$page - Wifidog Auth-server installation and configuration</TITLE>

  <SCRIPT type="text/javascript">
    // This function adds a new configuration value to the "config" hidden input
    // On submit, the config will be parsed and the value saved to the config.php file
    function newConfig(dataAdd) {
      // TODO : Validate input data
      if (document.myform.config.value == '') {
        document.myform.config.value = dataAdd;
      }
      else {
        document.myform.config.value = document.myform.config.value + '|' + dataAdd;
      }
      //alert(document.myform.config.value);  // DEBUG
    }
  </SCRIPT>
		<script src="js/formutils.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="media/base_theme/stylesheet.css" />
</HEAD>
<BODY id='page' class='{$page}'>

<style type="text/css">
<!--

.submit
{
  font-size: 14pt;
  text-decoration: none;
  padding: 3px 5px 3px 5px;
  background-color: #ccccff;
}

table {
border-collapse: collapse;
}

-->
</style>


<FORM NAME="myform" METHOD="post">
<INPUT TYPE="HIDDEN" NAME="action">
<INPUT TYPE="HIDDEN" NAME="debug">
<INPUT TYPE="HIDDEN" NAME="config">
<div id="page_body">
    <div id="left_area">
        <div id="left_area_top">
            <h1>Installation status</h1>

EndHTML;
$pageindex = array("Welcome"=>"Welcome",
        "Prerequisites"=>"Prerequisites",
        "Permissions"=>"Permissions",
            "Dependencies"=> "Dependencies",
            "Database Access"=> "Database",
        "Database Connection"=>"testdatabase",
        "Database Initialisation"=>"dbinit",
        "Global Options"=>"options",
        "Language Locale"=>"languages",
        "User Creation"=>"admin",
        "Review"=>"Review",
        "Finish"=>"finish",);
foreach($pageindex as $pagekey => $pagevalue){
    if ($pagevalue != $page){
        print "$pagekey <br>";
    }
    else {
        print "<strong>$pagekey</strong> <br>";
    }
}
print<<<EndHTML
        </div>
        <div id="left_area_bottom">
		<p><A HREF="#" ONCLICK="javascript: document.myform.page.value='changelog'; document.myform.submit();">Change Log</A><BR>
            <a href="http://dev.wifidog.org/report/10">Known issues</a></p>
        </div>
    </div>
<div id="main_area">

    <div id="main_area_top">
        <table align="center"><tr><td><img src="media/base_theme/images/wifidog_install_banner.png" /></td></tr></table>
    </div>
    <div id="main_area_middle">
EndHTML;

//print "<pre>";      # DEBUG
//print_r($_SERVER);  # DEBUG
//print_r($_REQUEST); # DEBUG
//print "</pre>";     # DEBUG
#exit();

#Begin Perquisite Array 	(Needed files/directories with write access)
$dir_array = array (
'',
'tmp',
'tmp/simplepie_cache',
'lib/',
'tmp/smarty/templates_c',
'tmp/smarty/cache',
'tmp/openidserver',
'lib/simplepie',
'lib/feedpressreview',
'config.php'
);
#end perquisite array


#Begin Global Options Array
$optionsInfo = array (
/*********************************************************************************************
TODO:  SSL is now configured in the DB, but should still be handled by the install script
'SSL_AVAILABLE' => array (
'title' => 'SSL Support',
'depend' => 'return 1;',
'message' => '&nbsp;'
),
************************************************************************/
'CONF_USE_CRON_FOR_DB_CLEANUP' => array (
'title' => 'Use cron for DB cleanup',
'depend' => 'return 1;',
'message' => '&nbsp;'
),
'GMAPS_HOTSPOTS_MAP_ENABLED' => array (
'title' => 'Google Maps Support',
'depend' => 'return 1;',
'message' => '&nbsp;'
),
'LOG_CONTENT_DISPLAY' => array (
'title' => 'Log what content is displayed to users',
'depend' => 'return 1;',
'message' => '&nbsp;'
)
);



$CONFIG_FILE = 'config.php';
$LOCAL_CONFIG_FILE = 'local.config.php';

if (!empty ($config)) # If not empty, save javascript 'config' variable to config.php file
saveConfig($config);

### Read Configuration file. Keys and Values => define('FOO', 'BRAK');
# Use config.php if local.config.php does not exist
//if(!file_exists(WIFIDOG_ABS_FILE_PATH."$LOCAL_CONFIG_FILE"))
$contentArray = file(WIFIDOG_ABS_FILE_PATH . "$CONFIG_FILE");
//else
//  $contentArray = file(WIFIDOG_ABS_FILE_PATH."$LOCAL_CONFIG_FILE");

$configArray = array ();

foreach ($contentArray as $line) {
    #print "$line<BR>"; # Debug
    if (preg_match("/^define\((.+)\);/", $line, $matchesArray)) {
        //echo '<pre>';print_r($matchesArray);echo '</pre>';
        list ($key, $value) = explode(',', $matchesArray[1]);
        $pattern = array (
        "/^'/",
        "/'$/"
        );
        $replacement = array (
        '',
        ''
        );
        $key = preg_replace($pattern, $replacement, trim($key));
        $value = preg_replace($pattern, $replacement, trim($value));
        $configArray[$key] = $value;
    }
}
//echo '<pre>';print_r($configArray);echo '</pre>';
# Database connections variables
$CONF_DATABASE_HOST = $configArray['CONF_DATABASE_HOST'];
$CONF_DATABASE_PORT = $configArray['CONF_DATABASE_PORT'];
$CONF_DATABASE_NAME = $configArray['CONF_DATABASE_NAME'];
$CONF_DATABASE_USER = $configArray['CONF_DATABASE_USER'];
$CONF_DATABASE_PASSWORD = $configArray['CONF_DATABASE_PASSWORD'];

//foreach($configArray as $key => $value) { print "K=$key V=$value<BR>"; } exit(); # DEBUG

###################################
# array (array1(name1, page1), array2(name2, page2), ..., arrayN(nameN, pageN));
function navigation($dataArray) {
    $SERVER = $_SERVER['HTTP_HOST'];
    $SCRIPT = $_SERVER['SCRIPT_NAME'];
    print "<p><br></p>";
    foreach ($dataArray as $num => $navArray) {
        $title = $navArray['title'];
        $page = $navArray['page'];
        empty ($navArray['action']) ? $action = '' : $action = $navArray['action'];
        print<<<EndHTML
<A HREF="#" ONCLICK="document.myform.page.value = '$page'; document.myform.action.value = '$action'; document.myform.submit();" CLASS="submit">$title</A>

EndHTML;
        if (array_key_exists($num +1, $dataArray))
        print "&nbsp; &nbsp;";
    }

}

###################################
#
function refreshButton() {
    print<<<EndHTML

<p><A HREF="#" ONCLICK="javascript: window.location.reload(true);" CLASS="submit">Refresh</A></p>
EndHTML;
}


###################################
#
/*function debugButton() {
 print <<<EndHTML
 <p><INPUT TYPE="button" VALUE="Debug" ONCLICK="javascript: window.location.reload(true);"></p>
 EndHTML;
 } */

###################################
#
function saveConfig($data) {
    print "<!-- saveConfig DATA=($data) -->\n"; # DEBUG

    global $CONFIG_FILE;

    $contentArray = file(WIFIDOG_ABS_FILE_PATH . "$CONFIG_FILE");
    $fd = fopen(WIFIDOG_ABS_FILE_PATH . "$CONFIG_FILE", 'w');

    $defineArrayToken = array ();
    $defineArrayToken = explode('|', $data);

    foreach ($defineArrayToken as $nameValue) {
        list ($name, $value) = explode('=', $nameValue);
        $defineArray[$name] = $value; # New define value ($name and value)
        #print "K=$name V=$value<BR>"; # DEBUG
    }

    foreach ($contentArray as $line) {
        #print "L=$line<BR>\n";
        // maybe more than one define stentences
        $may_be_more_than_one_define_sentences = explode(";", $line );
        
        for($may_be_more_than_one_define_sentences as $line){
            // remove possible existed blanks
            $no_more_blanks_line = trim($line);
            $line = $no_more_blanks_line . ";";
            
            if (preg_match("/^define\((.+)\);/", $line, $matchesArray)) {
            	list ($key, $value) = explode(',', $matchesArray[1]);
            	$pattern = array (
                    "/^'/",
                    "/'$/"
                );
            	$replacement = array (
                    '',
                    ''
                );
                $key = preg_replace($pattern, $replacement, trim($key));
                //$value = preg_replace($pattern, $replacement, trim($value));

                if (array_key_exists($key, $defineArray)) { // A new value is defined
                    #print "$key EXISTS<BR>";
                    #print "define => (" . $defineArray[$key] . ")<BR>";
                    $pattern = array (
                    "/^\\\'/",
                    "/\\\'$/"
                    );
                    $replacement = array (
                    "'",
                    "'"
                    );
                    $value = preg_replace($pattern, $replacement, trim($defineArray[$key]));
                    #print "(define('$key', $value);)<BR>";
                    fwrite($fd, "define('$key', $value);\n"); # Write the new define($name, $value)
                }
                else { // The key does not exist (no new value to be saved)
                    fwrite($fd, $line); # Write the same line in config.php
                }
            }else {
                fwrite($fd, $line); # Write the line (not a define line). Ex: Commented text
            }
        }
        
       
    }
}



/********************************************************************************
 * MAIN PROCEDURE																*
 * case statement to navigate through install script
 *																				*
 *********************************************************************************/
switch ($page) {
    case 'Permissions' :
        print "<h1>Folder Permissions</h1>";

        if (function_exists(posix_getpwuid)) {
            $process_info_user_id = posix_getpwuid(posix_getuid());
        }

        if ($process_info_user_id) {
            $process_username = $process_info_user_id['name'];
        } else {
            // Posix functions aren't available on windows or couldn't be read
            $process_username = 'unknown_user';
        }

        if (function_exists(posix_getgrgid)) {
            $process_info_group_id = posix_getgrgid(posix_getegid());
        }

        if ($process_info_group_id) {
            $process_group = $process_info_group_id['name'];
        } else {
            //Posix functions aren't available on windows or couldn't be read
            $process_group = 'unknown_group';
        }

        $cmd_mkdir = '';
        $cmd_chown = '';
        $error = 0;

        print "<p><em>HTTP daemon UNIX username/group</em>: $process_username/$process_group</p>";
        #    print "<p><em>HTTPD group</em>: $process_group<BR</p>";
        print "<p><table BORDER=\"1\"><tr><td><b>Directory</b></td></td><td><b>Owner</b></td><td><b>Writable</b></td></tr>\n";

        foreach ($dir_array as $dir) {
            print "<tr><td>$dir</td>";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH . "$dir")) {
                print "<TD COLSPAN=\"2\" STYLE=\"text-align:center; background:red;\">Missing</td></tr>\n";
                $cmd_mkdir .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                $cmd_chown .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                $error = 1;
                continue;
            }

            if (function_exists(posix_getpwuid)) {
                $dir_info = posix_getpwuid(fileowner(WIFIDOG_ABS_FILE_PATH . "$dir"));
            }

            if ($dir_info) {
                $dir_owner_username = $dir_info['name'];
            } else {
                //Posix functions aren't available on windows or couldn't be read
                $dir_owner_username = fileowner(WIFIDOG_ABS_FILE_PATH . "$dir");
            }

            print "<td>$dir_owner_username</td>";

            if (is_writable(WIFIDOG_ABS_FILE_PATH . "$dir")) {
                print "<td STYLE='background:lime;'>YES</td>";
            } else {
                print "<td STYLE='background:red;'>NO</td>";
                $cmd_chown .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                $error = 1;
            }

            print "</tr>\n";
        }
        print "</table>\n";

        if ($error != 1) {
            navigation(array (
            array (
                "title" => "Back",
                "page" => "Prerequisites"
                ),
                array (
                "title" => "Next",
                "page" => "Dependencies"
                )
                ));
        }
        else {
            refreshButton();
            navigation(array (
            array (
                "title" => "Back",
                "page" => "Prerequisites"
                )
                ));
                print "<p>UNIX user <em>$process_username</em> must be able to write to these directories (mkdir, chown or chmod)</p>";
                if (!empty ($cmd_mkdir) || !empty ($cmd_mkdir))
                print "<p><b>For instance, you may want to use the following commands</b> :</p>\n";
                if (!empty ($cmd_mkdir))
                print "mkdir $cmd_mkdir <br />";
                if (!empty ($cmd_chown))
                print "chgrp -R $process_group $cmd_chown;<br/>chmod g+wx $cmd_chown;<br/>";
                print "<p>After permission modifications have been preformed, click the REFRESH button to check they have been completed successfully. The NEXT button will then appear to continue with the installation.";
        }
        break;
        ###########################################
    case 'Dependencies' :
        print "<h1>Checking Dependencies</h1>";
        $error = 0;
        $userData['error']=&$error;
        require_once("classes/DependenciesList.php");
        print DependenciesList::getAdminUIStatic($userData);
        refreshButton();
        if ($error != 1) {
            navigation(array (
            array (
                "title" => "Back",
                "page" => "Permissions"
                ),
                array (
                "title" => "Next",
                "page" => "Database"
                )
                ));
        }

        break;

        ###########################################
    case 'Database' :
        ### TODO : Valider en javascript que les champs soumit ne sont pas vide
        #          Pouvoir choisir le port de la DB ???
        print<<< EndHTML
<h1>Database Access Configuration</h1>
<BR>
<table border="1">
  <tr><td>Host</td><td><INPUT type="text" name="CONF_DATABASE_HOST" value="$CONF_DATABASE_HOST"></td></tr>
  <tr><td>Port</td><td><INPUT type="text" name="CONF_DATABASE_PORT" value="$CONF_DATABASE_PORT"></td></tr>
  <tr><td>DB Name</td><td><INPUT type="text" name="CONF_DATABASE_NAME" value="$CONF_DATABASE_NAME"></td></tr>
  <tr><td>Username</td><td><INPUT type="text" name="CONF_DATABASE_USER" value="$CONF_DATABASE_USER"></td></tr>
  <tr><td>Password</td><td><INPUT type="text" name="CONF_DATABASE_PASSWORD" value="$CONF_DATABASE_PASSWORD"></td></tr>
</table>

<p>By clicking Next, your configuration will be automatically saved.</p>

<script type="text/javascript">
  function submitDatabaseValue() {
    newConfig("CONF_DATABASE_HOST='" + document.myform.CONF_DATABASE_HOST.value + "'");
    newConfig("CONF_DATABASE_PORT='" + document.myform.CONF_DATABASE_PORT.value + "'");
    newConfig("CONF_DATABASE_NAME='" + document.myform.CONF_DATABASE_NAME.value + "'");
    newConfig("CONF_DATABASE_USER='" + document.myform.CONF_DATABASE_USER.value + "'");
    newConfig("CONF_DATABASE_PASSWORD='" + document.myform.CONF_DATABASE_PASSWORD.value + "'");
  }
</script>

EndHTML;

        navigation(array (
        array (
            "title" => "Back",
            "page" => "Dependencies"
            ),
            )); #, array("title" => "Next", "page" => "testdatabase")));
            print<<< EndHTML
<A HREF="#" ONCLICK="javascript: document.myform.page.value='testdatabase'; submitDatabaseValue(); document.myform.submit();" CLASS="submit">Next</A>

EndHTML;

            break;
            ###########################################
    case 'testdatabase' :
        print "<h1>Database connection</h1>";
        /* TODO : Tester la version minimale requise de Postgresql                */

        print "<UL><LI>Trying to open a Postgresql database connection : ";

        $conn_string = "host=$CONF_DATABASE_HOST port=$CONF_DATABASE_PORT dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
        $ptr_connexion = pg_connect($conn_string);

        if ($ptr_connexion == TRUE) {
            print "Success!<BR>";
        }
        else {
            printf ("<p>Unable to connect to database!  Please make sure the server is online and the database \"%s\" exists. Also 'postgresql.conf' and 'pg_hba.conf' must allow the user \"%s\" to open a connection to it on host \"%s\" port %d to continue.  See the error above for clues on what the problem may be.</p>", $CONF_DATABASE_NAME, $CONF_DATABASE_PORT, $CONF_DATABASE_USER, $CONF_DATABASE_HOST);
            print "<p>Please go back and retry with correct values, or fix your server configuration.</p>";
            refreshButton();
            navigation(array(array("title" => "Back", "page" => "Database")));
            #die(); - causes inability go go back and change values
            break;
        }
        print "</li>";
        print "<li>";
        $postgresql_info = pg_version();
        printf ("PostgreSQL server version: %s", $postgresql_info['server']);        print "</li>";

        #        if ($postgresql_info['server'] > $requiredPostgeSQLVersion) { Todo : Do something }


        print "</UL>";
        refreshButton();
        navigation(array (
        array (
            "title" => "Back",
            "page" => "Database"
            ),
            array (
            "title" => "Next",
            "page" => "dbinit"
            )
            ));
            break;
            ###########################################
    case 'dbinit' :
        print "<h1>Database Initialisation</h1>";
        # SQL are executed with PHP, some lines need to be commented out.
        $file_db_version = 'UNKNOW';
        $patterns[0] = '/CREATE DATABASE wifidog/';
        $patterns[1] = '/\\\connect/';
        //The following is strictly for compatibility with postgresql 7.4
        $patterns[2] = '/COMMENT/';
        $patterns[3] = '/^SET /m';
        $patterns[4] = '/CREATE PROCEDURAL LANGUAGE/';
        $patterns[5] = '/ALTER SEQUENCE/';
        $patterns[6] = '/::regclass/';//To fix incompatibility of postgres < 8.1 with later nextval() calling convention
        $replacements[0] = '-- ';
        $replacements[1] = '-- ';
        $replacements[2] = '-- ';
        $replacements[3] = '-- ';
        $replacements[4] = '-- ';
        $replacements[5] = '-- ';
        $replacements[6] = '::text';

        $content_schema_array = file(WIFIDOG_ABS_FILE_PATH . "../sql/wifidog-postgres-schema.sql") or die("<em>Error</em>: Can not open $basepath/../sql/wifidog-postgres-schema.sql"); # Read SQL schema file
        $content_schema = implode("", $content_schema_array);
        $content_data_array = file(WIFIDOG_ABS_FILE_PATH . "../sql/wifidog-postgres-initial-data.sql"); # Read SQL initial data file
        $content_data = implode("", $content_data_array);

        $db_schema_version = ''; # Schema version query from database
        $file_schema_version = ''; # Schema version from define(REQUIRED_SCHEMA_VERSION) in schema_validate.php

        $conn_string = "host=$CONF_DATABASE_HOST port=$CONF_DATABASE_PORT dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
        $connection = pg_connect($conn_string) or die(); # or die("Couldn't Connect ==".pg_last_error()."==<BR>");

        if (preg_match("/\('schema_version', '(\d+)'\);/", $content_data, $matchesArray)) # Get schema_version from initial data file
        $file_db_version = $matchesArray[1];

        $contentArray = file(WIFIDOG_ABS_FILE_PATH . "include/schema_validate.php");
        foreach ($contentArray as $line) {
            #print "$line<BR>"; # Debug
            if (preg_match("/^define\('REQUIRED_SCHEMA_VERSION', (\d+)\);/", $line, $matchesArray)) {
                #print "REQUIRED_SCHEMA_VERSION = " . $matchesArray[1] . "<BR>";
                $file_schema_version = $matchesArray[1];
            }
        }

        # Get current database schema version (if defined)
        $schemaVersionSql = "SELECT * FROM schema_info WHERE tag='schema_version'";

        if (!@ pg_query($connection, $schemaVersionSql)) { # The @ remove warning display

            print "<UL><LI>Database did not exist, creating wifidog database schema : ";
            $content_schema = preg_replace($patterns, $replacements, $content_schema); # Comment bad SQL lines
            //echo "<pre>$content_schema</pre>";
            $result = pg_query($connection, $content_schema) or die("<em>" . pg_last_error() . "</em> <=<BR>");
            print "OK</li>";

            print "<LI>Adding wifidog initial data to database: ";
            $content_data = preg_replace($patterns, $replacements, $content_data); # Comment bad SQL lines

            $result = pg_query($connection, $content_data) or die("<em>" . pg_last_error() . "</em> <=<BR>");
            print "OK</li>";
            print "</UL>";

        }

        if ($result = @ pg_query($connection, $schemaVersionSql)) { # The @ remove warning display
            $result_array = pg_fetch_all($result);
            $db_shema_version = $result_array[0]['value'];

            print "<p>On <em>$CONF_DATABASE_HOST:$CONF_DATABASE_PORT</em>, Database <em>$CONF_DATABASE_NAME</em> exists and is ";
            if ($db_shema_version == $file_schema_version) {
                print "up to date (shema version <em>$db_shema_version</em>).";
            }
            elseif ($db_shema_version < $file_schema_version) {
                print "at schema version <em>$db_shema_version</em>. The required schema version is <em>$file_schema_version</em>. Triggering database schema upgrade\n";


                require_once (dirname(__FILE__) . '/include/common.php');
                require_once ('classes/AbstractDb.php');
                require_once ('classes/Session.php');
                require_once ('include/schema_validate.php');
                validate_schema();

            }
            else {
                print "Error : Unexpected result";
                exit ();
            }

        }

        navigation(array (
        array (
            "title" => "Back",
            "page" => "testdatabase"
            ),
            array (
            "title" => "Next",
            "page" => "options"
            )
            ));
            break;
            ###########################################
    case 'options' :
        # TODO : Tester que la connection SSL est fonctionnelle
        #        Options avancees : Supporter les define de [SMARTY|PHLICKR|JPGRAPH]_REL_PATH
        print<<< EndHTML
<h1>Available Options</h1>
  <table border="1">

EndHTML;

        //echo '<pre>';print_r($optionsInfo);echo '</pre>';
        foreach ($optionsInfo as $name => $foo) { # Foreach generate all <table> fields
            $value = $configArray[$name]; # Value of option in config.php
            $title = $optionsInfo[$name]['title']; # Field Title
            $message = $optionsInfo[$name]['message']; # Message why option is disabled
            if(empty($value))
            $message .= ", ERROR: unable to find the '$name' directive in the config file";
            $depend = @ eval ($optionsInfo[$name]['depend']); # Evaluate the dependencie
            $selectedTrue = '';
            $selectedFalse = ''; # Initialise value
            $value == 'true' ? $selectedTrue = 'SELECTED' : $selectedFalse = 'SELECTED'; # Use to select the previous saved option
            $depend == 1 ? $disabled = '' : $disabled = 'DISABLED'; # Disable <SELECT> if dependencie is not satisfied
            $jscript = "<script type=\"text/javascript\"> newConfig(\"$name=false\"); </script>\n"; # Use to save a failed dependencie (option=false)
            if ($disabled == '') # Dependencie ok, erase $jscript value
            $jscript = '';

            print<<< EndHTML
  <tr>
    <td>$title</td>
    <td><SELECT name="$name" $disabled>
          <OPTION value="true" $selectedTrue>true</OPTION>
          <OPTION value="false" $selectedFalse>false</OPTION>
        </SELECT>
    </td>
    <td>$message</td>
  </tr>
  $jscript
EndHTML;
        } # End or foreach
        print<<< EndHTML
    </table>

<script type="text/javascript">
  function submitOptionsValue() {

EndHTML;

        foreach ($optionsInfo as $name => $foo) { # Generate the javascript to save value on submit
            print<<< EndHTML
    if (!document.myform.$name.disabled)
      newConfig("$name=" + document.myform.$name.value);

EndHTML;
        } # End Foreach

        print<<< EndHTML
  }
</script>

EndHTML;
        navigation(array (
        array (
            "title" => "Back",
            "page" => "dbinit"
            )
            ));

            print<<<EndHTML
<A HREF="#" ONCLICK="javascript: document.myform.page.value='languages'; submitOptionsValue(); document.myform.submit();" CLASS="submit">Next</A>
EndHTML;

            break;
            ###########################################
    case 'languages' :

        require_once('classes/LocaleList.php');

        #check for current language code in config.php
        $CURRENT_LOCALE = $configArray['DEFAULT_LANG'];

        #pull a list of all available languages (codes and real friendly names)
        $AVAIL_LOCALE_ARRAY = LocaleList::getAvailableLanguageArray();


        print "<h1>Languages Configuration</h1>";



        print<<<EndHTML

      <p>Please select the Authentication Servers default language and locale</p>

       <div class="language">
               <div>Default Server Locale:
    <select name="default_locale" onchange="newConfig('DEFAULT_LANG=' + this.options[this.selectedIndex].value);">
EndHTML;
        #for each language in the array get the language code and the friendly name
        foreach ($AVAIL_LOCALE_ARRAY as $_langIds => $_langNames) {
            #if the current local in config.php is the same as the current member of the array, select it as default
            if ($CURRENT_LOCALE == $_langIds) {
                $_selected = (' selected="selected"');
            } else {#else leave it alone
                $_selected = "";
            }
            #add the options to the combobox (hidden value= language code, user friendly name [0]multilingual [1]English
            echo'<option value="' . $_langIds . '"' . $_selected . '>'.$_langNames[1].'</option>';
        }
        print<<<EndHTML
        </select>
               </div>
       </div>




<br><br><br><br>
<strong>Common Error message:</strong> <BR>
<p>This is an example of message you may see in the top of your working auth-server IF the languagepacks on your server have not been installed. In most Unix/Linux system, you could use locale -a to list all available locales on the server and run "apt-get install locales-all" for full language support</p>

<DIV style="border:solid black;">Warning: language.php: Unable to setlocale() to fr, return value: , current locale: LC_CTYPE=en_US.UTF-8;LC_NUMERIC=C; [...]</DIV>


EndHTML;

        navigation(array (
        array (
            "title" => "Back",
            "page" => "options"
            ),
            array (
            "title" => "Next",
            "page" => "admin"
            )
            ));
            break;
            ###########################################
    case 'admin' :
        print "<h1>Administration accounts</h1>";
        # TODO : Allow to create more than one admin account and list the current admin users
        #        Allow admin to choose to show or not is username
        empty ($_REQUEST['username']) ? $username = 'admin' : $username = $_REQUEST['username'];
        empty ($_REQUEST['password']) ? $password = '' : $password = $_REQUEST['password'];
        empty ($_REQUEST['password2']) ? $password2 = '' : $password2 = $_REQUEST['password2'];
        empty ($_REQUEST['email']) ? $email = $_SERVER['SERVER_ADMIN'] : $email = $_REQUEST['email'];

        $conn_string = "host=$CONF_DATABASE_HOST port=$CONF_DATABASE_PORT dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
        $connection = pg_connect($conn_string) or die();

        $sql = "SELECT * FROM users NATURAL JOIN server_stakeholders";
        $result = pg_query($connection, $sql);
        $result_array = pg_fetch_all($result);
        $username_db = $result_array[0]['username'];

        if (empty ($username_db) && $action == 'create') {//Only allow creating an adminstrator if we don't already have one.  Otherwise we have a HUGE security hole.
            //      require_once(dirname(__FILE__) . '/config.php');
            require_once (dirname(__FILE__) . '/include/common.php');
            require_once (dirname(__FILE__) . '/classes/User.php');

            $created_user = User :: createUser(get_guid(), $username, Network :: getDefaultNetwork(), $email, $password);
            $user_id = $created_user->getId();

            # Add user to admin table, hide his username and set his account status to 1 (allowed)
            $sql = "INSERT INTO server_stakeholders (user_id, role_id, object_id) VALUES ('$user_id', 'SERVER_OWNER', 'SERVER_ID');\n";
            $sql .= "INSERT INTO network_stakeholders (user_id, role_id, object_id) VALUES ('$user_id', 'NETWORK_OWNER', 'default-network');\n";
            $sql .= "UPDATE users SET account_status='1' WHERE user_id='$user_id'";
            $result = pg_query($connection, $sql);
        }

        $sql = "SELECT * FROM users NATURAL JOIN server_stakeholders";
        $result = pg_query($connection, $sql);
        $result_array = pg_fetch_all($result);
        $username_db = $result_array[0]['username'];

        if (!empty ($username_db)) {#if a username exists
            print "<table>\n";
            print "<tr><th colspan=2>Your current administrator accounts are:</th></tr>\n";
            print "<tr><th>email</th><th>username</th></tr>\n";
            foreach($result_array as $arraykey => $arrayvalue1) {
                print "<tr><td>".$arrayvalue1['email']."</td><td>".$arrayvalue1['username']."</td></tr>\n";
            }
            print "</table>\n";
        }
        else {
            print"<strong>No current administrators exist, please create at least one...</strong>";
            print<<<EndHTML
        <p>
        <table>
        <tr>
          <td>Username</td><td><INPUT type="text" name="username" value="$username"></td>
        </tr>
        <tr>
          <td>Password</td><td><INPUT type="password" name="password"></td>
        </tr>
        <tr>
          <td>Password again</td><td><INPUT type="password" name="password2"></td>
        </tr>
        <tr>
          <td>Email</td><td><INPUT type="text" name="email" value="$email"></td>
        </tr>
        </table>

        <script type="text/javascript">
          
        
        function submitValue() {

        if (isEmpty(document.myform.username)) {
              alert('Please enter a username');
              exit();
            }
        if (document.myform.password.value != document.myform.password2.value) {
              alert('Password mismatch, Please retry');
              exit();
            }
        if (isEmpty(document.myform.password)) {
              alert('Please enter a valid password');
              exit();
            }

        if (!isValidPassword(document.myform.password)) {
              alert('Your password does not meet complexity requirements. 6 letters and/or numbers ');
              exit();
            }

        if (!isValidEmail(document.myform.email)) {
              alert('Please enter a valid email address');
              exit();
            }
        document.myform.page.value='admin';
        document.myform.action.value='create';
        document.myform.submit();
          }
        </script>
EndHTML;

            print "<p><A HREF=\"#\" ONCLICK=\"javascript: submitValue(); window.location.reload(true);\" CLASS=\"submit\">Create User</A></p><br><br><br>\n";

        }

        if (!empty ($username_db)) {
            navigation(array (
            array (
                "title" => "Back",
                "page" => "languages"
                ),
                array (
                "title" => "Next",
                "page" => "finish"
                )
                ));
        }
        else {
            navigation(array (
            array (
                "title" => "Back",
                "page" => "languages"
                )
                ));

        }
        break;
        ###########################################
    case 'finish' :
        $url = 'http://' . $_SERVER['HTTP_HOST'] . SYSTEM_PATH;
        print<<<EndHTML
  <h1>Thank you for choosing Wifidog</h1>
  <p>Redirecting you to your new WifiDog Authentification Server in 10 seconds</p>
  <meta http-equiv="REFRESH" content="10;url=$url">
  <p>For Help and documentation please visit <a href="http://www.wifidog.org">http://www.wifidog.org</a></p>
<pre>
               |\   /|              _
               |A\_/A|            z   z
            ___|     |           ( (o) )
           o    6     \           z _ z
           |___        \           /|
               |        \         / |
                \        \_______/  |
                |                   |
                |      WIFIDOG      |
                |   Captive Portal  |
                |   _____________   |
                |  /             \  |
                |  |             |  |
                |  |             |  |
              _/   |           _/   |
             ?_____|          ?_____|
</pre>
EndHTML;
        navigation(array(array("title" => "Back", "page" => "admin")));
        break;
        ###########################################
    case 'review' :
        print<<<EndHTML
<!-- /* Editor highlighting trick -->
<pre>
will show a table with all config.php options at the end of the installation
</pre>
<!-- Editor highlighting trick */ -->
EndHTML;
        break;
        ###########################################
    case 'Prerequisites' :
        print<<<EndHTML
<h1><strong>Before Continuing with the installation,</strong> please make sure the following has been completed:</h1>
<h2>1-Make sure you have created a valid PostgreSQL database and database user.</h2>
<p>Here is an example of creating these through the command line:</p>
<em>Create the PostgreSQL database user for WifiDog</em> (createuser and createdb need to be in you PATH) :
<pre>  <I>postgres@yourserver $></I> createuser wifidog --pwprompt
  Enter password for new user:
  Enter it again:
  Shall the new user be allowed to create databases? (y/n) n
  Shall the new user be allowed to create more new users? (y/n) n
  CREATE USER
</pre>
<em>Create the WifiDog database</em>
<pre>  <I>postgres@yourserver $></I> createdb wifidog --encoding=UTF-8 --owner=wifidog
  CREATE DATABASE
</pre>
<h2>2-Check that the paths were autodected properly</h2>
EndHTML;
        echo "<table>\n";
        echo "<tr><td>Absolute path to the /wifidog directory (WIFIDOG_ABS_FILE_PATH):</td><td>" . WIFIDOG_ABS_FILE_PATH . "</td></tr>\n";
        echo "<tr><td>URL path to reach the /wifidog directory with a web browser (SYSTEM_PATH): </td><td>" . SYSTEM_PATH . "</td></tr>\n";
        echo "<tr><td colspan=2><em>Please verify the two values above.</em> They should be autodetected correctly by wifidog using include/path_defines_base.php.  If there is a bug and they are not, you will need to override them manually in config.php (or find the bug), or your auth server will not work properly.  </td></tr></table>\n";


        navigation(array (
        array (
                "title" => "Back",
                "page" => "Welcome"
                ),
                array (
                "title" => "Next",
                "page" => "Permissions"
                )
                ));
                break;

                ###########################################


    case 'changelog' :
        echo "<h1>Change log</h1>";

print"<pre>";
        include WIFIDOG_ABS_FILE_PATH . '../CHANGELOG';
print"</pre>";

        navigation(array (
        array (
                "title" => "Back",
                "page" => "Welcome"
                ),
               ));
               break;
                ###########################################
                
    default :
        $WIFIDOG_VERSION = $configArray['WIFIDOG_VERSION'];
        print<<<EndHTML
<h1>Welcome to the WifiDog Auth-Server installation and configuration script.</h1>
<p>WiFiDog Authentication Server Version: <em>$WIFIDOG_VERSION</em>.</p>

<p>This Software is free. You can redistribute it and/or modify it in any way, under the terms of the GNU General Public Licence V2 or later, as published by the Free Software Foundation. This program is provided without any warranty (either implied or express) of any kind, as such, it is installed entirely at your own risk. More information can be found in the GNU General Public Licence. </p>

<h3>THIS PROGRAM IS STILL IN A BETA STAGE, PLEASE REPORT ANY BUGS AT <a href="http://dev.wifidog.org">http://dev.wifidog.org</a></h3>

<p>To Continue this installation you will need to enter the password found in the file: <em>$password_file</em> on your server's filesystem. Please enter it when prompted. This is to stop accidental or malicious activity from users gaining access to the install.php file if you dont move it from the base directory. <strong>PLEASE LEAVE THE USERNAME FIELD BLANK</strong></p>

EndHTML;

        navigation(array (
        array (
            "title" => "Next",
            "page" => "Prerequisites"
            )
            ));
}
echo "<input type='hidden' name='page' value='$_REQUEST[page]'/><br><br>" ;
?>
</div>
</div>
</div>
</form>
</body>
</html>

