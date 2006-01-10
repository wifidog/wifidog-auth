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
 * @author     Pascal Leclerc <isf@plec.ca>
 * @copyright  2005 Pascal Leclerc <isf@plec.ca>
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once ('include/path_defines_base.php');

empty ($_REQUEST['page']) ? $page = 'Welcome' : $page = $_REQUEST['page']; # The page to be loaded
empty ($_REQUEST['action']) ? $action = '' : $action = $_REQUEST['action']; # The action to be done (in page)
empty ($_REQUEST['debug']) ? $debug = 0 : $debug = $_REQUEST['debug']; # Use for MySQL debugging
empty ($_REQUEST['config']) ? $config = '' : $config = $_REQUEST['config']; # Store data to be saved in config.php

# Security : Minimal access validation is use by asking user to retreive a random password in a local file. This prevent remote user to access unprotected installation script. It's dummy, easy to implement and better than nothing.

# Random password generator
$password_file = '/tmp/dog_cookie.txt';
if (!file_exists($password_file)) {
    srand(date("s"));
    $possible_charactors = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $password = "";
    while (strlen($random_password) < 8) {
        $random_password .= substr($possible_charactors, rand() % (strlen($possible_charactors)), 1);
    }
    $fd = fopen($password_file, 'w');
    fwrite($fd, $random_password);
    fclose($fd);
}

# Read password file
$fd = fopen($password_file, "rb");
$password = fread($fd, filesize($password_file));
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

if (!$auth) { # Ask user for the passorwd
    header('WWW-Authenticate: Basic realm="Private"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Authorization Required !";
    exit;
}
# End of Security validation

print<<<EndHTML
<HTML>
<HEAD>
  <TITLE>$page - Wifidog Auth-server configuration</TITLE>

  <SCRIPT type="text/javascript">
    // This function add new configuration value to the "config" hidden input
    // On submit, config will be parsed and value saved to config.php file
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

</HEAD>
<BODY text="black" bgcolor="#CFCFCF">

<style type="text/css">
<!--
.button
{
  font-size: 12pt;
  font-weight: bold;
  color: black;
  background: #D4D0C8;
  text-decoration: none;
  border-top: 1px solid white;
  border-right: 2px solid gray;
  border-bottom: 2px solid gray;
  border-left: 1px solid white;
  padding: 3px 5px 3px 5px;
}

td
{
  padding: 1px 4px 1px 4px;
}
//-->
</style>

<FORM NAME="myform" METHOD="post">
<INPUT TYPE="HIDDEN" NAME="page">
<INPUT TYPE="HIDDEN" NAME="action">
<INPUT TYPE="HIDDEN" NAME="debug">
<INPUT TYPE="HIDDEN" NAME="config">

EndHTML;

#print "<PRE>";      # DEBUG
#print_r($_SERVER);  # DEBUG
#print_r($_REQUEST); # DEBUG
#print "</PRE>";     # DEBUG
#exit();

# Minimal version needed
$requiredPHPVersion = '5.0.0';
$requiredMySQLVersion = '0.0.0'; // Todo
$requiredPostgeSQLVersion = '0.0.0'; // Todo

# Needed files/directories with write access
$dir_array = array ('tmp', 'tmp/magpie_cache', 'lib/smarty', 'tmp/smarty/templates_c', 'lib/magpie', 'lib/Phlickr', 'config.php');

$smarty_full_url    = 'http://smarty.php.net/do_download.php?download_file=Smarty-2.6.7.tar.gz';
$magpierss_full_url = 'http://easynews.dl.sourceforge.net/sourceforge/magpierss/magpierss-0.71.1.tar.gz';
$phlickr_full_url   = 'http://easynews.dl.sourceforge.net/sourceforge/phlickr/Phlickr-0.2.4.tgz';

$neededPackages = array(
  'smarty'    => array('needed' => 1, 'available' => 0, 'message' => '', 'file' => 'lib/smarty/Smarty.class.php'),
  'magpierss' => array('needed' => 0, 'available' => 0, 'message' => '', 'file' => 'lib/magpie/rss_fetch.inc'),
  'phlickr'   => array('needed' => 0, 'available' => 0, 'message' => '', 'file' => 'lib/Phlickr/Photo.php')
);

$neededExtentions = array(
  'xml'      => array('needed'    => 0,
                      'available' => 0,
                      'message'   => '<B>xml</B> extention is missing',
                      'note'      => 'Required for RSS support'),
  'pgsql'    => array('needed'    => 1,
                      'available' => 0,
                      'message'   => '<B>Posgresql</B> extention is missing',
                      'note'      => 'Required to connect to Postgresql database'),
  'mysql'    => array('needed'    => 0,
                      'available' => 0,
                      'message'   => '<B>MySQL</B> extention is missing',
                      'note'      => 'Required to connect to MySQL database (experimental and not working)'),
  'dom'      => array('needed'    => 1,
                      'available' => 0,
                      'message'   => '<B>DOM</B> extention is missing',
                      'note'      => 'Required if you want to export the list of HotSpots as a RSS feed'),
  'gettext'  => array('needed'    => 0,
                      'available' => 0,
                      'message'   => 'Gettext is unavailable, the auth-server will work, but you will loose internationalization',
                      'note'      => 'Internationalization support'),
  'mbstring' => array('needed'    => 1,
                      'available' => 0,
                      'message'   => '<B>mbstring</B> extention is missing',
                      'note'      => 'Required for core auth-server and RSS support'),
  'mcrypt'   => array('needed'    => 0,
                      'available' => 0,
                      'message'   => '<B>mcrypt</B> extention is missing',
                      'note'      => 'Required for RADIUS support'),
  'mhash'    => array('needed'    => 0,
                      'available' => 0,
                      'message'   => '<B>mhash</B> extention is missing',
                      'note'      => 'Required for RADIUS support'),
  'xmlrpc'   => array('needed'    => 0,
                      'available' => 0,
                      'message'   => '<B>xmlrpc</B> extention is missing',
                      'note'      => 'Required for RADIUS support')
);

$loadedExtensions = array_flip(get_loaded_extensions()); # Debug : An empty array for $loadedExtensions will show needed dependencies

$neededPEARPackages = array(
  'radius'      => array('needed' => 0, 'available' => 0, 'command' => "return dl('radius.so');",                'message' => 'Try in command line : pear install radius'),
  'Auth_RADIUS' => array('needed' => 0, 'available' => 0, 'command' => "return include_once 'Auth/RADIUS.php';", 'message' => 'Try in command line : pear install Auth_RADIUS'),
  'Crypt_CHAP'  => array('needed' => 0, 'available' => 0, 'command' => "return include_once 'Crypt/CHAP.php';",  'message' => 'Try in command line : pear install Crypt_CHAP (mhash and mcrypt extensions are needed)')
);

$optionsInfo = array(
  'SSL_AVAILABLE'                => array('title'   => 'SSL Support',
                                          'depend'  => 'return 1;',
                                          'message' => '&nbsp;'),
  'RSS_SUPPORT'                  => array('title'   => 'RSS Support',
                                          'depend'  => 'return ($neededExtentions[\'xml\'][\'available\'] && $neededPackages[\'magpierss\'][\'available\']);',
                                          'message' => 'Missing <B>xml</B> extentions or <B>MagpieRSS</B>'),
  'PHLICKR_SUPPORT'              => array('title'   => 'Flickr Photostream content support',
                                          'depend'  => 'return $neededPackages[\'phlickr\'][\'available\'];',
                                          'message' => '<B>Phlickr</B> library not installed'),
  'CONF_USE_CRON_FOR_DB_CLEANUP' => array('title'   => 'Use cron for DB cleanup',
                                          'depend'  => 'return 1;',
                                          'message' => '&nbsp;'),
  'XSLT_SUPPORT'                 => array('title'   => 'XSLT Support',
                                          'depend'  => 'return 1;',
                                          'message' => '&nbsp;'),
  'GMAPS_HOTSPOTS_MAP_ENABLED'   => array('title'   => 'Google Maps Support',
                                          'depend'  => 'return 1;',
                                          'message' => '&nbsp;')
);

foreach ($neededExtentions as $key => $value) { # Detect availables extentions
    if (array_key_exists($key, $loadedExtensions))
        $neededExtentions[$key]['available'] = 1;
}

foreach ($neededPEARPackages as $key => $value) { # Detect availables PEAR Packages
    if (@ eval ($neededPEARPackages[$key]['command']))
        $neededPEARPackages[$key]['available'] = 1;
}

foreach ($neededPackages as $key => $value) { # Detect installed libraries (smarty, ...)
    if (file_exists(WIFIDOG_ABS_FILE_PATH.$neededPackages[$key]['file']))
        $neededPackages[$key]['available'] = 1;
}

$CONFIG_FILE = 'config.php';
$LOCAL_CONFIG_FILE = 'local.config.php';

if (!empty ($config)) # If not empty, save javascript 'config' variable to config.php file
    saveConfig($config);

### Read Configuration file. Keys and Values => define('FOO', 'BRAK');
# Use config.php if local.config.php does not exist
//if(!file_exists(WIFIDOG_ABS_FILE_PATH."$LOCAL_CONFIG_FILE"))
$contentArray = file(WIFIDOG_ABS_FILE_PATH."$CONFIG_FILE");
//else
//  $contentArray = file(WIFIDOG_ABS_FILE_PATH."$LOCAL_CONFIG_FILE");

$configArray = array ();

foreach ($contentArray as $line) {
    #print "$line<BR>"; # Debug
    if (preg_match("/^define\((.+)\);/", $line, $matchesArray)) {
        list ($key, $value) = explode(',', $matchesArray[1]);
        $pattern = array ("/^'/", "/'$/");
        $replacement = array ('', '');
        $key = preg_replace($pattern, $replacement, trim($key));
        $value = preg_replace($pattern, $replacement, trim($value));
        $configArray[$key] = $value;
    }
}

# Database connections variables
$CONF_DBMS = $configArray['CONF_DBMS'];
$CONF_DATABASE_HOST = $configArray['CONF_DATABASE_HOST'];
$CONF_DATABASE_NAME = $configArray['CONF_DATABASE_NAME'];
$CONF_DATABASE_USER = $configArray['CONF_DATABASE_USER'];
$CONF_DATABASE_PASSWORD = $configArray['CONF_DATABASE_PASSWORD'];

//foreach($configArray as $key => $value) { print "K=$key V=$value<BR>"; } exit(); # DEBUG

###################################
# array (array1(name1, page1), array2(name2, page2), ..., arrayN(nameN, pageN));
# Todo : Supporter HTTP_REFERER (j'me comprends)
function navigation($dataArray) {
    $SERVER = $_SERVER['HTTP_HOST'];
    $SCRIPT = $_SERVER['SCRIPT_NAME'];
    print "\n<P>";
    foreach ($dataArray as $num => $navArray) {
        $title = $navArray['title'];
        $page = $navArray['page'];
        empty ($navArray['action']) ? $action = '' : $action = $navArray['action'];
        print<<<EndHTML
<A HREF="#" ONCLICK="document.myform.page.value = '$page'; document.myform.action.value = '$action'; document.myform.submit();" CLASS="button">$title</A>

EndHTML;
        if (array_key_exists($num +1, $dataArray))
            print "&nbsp;-&nbsp;";
    }
    print "</P>\n";
}

###################################
#
function refreshButton() {
    print<<<EndHTML

<P><A HREF="#" ONCLICK="javascript: window.location.reload(true);" CLASS="button">Refresh</A></P>
EndHTML;
}

###################################
#
/*function debugButton() {
  print <<<EndHTML
<P><INPUT TYPE="button" VALUE="Debug" ONCLICK="javascript: window.location.reload(true);"></P>
EndHTML;
} */

###################################
# In development
/*function installPackage($pkg_name, $full_url, $copy) {
    print "<H1>$pkg_name installation</H1>\n";
    chdir(WIFIDOG_ABS_FILE_PATH."tmp");
    list($url, $filename) = split ("=", $full_url);

    print "Download source code ($filename) : ";
    if (!file_exists($filename))
      exec("wget \"$url\"", $output, $return);
    if (!file_exists($filename)) // wget success if file exists
      exec("wget \"$full_url\" 2>&1", $output_array, $return);
    if (!file_exists($filename)) {
      print "<B STYLE=\"color:red\">Error</B><P>Current working directory : <B>$basepath/tmp/smarty</B>";
      $output = implode("\n", $output_array);
      print "<PRE><B>wget \"$full_url\"</B>\n$output</PRE>";
      exit();
    } else {
      print "OK<BR>";
    }

    print "Uncompressing : ";
    $dirname = array_shift(split(".tar.gz", $filename));

    if (!file_exists($dirname))
      exec("tar -xzf $dirname.tar.gz &>/tmp/tar.output", $output, $return);
    print "OK<BR>";

    exec("pwd", $output, $return);
    print "Copying : ";
    if (!file_exists('../../lib/smarty/Smarty.class.php'));
      exec("cp -r $dirname/libs/* ../../lib/smarty &>/tmp/cp.output", $output, $return);
      exec("cp -r $dirname/libs/* ../../lib/smarty &>/tmp/cp.output", $output, $return);
      $copy
    print "OK<BR>";
}*/

###################################
#
function saveConfig($data) {
    print "<!-- saveConfig DATA=($data) -->\n"; # DEBUG

    global $CONFIG_FILE;

    $contentArray = file(WIFIDOG_ABS_FILE_PATH."$CONFIG_FILE");
    $fd = fopen(WIFIDOG_ABS_FILE_PATH."$CONFIG_FILE", 'w');

    $defineArrayToken = array ();
    $defineArrayToken = explode('|', $data);

    foreach ($defineArrayToken as $nameValue) {
        list ($name, $value) = explode('=', $nameValue);
        $defineArray[$name] = $value; # New define value ($name and value)
        #print "K=$name V=$value<BR>"; # DEBUG
    }

    foreach ($contentArray as $line) {
        #print "L=$line<BR>\n";
        if (preg_match("/^define\((.+)\);/", $line, $matchesArray)) {
            list ($key, $value) = explode(',', $matchesArray[1]);
            $pattern = array ("/^'/", "/'$/");
            $replacement = array ('', '');
            $key = preg_replace($pattern, $replacement, trim($key));
            //$value = preg_replace($pattern, $replacement, trim($value));

            if (array_key_exists($key, $defineArray)) { // A new value is defined
                #print "$key EXISTS<BR>";
                #print "define => (" . $defineArray[$key] . ")<BR>";
                $pattern = array ("/^\\\'/", "/\\\'$/");
                $replacement = array ("'", "'");
                $value = preg_replace($pattern, $replacement, trim($defineArray[$key]));
                #print "(define('$key', $value);)<BR>";
                fwrite($fd, "define('$key', $value);\n"); # Write the new define($name, $value)
            }
            else { // The key does not exist (no new value to be saved)
                fwrite($fd, $line); # Write the same line in config.php
            }
        }
        else {
            fwrite($fd, $line); # Write the line (not a define line). Ex: Commented text
        }
    }
}

###################################
# MAIN
switch ($page) {
    case 'version' :
        /*  TODO : Valider qu'au moins une extention de DB est existante (pgsql, mysql, etc)
                   Definir les versions minimales de Posgres et MySQL */

        print "<H1>Version validation</H1>";
        print "<TABLE BORDER=\"1\">";

        $phpVersion = phpversion();
        $okMsg = '<TD ALIGN="CENTER" STYLE="background:lime;">OK</TD>';
        $errorMsg = '<TD ALIGN="CENTER" STYLE="background:red;">ERROR</TD>';
        $warningMsg = '<TD ALIGN="CENTER" STYLE="background:yellow;">Warning</TD>';
        $error = 0;

        print "<TR><TD>PHP</TD>";
        if (version_compare($phpVersion, $requiredPHPVersion, ">=")) {
            print "$okMsg<TD>$phpVersion</TD>"; // Version 5.0.0 or later
        }
        else {
            print "$errorMsg<TD>Version $requiredPHPVersion needed</TD>"; // Version < 5.0.0
            $error = 1;
        }
        print "</TR></TABLE><BR>";

        print "<TABLE BORDER=\"1\"><TR><TD><B>Extention</B></TD><TD><B>Status</B></TD><TD><B>Note</B></TD><TD><B>Message</B></TD></TR>";
        foreach ($neededExtentions as $key => $value) {
            print "<TR><TD><A HREF=\"http://www.php.net/$key\">$key</A></TD>";
            $note = $neededExtentions[$key]['note'];
            if ($neededExtentions[$key]['available']) {
                print "$okMsg<TD>$note</TD><TD>&nbsp;</TD></TR>";
            }
            else {
                $message = $neededExtentions[$key]['message'];
                if ($neededExtentions[$key]['needed'] == 1) {
                    print "$errorMsg<TD>$note</TD><TD>$message</TD></TR>";
                    $error = 1;
                }
                else {
                    print "$warningMsg<TD>$note</TD><TD>$message</TD></TR>";
                }
            }
        }
        print "</TABLE><BR>";

        /************************************
        * PEAR Components
        *************************************/
        print "<TABLE BORDER=\"1\"><TR><TD COLSPAN=\"3\"><CENTER><B>PEAR</B></CENTER></TD></TR>";
        print "<TR><TD><B>Component</B></TD><TD><B>Status</B></TD><TD><B>Note</B></TD></TR>";

        foreach ($neededPEARPackages as $key => $value) {
            $return = 0;
            print "<TR><TD><A HREF=\"http://pear.php.net/package/$key\">$key</A></TD>";
            if ($neededPEARPackages[$key]['available']) {
                print "$okMsg<TD>&nbsp;</TD></TR>";
            }
            else {
                $message = $neededPEARPackages[$key]['message'];
                if ($neededPEARPackages[$key]['needed'] == 1) {
                    print "$errorMsg<TD>$message</TD></TR>";
                    $error = 1;
                }
                else {
                    print "$warningMsg<TD>$message</TD></TR>";
                }
            }
        }
        print "</TABLE><BR>";

        /************************************
        * GD with PNG and JPEG support (will be needed by stats and graphics)
        *************************************/
        /*  $gdInfo = @gd_info();
            print "<TABLE BORDER=\"1\"><TR><TD><PRE>";
            print_r($gdInfo);
            print "</PRE></TD></TR></TABLE>";
        */

        #    if (!($neededExtentions['pgsql']['available'] && $neededExtentions['mysql']['available']))
        #      print "At least one DB extentions is nedded<BR>";

        //TODO: PostgreSQL and MySQL version are not validated";
        print "We recommend you use PostgreSQL 8.0 or newer";

        refreshButton();
        if ($error != 1) {
            navigation(array (array ("title" => "Next", "page" => "permission")));
        }

        break;
        ###################################
    case 'permission' :
        print "<H1>Permissions</H1>";

        $process_info_user_id = posix_getpwuid(posix_getuid());
        $process_info_group_id = posix_getgrgid(posix_getegid());
        $process_username = $process_info_user_id['name'];
        $process_group = $process_info_group_id['name'];
        $cmd_mkdir = '';
        $cmd_chown = '';
        $error = 0;

        print "<P><B>Installation directory</B>: ".WIFIDOG_ABS_FILE_PATH."</P>";
        print "<P><B>HTTP daemon UNIX username/group</B>: $process_username/$process_group</P>";
        #    print "<P><B>HTTPD group</B>: $process_group<BR</P>";
        print "<P><TABLE BORDER=\"1\"><TR><TD><B>Directory</B></TD></TD><TD><B>Owner</B></TD><TD><B>Writable</B></TD></TR>\n";

        foreach ($dir_array as $dir) {
            print "<TR><TD>$dir</TD>";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH."$dir")) {
                print "<TD COLSPAN=\"2\" STYLE=\"text-align:center;\">Missing</TD></TR>\n";
                $cmd_mkdir .= WIFIDOG_ABS_FILE_PATH."$dir ";
                $cmd_chown .= WIFIDOG_ABS_FILE_PATH."$dir ";
                $error = 1;
                continue;
            }

            $dir_info = posix_getpwuid(fileowner(WIFIDOG_ABS_FILE_PATH."$dir"));
            $dir_owner_username = $dir_info['name'];
            print "<TD>$dir_owner_username</TD>";

            if (is_writable(WIFIDOG_ABS_FILE_PATH."$dir")) {
                print "<TD>YES</TD>";
            }
            else {
                print "<TD>NO</TD>";
                $cmd_chown .= WIFIDOG_ABS_FILE_PATH."$dir ";
                $error = 1;
            }
            print "</TR>\n";
        }
        print "</TABLE>\n";

        if ($error != 1) {
            print "<P><B>Note:</B> Please validate that 'Installation directory' value is the right one. If this value is wrong, the PATH de automatic detection will not work as expected";
            navigation(array (array ("title" => "Back", "page" => "version"), array ("title" => "Next", "page" => "smarty")));
        }
        else {
            refreshButton();
            print "<P>You need to allow UNIX user <B>$process_username</B> to write to these directories (mkdir, chown or chmod)</P>";
            if (!empty ($cmd_mkdir))
                print "<P><B>For instance</B> : mkdir $cmd_mkdir";
            if (!empty ($cmd_chown))
                print "<P><B>For instance</B> : chown -R $process_username:$process_group $cmd_chown";
            print "<P>After permissions modification done, hit the REFRESH button to see the NEXT button and continue with the installation";
        }
        break;
        ###################################
    case 'smarty' : // Download, uncompress and install Smarty
        print<<< EndHTML
    <H1>Smarty template engine installation</H1>
    <P><A HREF="http://smarty.php.net/">Smarty</A> is Template Engine. WifiDog requires you install it before you continue.</P>
EndHTML;
        if ($neededPackages['smarty']['available']) {
            print "Already installed !<br/>";
        }
        else {
            chdir(WIFIDOG_ABS_FILE_PATH."tmp");
            list ($url, $filename) = split("=", $smarty_full_url);

            print "Download source code ($filename) : ";
            if (!file_exists($filename))
                exec("wget \"$smarty_full_url\" 2>&1", $output, $return);
            if (!file_exists($filename)) {
                print "<B STYLE=\"color:red\">Error</B><P>Current working directory : <B>$basepath/tmp/smarty</B>";
                $output = implode("\n", $output);
                print "<PRE><B>wget \"$smarty_full_url\"</B>\n$output</PRE>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dir_array = split(".tar.gz", $filename);
            $dirname = array_shift($dir_array);

            if (!file_exists($dirname))
                exec("tar -xzf $dirname.tar.gz &>/tmp/tar.output", $output, $return);
            print "OK<BR>";

            exec("pwd", $output, $return);
            print "Copying : ";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH."lib/smarty"));
            exec("cp -r $dirname/libs/* $basepath/lib/smarty &>/tmp/cp.output", $output, $return); # TODO : Utiliser SMARTY_REL_PATH
            print "OK<BR>";

            refreshButton();
        }
        navigation(array (array ("title" => "Back", "page" => "permission"), array ("title" => "Next", "page" => "magpierss")));
        break;
        ###################################
    case 'magpierss' : // Download, uncompress and install MagpieRSS
        print "<H1>MagpieRSS installation</H1>\n";

        if ($neededPackages['magpierss']['available']) {
            print "Already installed !<BR>";
            navigation(array (array ("title" => "Back", "page" => "smarty"), array ("title" => "Next", "page" => "phlickr")));
        }
        elseif ($action == 'install') {
            chdir(WIFIDOG_ABS_FILE_PATH."tmp");
            $filename_array = preg_split("/\//", $magpierss_full_url);
            $filename = array_pop($filename_array);

            print "Download source code ($filename) : ";
            if (!file_exists($filename))
                exec("wget \"$magpierss_full_url\" 2>&1", $output, $return);
            if (!file_exists($filename)) {
                print "<B STYLE=\"color:red\">Error</B><P>Current working directory : <B>$basepath/tmp/smarty</B>";
                $output = implode("\n", $output);
                print "<PRE><B>wget \"$magpierss_full_url\"</B>\n$output</PRE>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dir_array = split(".tar.gz", $filename);
            $dirname = array_shift($dir_array);
            if (!file_exists($dirname))
                exec("tar -xzf $dirname.tar.gz &>/tmp/tar.output", $output, $return);
            print "OK<BR>";

            print "Copying : ";
            exec("cp -r $dirname/* ../lib/magpie &>/tmp/cp.output", $output, $return); # TODO : Utiliser MAGPIE_REL_PATH
            print "OK<BR>";

            refreshButton();
            navigation(array (array ("title" => "Back", "page" => "smarty"), array ("title" => "Next", "page" => "phlickr")));
        }
        else {
            print<<< EndHTML
<P><A HREF="http://magpierss.sourceforge.net/">MagpieRSS</A> provides an XML-based (expat) RSS parser in PHP. MagpieRSS is needed by Wifidog for RSS feeds. It's is recommended to install MagpieRSS, if you don't, RSS feeds options will be disable.

<P>Do you want to install MagpieRSS ?
EndHTML;
            navigation(array (array ("title" => "Back", "page" => "smarty"), array ("title" => "Install", "page" => "magpierss", "action" => "install"), array ("title" => "Next", "page" => "phlickr")));
        }
        break;
        ###################################
    case 'phlickr' : // Download, uncompress and install phlickr library
        print "<H1>Phlickr installation</H1>\n";

        if ($neededPackages['phlickr']['available']) {
            print "Already installed !<BR>";
            navigation(array (array ("title" => "Back", "page" => "magpierss"), array ("title" => "Next", "page" => "database")));
        }
        elseif ($action == 'install') {
            chdir(WIFIDOG_ABS_FILE_PATH."tmp");
            $filename_array = preg_split("/\//", $phlickr_full_url);
            $filename = array_pop($filename_array);

            print "Download source code ($filename) : ";
            if (!file_exists($filename))
                exec("wget \"$phlickr_full_url\" 2>&1", $output, $return);
            if (!file_exists($filename)) { # Error occured, print output of wget
                print "<B STYLE=\"color:red\">Error</B><P>Current working directory : <B>$basepath/tmp/smarty</B>";
                $output = implode("\n", $output);
                print "<PRE><B>wget \"$phlickr_full_url\"</B>\n$output</PRE>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dirname_array = split(".tgz", $filename);
            $dirname = array_shift($dirname_array);
            if (!file_exists($dirname))
                exec("tar -xzf $dirname.tgz &>/tmp/tar.output", $output, $return);
            print "OK<BR>";

            print "Copying : ";
            if (!file_exists('../../lib/Phlickr/Photo.php'))
                exec("cp -r $dirname/* ../lib/Phlickr &>/tmp/cp.output", $output, $return); # TODO : Utiliser PHLICKR_REL_PATH
            print "OK<BR>";

            refreshButton();
            // Skipping jpgraph install
            navigation(array (array ("title" => "Back", "page" => "magpierss"), array ("title" => "Next", "page" => "database")));
        }
        else {
            print<<< EndHTML
<P><A HREF="http://phlickr.sourceforge.net/">Phlickr</A> is an Open Source PHP 5 interface to the Flickr API. <A HREF="http://flickr.com/">Flickr</A> is a digital photo sharing website. Phlickr allows WifiDog to display pictures from Flickr on its portal pages. Phlickr is thus an optional package..

<P>Do you want to install Phlickr ?
EndHTML;
            navigation(array (array ("title" => "Back", "page" => "magpierss"), array ("title" => "Install", "page" => "phlickr", "action" => "install"), array ("title" => "Next", "page" => "database")));
        }
        break;
        ###################################
    case 'jpgraph' : // Download, uncompress and install JpGraph library
        print "<H1>JpGraph installation</H1>\n";

        if ($neededPackages['jpgraph']['available']) {
            print "Already installed !<BR>";
            navigation(array (array ("title" => "Back", "page" => "phlickr"), array ("title" => "Next", "page" => "database")));
        }
        elseif ($action == 'install') {
            chdir(WIFIDOG_ABS_FILE_PATH."tmp");
            $filename = array_pop(preg_split("/\//", $jpgraph_full_url));

            print "Download source code ($filename) : ";
            if (!file_exists($filename))
                exec("wget \"$jpgraph_full_url\" 2>&1", $output, $return);
            if (!file_exists($filename)) { # Error occured, print output of wget
                print "<B STYLE=\"color:red\">Error</B><P>Current working directory : <B>$basepath/tmp</B>";
                $output = implode("\n", $output);
                print "<PRE><B>wget \"$jpgraph_full_url\"</B>\n$output</PRE>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dirname = array_shift(split(".tar.gz", $filename));
            if (!file_exists($dirname))
                exec("tar -xzf $dirname.tar.gz &>/tmp/tar.output", $output, $return);
            print "OK<BR>";

            print "Copying : ";
            if (!file_exists('../../lib/jpgraph/jpgraph.php'))
                exec("cp $dirname/src/* ../lib/jpgraph &>/tmp/cp.output", $output, $return); # TODO : Utiliser JPGRAPH_REL_PATH

            print "OK<BR>";

            refreshButton();
            navigation(array (array ("title" => "Back", "page" => "phlickr"), array ("title" => "Next", "page" => "database")));
        }
        else {
            print<<< EndHTML
<P><A HREF="http://www.aditus.nu/jpgraph/">JpGraph</A> is a Object-Oriented Graph creating library for PHP.
JpGraph is not currently use by Wifidog (will be use for statistique graph in a later version). You can skip this installation if your not a developper.

<P>Do you want to install JpGraph ?
EndHTML;
            navigation(array (array ("title" => "Back", "page" => "phlickr"), array ("title" => "Install", "page" => "jpgraph", "action" => "install"), array ("title" => "Next", "page" => "database")));
        }
        break;
        ###################################
    case 'database' :
        ### TODO : Valider en javascript que les champs soumit ne sont pas vide
        #          Pouvoir choisir le port de la DB ???
        print<<< EndHTML
<H1>Database access configuration</H1>
<BR>
<TABLE border="1">
  <TR><TD>DB</TD><TD><SELECT name="CONF_DBMS">

EndHTML;

        foreach ($configArray as $key => $value) { # In config.php, find all DBMS_* define
            if (preg_match("/^(DBMS_)(.*)/", $key, $matchesArray)) {
                $dbname_lower = strtolower($matchesArray[2]);
                if ($dbname_lower == 'postgres') # config.php use postgres and PHP use pgsql
                    $dbname_lower = 'pgsql';
                if ($neededExtentions[$dbname_lower]['available'] == 0) # Validate dependencie
                    continue;
                if ($CONF_DBMS == $key)
                    print "    <OPTION value=\"$key\" SELECTED>".$matchesArray[2]."</OPTION>\n";
                else
                    print "    <OPTION value=\"$key\">".$matchesArray[2]."</OPTION>\n";
            }
        }

        print<<< EndHTML
  </TD></TR>
  <TR><TD>Host</TD><TD><INPUT type="text" name="CONF_DATABASE_HOST" value="$CONF_DATABASE_HOST"></TD></TR>
  <TR><TD>DB Name</TD><TD><INPUT type="text" name="CONF_DATABASE_NAME" value="$CONF_DATABASE_NAME"></TD></TR>
  <TR><TD>Username</TD><TD><INPUT type="text" name="CONF_DATABASE_USER" value="$CONF_DATABASE_USER"></TD></TR>
  <TR><TD>Password</TD><TD><INPUT type="text" name="CONF_DATABASE_PASSWORD" value="$CONF_DATABASE_PASSWORD"></TD></TR>
</TABLE>

<P>By clicking Next, your configuration will be automaticaly saved

<script type="text/javascript">
  function submitDatabaseValue() {
    newConfig("CONF_DBMS=" + document.myform.CONF_DBMS.value);
    newConfig("CONF_DATABASE_HOST='" + document.myform.CONF_DATABASE_HOST.value + "'");
    newConfig("CONF_DATABASE_NAME='" + document.myform.CONF_DATABASE_NAME.value + "'");
    newConfig("CONF_DATABASE_USER='" + document.myform.CONF_DATABASE_USER.value + "'");
    newConfig("CONF_DATABASE_PASSWORD='" + document.myform.CONF_DATABASE_PASSWORD.value + "'");
  }
</script>

<P><B>Note</B> : MySQL support is currently broken</P>

EndHTML;

        navigation(array (array ("title" => "Back", "page" => "magpierss"))); #, array("title" => "Next", "page" => "testdatabase")));
        print<<< EndHTML
<P><A HREF="#" ONCLICK="javascript: document.myform.page.value='testdatabase'; submitDatabaseValue(); document.myform.submit();" CLASS="button">Next</A></P>

EndHTML;

        break;
        ###################################
    case 'testdatabase' :
        print "<H1>Database connection</H1>";
        /* TODO : Tester la version minimale requise de MySQL et Postgresql
                  Tester si MySQL supporte InnoDB                           */

        switch ($CONF_DBMS) {
            case 'DBMS_POSTGRES' :
                print "<UL><LI>Postgresql database connection : ";

                $conn_string = "host=$CONF_DATABASE_HOST dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
                $ptr_connexion = pg_connect($conn_string) or die(); # or die("Couldn't Connect ==".pg_last_error()."==<BR>");

                #if ($ptr_connexion == TRUE) {
                print "Success<BR>";
                #}
                #        } else {
                #          print "Unable to connect to database on $CONF_DATABASE_HOST<BR>The database must be online to continue.<BR>Please go back and retry with correct values";
                #          navigation(array(array("title" => "Back", "page" => "database")));
                #        }

                $postgresql_info = pg_version();
                #        $postgresql_info['server'];
                #        if ($postgresql_info['server'] > $requiredPostgeSQLVersion) { Todo : Do something }

                print "</UL>";
                refreshButton();
                navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Next", "page" => "dbinit")));
                break;
                ###################################
            case 'DBMS_MYSQL' :
                $ptr_connexion = @ mysql_connect($CONF_DATABASE_HOST, $CONF_DATABASE_USER, $CONF_DATABASE_PASSWORD);
                print "<UL>\n";

                if ($ptr_connexion == TRUE) {
                    print "<LI>MySQL database connection : Success";

                    $mysql_server_version = mysql_get_server_info();
                    print ("<LI>MySQL server version: $mysql_server_version");

                    #if ($mysql_server_version > $requiredMySQLVersion) { Todo : Do something }

                    #printf("<LI>MySQL host info: %s\n", mysql_get_host_info());

                    print "<LI>Select DB $CONF_DATABASE_NAME : ";
                    $select_db = mysql_select_db($CONF_DATABASE_NAME);

                    if ($select_db == TRUE) {
                        print "Success</UL>";
                        navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Next", "page" => "dbinit")));
                    }
                    else {
                        print "</UL>ERROR (Unable to select the database)<BR>";
                        refreshButton();
                        navigation(array (array ("title" => "Back", "page" => "database")));
                    }
                }
                else {
                    print "Unable to connect to database on <B>$CONF_DATABASE_HOST</B><BR>The database must be online to continue.<P>Please go back and retry with correct values";
                    refreshButton();
                    navigation(array (array ("title" => "Back", "page" => "database")));
                }
                break;
            default :
                print<<<EndHTML
          The CONF_DBMS value <B>$CONF_DBMS</B> is not currently suported by this install script.
EndHTML;
                navigation(array (array ("title" => "Back", "page" => "database")));
        }
        break;
        ###################################
    case 'dbinit' :
        print "<H1>Database initialisation</H1>";

        # SQL are executed with PHP, some lignes need to be commented
        $file_db_version = 'UNKNOW';
        $patterns[0] = '/CREATE DATABASE wifidog/';
        $patterns[1] = '/\\\connect/';
        $patterns[2] = '/COMMENT/';
        $patterns[3] = '/SET default_tablespace/';
        $patterns[4] = '/SET default_with_oids/';
        $replacements[0] = '-- ';
        $replacements[1] = '-- ';
        $replacements[2] = '-- ';
        $replacements[3] = '-- ';
        $replacements[4] = '-- ';

        $content_schema_array = file(WIFIDOG_ABS_FILE_PATH."../sql/wifidog-postgres-schema.sql") or die("<B>Error</B>: Can not open $basepath/../sql/wifidog-postgres-schema.sql"); # Read SQL schema file
        $content_schema = implode("", $content_schema_array);
        $content_data_array = file(WIFIDOG_ABS_FILE_PATH."../sql/wifidog-postgres-initial-data.sql"); # Read SQL initial data file
        $content_data = implode("", $content_data_array);

        $db_schema_version = ''; # Schema version query from database
        $file_schema_version = ''; # Schema version from define(REQUIRED_SCHEMA_VERSION) in schema_validate.php

        switch ($CONF_DBMS) {
            case 'DBMS_POSTGRES' :
                $conn_string = "host=$CONF_DATABASE_HOST dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
                $connection = pg_connect($conn_string) or die(); # or die("Couldn't Connect ==".pg_last_error()."==<BR>");

                if (preg_match("/\('schema_version', '(\d+)'\);/", $content_data, $matchesArray)) # Get schema_version from initial data file
                    $file_db_version = $matchesArray[1];

                $contentArray = file(WIFIDOG_ABS_FILE_PATH."include/schema_validate.php");
                foreach ($contentArray as $line) {
                    #print "$line<BR>"; # Debug
                    if (preg_match("/^define\('REQUIRED_SCHEMA_VERSION', (\d+)\);/", $line, $matchesArray)) {
                        #print "REQUIRED_SCHEMA_VERSION = " . $matchesArray[1] . "<BR>";
                        $file_schema_version = $matchesArray[1];
                    }
                }

                # Get current database schema version (if defined)
                $sql = "SELECT * FROM schema_info WHERE tag='schema_version'";
                if ($result = @ pg_query($connection, $sql)) { # The @ remove warning display
                    $result_array = pg_fetch_all($result);
                    $db_shema_version = $result_array[0]['value'];

                    print "<P>On <B>$CONF_DATABASE_HOST</B>, Database <B>$CONF_DATABASE_NAME</B> exists and is ";
                    if ($db_shema_version == $file_schema_version) {
                        print "up to date (shema version <B>$db_shema_version</B>).";
                        navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Next", "page" => "options")));
                    }
                    elseif ($db_shema_version < $file_schema_version) {
                        print "at schema version <B>$db_shema_version</B>. The required schema version is <B>$file_schema_version</B><P>Please upgrade the database";
                        navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Upgrade", "page" => "schema_validate"), array ("title" => "Next", "page" => "options")));
                    }
                    else {
                        print "Error : Unexpected result";
                    }
                    exit ();
                }

                print "<UL><LI>Creating wifidog database schema : ";
                $content_schema = preg_replace($patterns, $replacements, $content_schema); # Comment bad SQL lines

                $result = pg_query($connection, $content_schema) or die("<B>".pg_last_error()."</B> <=<BR>");
                print "OK";

                print "<LI>Creating wifidog database initial data : ";
                $content_data = preg_replace($patterns, $replacements, $content_data); # Comment bad SQL lines

                $result = pg_query($connection, $content_data) or die("<B>".pg_last_error()."</B> <=<BR>");
                print "OK</UL>";

                navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Next", "page" => "options")));
                break;
                ###################################
            case 'DBMS_MYSQL' :
                print "MYSQL ... (Not working)<BR>\n";
                $ptr_connexion = @ mysql_connect($CONF_DATABASE_HOST, $CONF_DATABASE_USER, $CONF_DATABASE_PASSWORD);
                $select_db = mysql_select_db($CONF_DATABASE_NAME);

                $previous_line = ''; # Used to remove "," on the line before CONSTRAINT removed line.

                if ($debug)
                    print "<PRE>";

                $inTable = 0;
                foreach ($content_schema_array as $lineNum => $line) {
                    #          if (preg_match("/^--/", $line)) continue; # Remove commented lines
                    #          if (preg_match("/^$/", $line))  continue; # Remove empty lines
                    if ($debug)
                        print "<B>ORI</B> $line";
                    if (preg_match("/^CREATE TABLE/", $line, $matchesArray))
                        $inTable = 1;
                    if ($inTable) {
                        if ($inTable && preg_match("/^\);$/", $line, $matchesArray)) {
                            #print "<B STYLE=\"color:#FF0000;\">OUT</B>\n";

                            # PG    => );
                            # MySQL => ) TYPE=InnoDB;
                            $line = preg_replace("/^\);$/", ") TYPE=InnoDB;", $line);
                            $inTable = 0;
                        }
                        else {
                            #print "<B>IN  \n</B>"; # The line is in CREATE TABLE

                            if (preg_match("/\s*CONSTRAINT.*\n$/", $line)) { # Remove CONSTRAINT. TODO : support constraint
                                $line = preg_replace("/\s*CONSTRAINT.*\n$/", "", $line);
                                $previous_line = preg_replace("/,$/", "", $previous_line);
                                #print "<B STYLE=\"color:#FF0000;\">ICI : L=$line PL=$previous_line</B>";
                            }
                            # Mettre TYPE=InnoDB uniquement pour table avec CONSTRAINT ???

                            # PG    =>  token_status character varying(10) NOT NULL
                            # MySQL => `token_status` character varying(10) NOT NULL
                            $line = preg_replace("/^(\s+)(\w+)/", "\${1}`\${2}`", $line);

                            $line = preg_replace("/DEFAULT ('.*')::character varying NOT NULL/", "NOT NULL default \${1}", $line);

                            $line = preg_replace("/text DEFAULT [\w':]+/", "text DEFAULT ''", $line); # MySQL does not support "text" default value
                            #??? Erreur : 1101 - BLOB/TEXT column 'venue_type' can't have a default value. Solution : Changer 'text' pour varchar ???

                            # PG    =>  token_status character varying(10) NOT NULL
                            # MySQL =>  token_status VARCHAR(10) NOT NULL
                            $line = preg_replace("/character varying\(/", "VARCHAR(", $line);

                            $line = preg_replace("/::character varying/", "", $line); # Remove string "::character varying"

                            # PG    => account_status integer,
                            # MySQL => account_status int,
                            $line = preg_replace("/integer/", "int", $line);

                            # TODO : Comprendre : Le timestamp de postgres est sous le format '2005-04-07 16:33:49.917127'
                            #                     datetime de MySQL est '0000-00-00 00:00:00'
                            $line = preg_replace("/timestamp without time zone/", "datetime", $line);

                            $line = preg_replace("/now\(\)/", "'NOW()'", $line);

                            #$line = preg_replace("/::text/", "", $line);

                            $line = preg_replace("/false/", "0", $line); # Change "false" strings for 0 (zero)
                            $line = preg_replace("/true/", "1", $line); # Change "true" strings for 1 (one)
                            $line = preg_replace("/WITHOUT OIDS/", "", $line); # Remove "WITHOUT OIDS"

                            # PG    => binary_data bytea,
                            # MySQL => binary_data MEDIUMBLOB
                            # Uploading, Saving and Downloading Binary Data in a MySQL Database http://www.onlamp.com/lpt/a/370
                            $line = preg_replace("/bytea/", "MEDIUMBLOB", $line); # maximum 16777215 (2^24 - 1) bytes
                        } ### End of else. Regex in CREATE TABLE {};
                    } ### End of if ($inTable).

                    # PG    => CREATE INDEX idx_token ON connections USING btree (token);
                    # MySQL => CREATE INDEX idx_token USING btree ON connections (token);
                    $line = preg_replace("/(ON \w+) USING btree/", "USING btree \${1}", $line);

                    # SQL-query : CREATE UNIQUE INDEX idx_unique_username_and_account_origin USING btree ON users(username,account_origin)
                    # MySQL said: #1170 - BLOB/TEXT column 'username' used in key specification without a key length
                    # Solution : http://www.dbforums.com/t1100992.html
                    $line = preg_replace("/CREATE UNIQUE INDEX idx_unique_username_and_account_origin USING btree ON users \(username, account_origin\);/", "CREATE UNIQUE INDEX idx_unique_username_and_account_origin USING btree ON users (username(100), account_origin(100));", $line);

                    $line = preg_replace("/CREATE INDEX idx_content_group_element_content_group_id USING btree ON content_group_element \(content_group_id\);/", "CREATE INDEX idx_content_group_element_content_group_id USING btree ON content_group_element (content_group_id(100));", $line);

                    if ($debug)
                        print "NEW $line";
                    $content_mysql .= $previous_line;
                    $previous_line = $line;
                } ### End of foreach ($content_schema_array as $lineNum => $line)

                $content_mysql .= $previous_line; # TODO: verif save the last line ?
                if ($debug)
                    print "<B STYLE=\"color:#FF0000;\">####################################################################</B>\n\n$content_mysql"; # Debug
                if ($debug)
                    print "</PRE>";

                #        $content_data = implode("", $content_mysql);

                $patterns[3] = '/SET client_encoding/';
                $patterns[4] = '/SET check_function_bodies/';
                $patterns[5] = '/SET search_path/';
                $replacements[3] = '-- ';
                $replacements[4] = '-- ';
                $replacements[5] = '-- ';

                $content_mysql = preg_replace($patterns, $replacements, $content_mysql);

                print "<PRE>$content_mysql</PRE>"; # Debug

                exit ();

                $result = mysql_query($content_mysql);
                if (!$result) {
                    die('Invalid query: '.mysql_error());
                }

                navigation(array (array ("title" => "Back", "page" => "database"), array ("title" => "Next", "page" => "dbinit")));
                break;
            default :
                print<<<EndHTML
          The CONF_DBMS value <B>$CONF_DBMS</B> is not currently suported by this install script.
EndHTML;
                navigation(array (array ("title" => "Back", "page" => "database")));
        }
        break;

        ###################################
    case 'schema_validate' :
        print "<H1>Database schema upgrade</H1>\n";

        require_once (dirname(__FILE__).'/include/common.php');

        require_once ('classes/AbstractDb.php');
        require_once ('classes/Session.php');
        require_once ('include/schema_validate.php');

        validate_schema();

        navigation(array (array ("title" => "Back", "page" => "dbinit"), array ("title" => "Next", "page" => "options")));

        //navigation(array(array("title" => "Back", "page" => "dbinit")));
        break;

        ###################################
    case 'options' :
        # TODO : Tester que la connection SSL est fonctionnelle
        #        Options avancees : Supporter les define de [SMARTY|MAGPIE|PHLICKR|JPGRAPH]_REL_PATH
        print<<< EndHTML
<H1>Options</H1>
  <TABLE border="1">

EndHTML;

        #$neededPackages['phlickr']['available'] = 0;
        #$neededExtentions['xml']['available'] = 0;
        foreach ($optionsInfo as $name => $foo) { # Foreach generate all <TABLE> fields
            $value = $configArray[$name]; # Value of option in config.php
            $title = $optionsInfo[$name]['title']; # Field Title
            $message = $optionsInfo[$name]['message']; # Message why option is disable
            $depend = @ eval ($optionsInfo[$name]['depend']); # Evaluate the dependencie
            $selectedTrue = '';
            $selectedFalse = ''; # Initialize value
            $value == 'true' ? $selectedTrue = 'SELECTED' : $selectedFalse = 'SELECTED'; # Use to select the previous saved option
            $depend == 1 ? $disabled = '' : $disabled = 'DISABLED'; # Disable <SELECT> if dependencie is not satisfied
            $jscript = "<script type=\"text/javascript\"> newConfig(\"$name=false\"); </script>\n"; # Use to save a failed dependencie (option=false)
            if ($disabled == '') # Dependencie ok, erase $jscript value
                $jscript = '';

            print<<< EndHTML
  <TR>
    <TD>$title</TD>
    <TD><SELECT name="$name" $disabled>
          <OPTION value="true" $selectedTrue>true</OPTION>
          <OPTION value="false" $selectedFalse>false</OPTION>
        </SELECT>
    </TD>
    <TD>$message</TD>
  </TR>
  $jscript
EndHTML;
        } # End or foreach
        print<<< EndHTML
    </TABLE>

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
        navigation(array (array ("title" => "Back", "page" => "dbinit")));

        print<<< EndHTML
<P><A HREF="#" ONCLICK="javascript: document.myform.page.value='languages'; submitOptionsValue(); document.myform.submit();" CLASS="button">Next</A></P>
EndHTML;

        break;

        ###################################
    case 'languages' :
        print "<H1>Languages configuration</H1>";
        print<<< EndHTML
      <P>Not yet implemented ...</P>
      <P>Will allow selecting language to use.</P>
<B>Error message example</B> : <BR>
<DIV style="border:solid black;">Warning: language.php: Unable to setlocale() to fr, return value: , current locale: LC_CTYPE=en_US.UTF-8;LC_NUMERIC=C; [...]</DIV>
<P><B>I repeat</B> : This is an example of message you can see in the top of your working auth-server if language are not set correctly. To change these values please edit <B>config.php</B> in auth-server install directory. Look for "Available locales" and "Default language" header in config.php.
EndHTML;
        //    exec("locale -a 2>&1", $output, $return);

        navigation(array (array ("title" => "Back", "page" => "options"), array ("title" => "Next", "page" => "radius")));
        break;

        ###################################
    case 'radius' :
        print "<H1>Radius Authenticator configuration</H1>";
        print "<P>Not yet implemented ...";

        # Dependencies
        #$neededExtentions['mcrypt']['available'];
        #$neededExtentions['mhash']['available'];
        #$neededExtentions['xmlrpc']['available'];
        #$neededPEARPackages['radius']['available'];
        #$neededPEARPackages['Auth_RADIUS']['available'];
        #$neededPEARPackages['Crypt_CHAP']['available'];

        navigation(array (array ("title" => "Back", "page" => "languages"), array ("title" => "Next", "page" => "admin")));
        break;

        ###################################
    case 'admin' :
        print "<H1>Administration account</H1>";
        # TODO : Allow to create more than one admin account and list the current admin users
        #        Allow admin to choose to show or not is username
        empty ($_REQUEST['username']) ? $username = 'admin' : $username = $_REQUEST['username'];
        empty ($_REQUEST['password']) ? $password = '' : $password = $_REQUEST['password'];
        empty ($_REQUEST['password2']) ? $password2 = '' : $password2 = $_REQUEST['password2'];
        empty ($_REQUEST['email']) ? $email = $_SERVER['SERVER_ADMIN'] : $email = $_REQUEST['email'];

        $conn_string = "host=$CONF_DATABASE_HOST dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
        $connection = pg_connect($conn_string) or die();

        if ($action == 'create') {
            //      require_once(dirname(__FILE__) . '/config.php');
            require_once (dirname(__FILE__).'/include/common.php');
            require_once (dirname(__FILE__).'/classes/User.php');

            $created_user = User :: createUser(get_guid(), $username, Network :: getDefaultNetwork(), $email, $password);
            $user_id = $created_user->getId();

            # Add user to admin table, hide his username and set his account status to 1 (allowed)
            $sql = "INSERT INTO administrators (user_id) VALUES ('$user_id'); UPDATE users SET  account_status='1', never_show_username=true WHERE user_id='$user_id'";
            $result = pg_query($connection, $sql);
        }

        $sql = "SELECT * FROM users NATURAL JOIN administrators WHERE account_origin = 'default-network'";
        $result = pg_query($connection, $sql);
        $result_array = pg_fetch_all($result);
        $username_db = $result_array[0]['username'];

        if (!empty ($username_db)) {
            print "<P>Your administrator user account is <B>$username_db</B>";
            navigation(array (array ("title" => "Back", "page" => "radius"), array ("title" => "Next", "page" => "network")));
        }
        else {
            print<<<EndHTML
        <P>
        <TABLE BORDER="1">
        <TR>
          <TD>Username</TD><TD><INPUT type="text" name="username" value="$username"></TD>
        </TR>
        <TR>
          <TD>Password</TD><TD><INPUT type="password" name="password"></TD>
        </TR>
        <TR>
          <TD>Password again</TD><TD><INPUT type="password" name="password2"></TD>
        </TR>
        <TR>
          <TD>Email</TD><TD><INPUT type="text" name="email" value="$email"></TD>
        </TR>
        </TABLE>

        <script type="text/javascript">
          function submitValue() {
            if (document.myform.password.value != document.myform.password2.value) {
              alert('Password mismatch, Please retry');
              exit();
            }
            if (document.myform.password.value == '') {
              alert('You need to type a password');
              exit();
            }
            if (document.myform.email.value == '') {
              alert('You need to type a email');
              exit();
            }
            document.myform.page.value='admin';
            document.myform.action.value='create';
            document.myform.submit();
          }
        </script>

EndHTML;
            navigation(array (array ("title" => "Back", "page" => "radius")));
            print "<P><A HREF=\"#\" ONCLICK=\"javascript: submitValue();\" CLASS=\"button\">Next</A></P>\n";
        }
        break;

        ###################################
    case 'network' :
        print "<H1>Network</H1>";

        //$HOTSPOT_NETWORK_NAME          = $configArray['HOTSPOT_NETWORK_NAME'];
        //$HOTSPOT_NETWORK_URL           = $configArray['HOTSPOT_NETWORK_URL'];
        //$TECH_SUPPORT_EMAIL            = $configArray['TECH_SUPPORT_EMAIL'];
        //$VALIDATION_GRACE_TIME         = $configArray['VALIDATION_GRACE_TIME'];
        //$VALIDATION_EMAIL_FROM_ADDRESS = $configArray['VALIDATION_EMAIL_FROM_ADDRESS'];

        /**
         * @deprecated version - Dec 26, 2005 - Needs to use network abstraction
         *
         *
         * <P>
        <TABLE border="1">
        <TR>
        <TD>Network Name</TD><TD><INPUT type="text" name="HOTSPOT_NETWORK_NAME" value="" size="30"></TD>
        </TR>
        <TR>
        <TD>Network URL</TD><TD><INPUT type="text" name="HOTSPOT_NETWORK_URL" value="" size="30"></TD>
        </TR>
        <TR>
        <TD>Tech Support Email</TD><TD><INPUT type="text" name="TECH_SUPPORT_EMAIL" value="" size="30"></TD>
        </TR>
        <TR>
        <TD>Validation Grace Time (min)</TD><TD><INPUT type="text" name="VALIDATION_GRACE_TIME" value="" size="30"></TD>
        </TR>
        <TR>
        <TD>Validation Email (send from)</TD><TD><INPUT type="text" name="VALIDATION_EMAIL_FROM_ADDRESS" value="" size="30"></TD>
        </TR>
        </TABLE>
         */
        print "Need to reimplement this... Until then connect to the administration pages and modify it by yourself.";

        print<<< EndHTML


<script type="text/javascript">
  function submitOptionsValue() {
    //newConfig("HOTSPOT_NETWORK_NAME='" + document.myform.HOTSPOT_NETWORK_NAME.value + "'");
    //newConfig("HOTSPOT_NETWORK_URL='" + document.myform.HOTSPOT_NETWORK_URL.value + "'");
    //newConfig("TECH_SUPPORT_EMAIL='" + document.myform.TECH_SUPPORT_EMAIL.value + "'");
    //newConfig("VALIDATION_GRACE_TIME=" + document.myform.VALIDATION_GRACE_TIME.value);
    //newConfig("VALIDATION_EMAIL_FROM_ADDRESS='" + document.myform.VALIDATION_EMAIL_FROM_ADDRESS.value + "'");
  }
</script>

EndHTML;

        navigation(array (array ("title" => "Back", "page" => "admin")));

        print<<< EndHTML
<P><A HREF="#" ONCLICK="javascript: document.myform.page.value='hotspot'; submitOptionsValue(); document.myform.submit();" CLASS="button">Next</A></P>
EndHTML;
        #navigation(array(array("title" => "Back", "page" => "admin"), array("title" => "Next", "page" => "hotspot")));
        break;

        ###################################
    case 'hotspot' :
        print "<H1>Hotspot</H1>";
        print "<P>A default hotspot has already been created<P>Use administration interface to add more hotspots.";
        navigation(array (array ("title" => "Back", "page" => "network"), array ("title" => "Next", "page" => "end")));
        break;

        ###################################
    case 'delete' :
        print<<<EndHTML
  <H1>Delete temporary files</H1>
  ...
EndHTML;
        #navigation(array(array("title" => "Back", "page" => "hotspot")));
        break;

        ###################################
    case 'end' :
        $url = 'http://'.$_SERVER['HTTP_HOST'].SYSTEM_PATH;
        print<<<EndHTML
  <H1>Thanks for using Wifidog</H1>
  Redirection to your new WifiDog Authentification Server in 3 seconds
  <meta http-equiv="REFRESH" content="3;url=$url">
  <PRE>

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
</PRE>
EndHTML;
        #navigation(array(array("title" => "Back", "page" => "hotspot")));
        break;

        ###################################
    case 'toc' :
        print "<H1>Table of content</H1>";
        $contentArray = file(__file__); # Read myself
        print "<UL>\n";
        foreach ($contentArray as $line) {
            if (preg_match("/^  case '(\w+)':/", $line, $matchesArray)) { # Parse for "case" regex
                if ($matchesArray[1] == 'toc')
                    continue;
                print "<LI><A HREF=\"".$_SERVER['SCRIPT_NAME']."?page=".$matchesArray[1]."\">".$matchesArray[1]."</A>\n"; # Display a Table of Content
            }
        }
        print "</UL>\n";
        break;
        ###################################
    case 'notes' :
        print<<<EndHTML
<!-- /* Editor highlighting trick -->
<PRE>
<B>TODO</B>
  -Support des define de Google Maps dans config.php
  -Faire une fonction d'execution avec gestion de retour d'erreur et d'affichage de l'exection pour chaque "exec"
  -Ajouter une veritable validation (user/password admin provenant de la DB)
     Pour une meilleur securite du script d'installation.
       Au chargement, valider que la connection DB est fonctionnel, que la DB existe et que l'usager admin existe
         Si oui, on demande l'authentification
         Si non, on creer l'usager admin
  -Faire un vrai menu pour acceder directement aux pages desirees (pas une TOC poche)
  -Ameliorer le javascript et arreter de faire des document.myform.submit();
  -Generate valid HTML code
  -Integrate this script with the portal skin
  -Tester que les donnees de AVAIL_LOCALE_ARRAY dans config.php sont valides (fonctionnelles)
     Regarder le code dans include/language.php
  -Support pour l'option CUSTOM_SIGNUP_URL
  -Effacer repertoires/fichiers temporaires des installations

  -Nice2Have : Si test d'integrite et de fonctionnement existent, les integrer pour assurer le bon fonctionnement
  -Nice2Have : Si donnees de tests exitent (pour les developpeurs) Permettre d'en ajouter a la DB
  -Nice2Have : Creer un wifidog.conf (client) selon config + questions si necessaires

<B>Change Log</B>
  15-08-2005 : Bugs correction + comments added
  11-08-2005 : Options rewrite with foreach and dependencies
  09-08-2005 : Admin user creation + network configuration
  27-07-2005 : Added jpgraph install
  26-07-2005 : Added minimal security password validation
  14-07-2005 : saveConfig and all javascript code
  09-07-2005 : Added Phlickr installation
  05-07-2005 : Better PHP extention validation process
  17-06-2005 : MySQL schema and data submission
  17-06-2005 : Postgresql schema and data submission
  24-04-2005 : CSS button

</PRE>
<!-- Editor highlighting trick */ -->
EndHTML;
        break;
        ###################################
        /*  case 'phpinfo': // Use for debugging, to be removed
            print "<PRE>";
            print_r(get_loaded_extensions());
            print "</PRE><BR><BR>";
            phpinfo();
          break;*/

    default :
        $WIFIDOG_VERSION = $configArray['WIFIDOG_VERSION'];
        # TODO : Add links to auth-server web documents
        print<<<EndHTML
<H1>Welcome to WifiDog Auth-Server installation and configuration script.</H1>
<P>This installation still needs improvement, so please any report bug to the mailing list for better support.<BR/>
The current auth-server version is <B>$WIFIDOG_VERSION</B>.</P>

<P><strong>Before going any further</strong> with this installation you need to have/create a valid user and database.
<P>Here is a command line example for PostgreSQL (or use the way you like) :</P>

<B>Create the PostgreSQL databaser user for WifiDog</B> (createuser and createdb need to be in you PATH) :
<PRE>  <I>postgres@yourserver $></I> createuser wifidog --pwprompt
  Enter password for new user:
  Enter it again:
  Shall the new user be allowed to create databases? (y/n) n
  Shall the new user be allowed to create more new users? (y/n) n
  CREATE USER
</PRE>

<B>Create the WifiDog database</B>
<PRE>  <I>postgres@yourserver $></I> createdb wifidog --encoding=UTF-8 --owner=wifidog
  CREATE DATABASE
</PRE>

<B>Security</B> : A password is needed to continue with the installation. You need to read the random password in <B>$password_file</B> file. No username needed, only the password. This password is only usefull for the installation, you will never use it in Auth-Server administration pages.
</PRE>

<P>When you are ready click next</P>

EndHTML;

        navigation(array (array ("title" => "Next", "page" => "version")));
}
?>

</form>
</body>
</html>

