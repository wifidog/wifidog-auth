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
 * @copyright  2005-2006 Pascal Leclerc
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
$password_file = '/tmp/dog_cookie.txt';
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

# Read password file
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

#print "<pre>";      # DEBUG
#print_r($_SERVER);  # DEBUG
#print_r($_REQUEST); # DEBUG
#print "</pre>";     # DEBUG
#exit();

# Minimal version needed
$requiredPHPVersion = '5.0.0';
$requiredPostgeSQLVersion = '0.0.0'; // Todo

# Needed files/directories with write access
$dir_array = array (
    'tmp',
    'tmp/simplepie_cache',
    'lib/smarty',
    'lib/smarty/plugins',
    'tmp/smarty/templates_c',
    'tmp/smarty/cache',
    'lib/simplepie',
    'lib/feedpressreview',
    'config.php'
    );

    $smarty_full_url = 'http://smarty.php.net/do_download.php?download_file=Smarty-2.6.14.tar.gz';

    $neededPackages = array (
    'smarty' => array (
        'needed' => 1,
        'available' => 0,
        'message' => '',
        'file' => 'lib/smarty/Smarty.class.php'
        ),
    'simplepie' => array (
        'needed' => 0,
        'available' => 0,
        'message' => '',
        'file' => 'lib/simplepie/simplepie.inc',
        'svn_source' => 'http://svn.simplepie.org/simplepie/branches/1.0_b3/'
        ),
    'feedpressreview' => array (
        'needed' => 0,
        'available' => 0,
        'message' => '',
        'file' => 'lib/feedpressreview/FeedPressReview.inc',
        'svn_source' => 'http://projects.coeus.ca/svn/feedpressreview/trunk/'
        )
        );

        $loadedExtensions = array_flip(get_loaded_extensions()); # Debug : An empty array for $loadedExtensions will show needed dependencies

        $optionsInfo = array (
        /* TODO:  SSL is now configured in the DB, but should still be handled by the install script
         'SSL_AVAILABLE' => array (
         'title' => 'SSL Support',
         'depend' => 'return 1;',
         'message' => '&nbsp;'
         ),
         */
    'CONF_USE_CRON_FOR_DB_CLEANUP' => array (
        'title' => 'Use cron for DB cleanup',
        'depend' => 'return 1;',
        'message' => '&nbsp;'
        ),
    'XSLT_SUPPORT' => array (
        'title' => 'XSLT Support',
        'depend' => 'return 1;',
        'message' => '&nbsp;'
        ),
    'GMAPS_HOTSPOTS_MAP_ENABLED' => array (
        'title' => 'Google Maps Support',
        'depend' => 'return 1;',
        'message' => '&nbsp;'
        )
        );

        foreach ($neededPackages as $key => $value) { # Detect installed libraries (smarty, ...)
            if (file_exists(WIFIDOG_ABS_FILE_PATH . $neededPackages[$key]['file']))
            $neededPackages[$key]['available'] = 1;
        }

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
            print "\n<p>";
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
            print "</p>\n";
        }

        ###################################
        #
        function refreshButton() {
            print<<<EndHTML

<p><A HREF="#" ONCLICK="javascript: window.location.reload(true);" CLASS="button">Refresh</A></p>
EndHTML;
        }

/** Use PHP internal functions to download a file */
        function downloadFile($remoteURL, $localPath) {
            set_time_limit(1500); // 25 minutes timeout
            if (copy($remoteURL, $localPath))
            return true;
            else
            return false;
        }
/** Use PHP internal functions to execute a command 
 @return: Return value of the command*/
        function execVerbose($command, & $output, & $return_var, $always_show_output = true) {
            print "$command";
            $retval = exec($command.'  2>&1', & $output, & $return_var);
            if ($return_var != 0)
            print "<p style='color:red'><em>Error:</em>  Command did not complete successfully  (returned $return_var): <br/>\n";
            else
            print "<p style='color:green'>Command completed successfully  (returned $return_var): <br/>\n";

            if (($return_var != 0 || $always_show_output) && $output) {
                foreach ($output as $output_line)
                print " $output_line <br/>\n";
            }
            print "</p>\n";
            return $retval;
        }

        ###################################
        #
        /*function debugButton() {
        print <<<EndHTML
        <p><INPUT TYPE="button" VALUE="Debug" ONCLICK="javascript: window.location.reload(true);"></p>
        EndHTML;
        } */

        ###################################
        # In development
        /*function installPackage($pkg_name, $full_url, $copy) {
        print "<h1>$pkg_name installation</h1>\n";
        chdir(WIFIDOG_ABS_FILE_PATH."tmp");
        list($url, $filename) = split ("=", $full_url);

        print "Download source code ($filename) : ";
        if (!file_exists($filename))
        execVerbose("wget \"$url\"", $output, $return);
        if (!file_exists($filename)) // wget success if file exists
        execVerbose("wget \"$full_url\" 2>&1", $output_array, $return);
        if (!file_exists($filename)) {
        print "<B STYLE=\"color:red\">Error</b><p>Current working directory : <em>$basepath/tmp/smarty</em>";
        $output = implode("\n", $output_array);
        print "<pre><em>wget \"$full_url\"</em>\n$output</pre>";
        exit();
        } else {
        print "OK<BR>";
        }

        print "Uncompressing : ";
        $dirname = array_shift(split(".tar.gz", $filename));

        if (!file_exists($dirname))
        execVerbose("tar -xzf $dirname.tar.gz", $output, $return);
        print "OK<BR>";
        print "Copying : ";
        if (!file_exists('../../lib/smarty/Smarty.class.php'));
        execVerbose("cp -r $dirname/libs/* ../../lib/smarty", $output, $return);
        execVerbose("cp -r $dirname/libs/* ../../lib/smarty", $output, $return);
        $copy
        print "OK<BR>";
        }*/

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
                }
                else {
                    fwrite($fd, $line); # Write the line (not a define line). Ex: Commented text
                }
            }
        }

        ###################################
        # MAIN
        switch ($page) {
            case 'permission' :
                print "<h1>Permissions</h1>";

                $process_info_user_id = posix_getpwuid(posix_getuid());
                $process_info_group_id = posix_getgrgid(posix_getegid());
                $process_username = $process_info_user_id['name'];
                $process_group = $process_info_group_id['name'];
                $cmd_mkdir = '';
                $cmd_chown = '';
                $error = 0;

                print "<p><em>Installation directory (WIFIDOG_ABS_FILE_PATH)</em>: " . WIFIDOG_ABS_FILE_PATH . "</p>";
                print "<p><em>Note:</em> Please validate that 'Installation directory' value is the right one. If this value is wrong, the PATH automatic detection will not work as properly";
                print "<p><em>HTTP daemon UNIX username/group</em>: $process_username/$process_group</p>";
                #    print "<p><em>HTTPD group</em>: $process_group<BR</p>";
                print "<p><table BORDER=\"1\"><tr><td><em>Directory</em></td></td><td><em>Owner</em></td><td><em>Writable</em></td></tr>\n";

                foreach ($dir_array as $dir) {
                    print "<tr><td>$dir</td>";
                    if (!file_exists(WIFIDOG_ABS_FILE_PATH . "$dir")) {
                        print "<TD COLSPAN=\"2\" STYLE=\"text-align:center;\">Missing</td></tr>\n";
                        $cmd_mkdir .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                        $cmd_chown .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                        $error = 1;
                        continue;
                    }

                    $dir_info = posix_getpwuid(fileowner(WIFIDOG_ABS_FILE_PATH . "$dir"));
                    $dir_owner_username = $dir_info['name'];
                    print "<td>$dir_owner_username</td>";

                    if (is_writable(WIFIDOG_ABS_FILE_PATH . "$dir")) {
                        print "<td>YES</td>";
                    }
                    else {
                        print "<td>NO</td>";
                        $cmd_chown .= WIFIDOG_ABS_FILE_PATH . "$dir ";
                        $error = 1;
                    }
                    print "</tr>\n";
                }
                print "</table>\n";

                if ($error != 1) {
                    navigation(array (
                    array (
                    "title" => "Next",
                    "page" => "version"
                    )
                    ));
                }
                else {
                    refreshButton();
                    print "<p>You need to allow UNIX user <em>$process_username</em> to write to these directories (mkdir, chown or chmod)</p>";
                    if (!empty ($cmd_mkdir) || !empty ($cmd_mkdir))
                    print "<p><b>For instance, you may want to use the following commands</b> :</p>\n";
                    if (!empty ($cmd_mkdir))
                    print "mkdir $cmd_mkdir <br />";
                    if (!empty ($cmd_chown))
                    print "chgrp -R $process_group $cmd_chown;<br/>chmod g+wx $cmd_chown;<br/>";
                    print "<p>After permissions modification done, hit the REFRESH button to see the NEXT button and continue with the installation";
                }
                break;
            ###################################
            case 'version' :
                print "<h1>Checking dependencies</h1>";
                print "<table BORDER=\"1\">";

                /* PHP version check */
                $phpVersion = phpversion();
                $okMsg = '<TD ALIGN="CENTER" STYLE="background:lime;">OK</td>';
                $errorMsg = '<TD ALIGN="CENTER" STYLE="background:red;">ERROR</td>';
                $warningMsg = '<TD ALIGN="CENTER" STYLE="background:yellow;">Warning</td>';
                $error = 0;

                print "<tr><td>PHP</td>";
                if (version_compare($phpVersion, $requiredPHPVersion, ">=")) {
                    print "$okMsg<td>$phpVersion</td>"; // Version 5.0.0 or later
                }
                else {
                    print "$errorMsg<td>Version $requiredPHPVersion needed</td>"; // Version < 5.0.0
                    $error = 1;
                }
                print "</tr></table><BR>";
                
                require_once ('classes/Dependencies.php');
                $components = Dependencies::getDependencies();
                 print "<table BORDER=\"1\">\n";
                print "<tr><th>Component</th><th>Type</th><th>Status</th><th>Description</th><th>Message</th></tr>";
                 
                foreach ($components as $dependency) {
                    echo "<tr>\n";
                    $websiteUrl = $dependency->getWebsiteURL();
                    $component_key = $dependency->getId();
                    $description = $dependency->getDescription();
                    $mandatory = $dependency->isMandatory();
                    $type = $dependency->getType();
                    if($websiteUrl){
                        echo "<td><A HREF=\"$websiteUrl\">$component_key</A></td>\n";
                    }
                    else{
                   echo "<td>$component_key</td>\n";
                    }
                     echo "<td>$type</td>\n";
                    $available = Dependencies::check($component_key, $message);
                    if ($available) {
                        print "$okMsg<td>$description</td><td>&nbsp;</td></tr>";
                    }
                    else {
                        if ($mandatory) {
                            print "$errorMsg<td>$description</td><td>$message</td></tr>";
                            $error = 1;
                        }
                        else {
                            print "$warningMsg<td>$description</td><td>$message</td></tr>";
                        }
                    }
                }
                print "</table><BR>";
                               
        //TODO: PostgreSQL and MySQL version are not validated";
        print "We recommend you use PostgreSQL 8.0 or newer (but it isn't required)";

        refreshButton();
        if ($error != 1) {
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "permission"
                ),
                array (
                    "title" => "Next",
                    "page" => "smarty"
                )
            ));
        }

        break;
        ###################################
    case 'smarty' : // Download, uncompress and install Smarty
        print<<< EndHTML
    <h1>Smarty template engine installation</h1>
    <p><A HREF="http://smarty.php.net/">Smarty</A> is Template Engine. WifiDog requires you install it before you continue.</p>
EndHTML;
        if ($neededPackages['smarty']['available']) {
            print "Already installed !<br/>";
        }
        else {
            chdir(WIFIDOG_ABS_FILE_PATH . "tmp");
            list ($url, $filename) = split("=", $smarty_full_url);

            print "Download source code ($filename) : ";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH."tmp/" . $filename))
                //execVerbose("wget \"$smarty_full_url\" 2>&1", $output, $return);
                downloadFile($smarty_full_url, WIFIDOG_ABS_FILE_PATH."tmp/" . $filename);

            if (!file_exists(WIFIDOG_ABS_FILE_PATH."tmp/" . $filename)) {
                print "<B STYLE=\"color:red\">Error</b><p>Current working directory : <em>$basepath/tmp/smarty</em>";
                $output = implode("\n", $output);
                print "<pre><em>wget \"$smarty_full_url\"</em>\n$output</pre>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dir_array = split(".tar.gz", WIFIDOG_ABS_FILE_PATH."tmp/" . $filename);
            $dirname = array_shift($dir_array);

            if (!file_exists($dirname))
                execVerbose("tar -xzf $dirname.tar.gz", $output, $return);
            print "OK<BR>";
            print "Copying : ";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH . "lib/smarty"));
            execVerbose("cp -r $dirname/libs/* " . WIFIDOG_ABS_FILE_PATH . "/lib/smarty", $output, $return); # TODO : Utiliser SMARTY_REL_PATH
            print "OK<BR>";

            refreshButton();
        }
        navigation(array (
            array (
                "title" => "Back",
                "page" => "version"
            ),
            array (
                "title" => "Next",
                "page" => "simplepie"
            )
        ));
        break;
        ###################################
    case 'simplepie' : // Download, uncompress and install SimplePie
        print "<h1>SimplePie installation</h1>\n";

        if ($neededPackages['simplepie']['available']) {
            print "Already installed !<BR>";
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "smarty"
                ),
                array (
                    "title" => "Next",
                    "page" => "feedpressreview"
                )
            ));
        }
        elseif ($action == 'install') {

            print "Download source code frpm svn($filename) : ";
            execVerbose("svn co ".escapeshellarg($neededPackages['simplepie']['svn_source'])." ".escapeshellarg(WIFIDOG_ABS_FILE_PATH."lib/simplepie"), $output, $return);
            #execVerbose("locale", $output, $return);

            refreshButton();
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "smarty"
                ),
                array (
                    "title" => "Next",
                    "page" => "feedpressreview"
                )
            ));
        }
        else {
            print<<< EndHTML
<p><A HREF="http://simplepie.org/">SimplePie</A> is a dependency of provides an RSS parser in PHP. It is required for RssPressReview.  It's is recommended to install it, if you don't, RSS feeds options will be disabled.

<p>Do you want to install SimplePie ?
EndHTML;
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "smarty"
                ),
                array (
                    "title" => "Install",
                    "page" => "simplepie",
                    "action" => "install"
                ),
                array (
                    "title" => "Next",
                    "page" => "feedpressreview"
                )
            ));
        }
        break;
        ###################################
    case 'feedpressreview' : // Download, uncompress and install feedpressreview
        print "<h1>Feed press review installation</h1>\n";

        if ($neededPackages['feedpressreview']['available']) {
            print "Already installed !<BR>";
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "simplepie"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        elseif ($action == 'install') {

            print "Download source code frpm svn($filename) : ";
            execVerbose("svn co ".escapeshellarg($neededPackages['feedpressreview']['svn_source'])." ".escapeshellarg(WIFIDOG_ABS_FILE_PATH."lib/feedpressreview"), $output, $return);
            #execVerbose("locale", $output, $return);

            refreshButton();
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "smarty"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        else {
            print<<< EndHTML
<p><A HREF="http://projects.coeus.ca/feedpressreview/">Feed press review</A> is a dependency that provides a Feed aggregator in PHP.  It is recommended to install it.  If you don't, RSS feeds options will be disabled.

<p>Do you want to install FeedPressReview ?
EndHTML;
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "simplepie"
                ),
                array (
                    "title" => "Install",
                    "page" => "feedpressreview",
                    "action" => "install"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        break;
        ###################################
    case 'jpgraph' : // Download, uncompress and install JpGraph library
        print "<h1>JpGraph installation</h1>\n";

        if ($neededPackages['jpgraph']['available']) {
            print "Already installed !<BR>";
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "feedpressreview"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        elseif ($action == 'install') {
            chdir(WIFIDOG_ABS_FILE_PATH . "tmp");
            $filename = array_pop(preg_split("/\//", $jpgraph_full_url));

            print "Download source code ($filename) : ";
            if (!file_exists($filename))
                execVerbose("wget \"$jpgraph_full_url\" 2>&1", $output, $return);
            if (!file_exists($filename)) { # Error occured, print output of wget
                print "<B STYLE=\"color:red\">Error</b><p>Current working directory : <em>$basepath/tmp</em>";
                $output = implode("\n", $output);
                print "<pre><em>wget \"$jpgraph_full_url\"</em>\n$output</pre>";
                exit ();
            }
            else {
                print "OK<BR>";
            }

            print "Uncompressing : ";
            $dirname = array_shift(split(".tar.gz", $filename));
            if (!file_exists($dirname))
                execVerbose("tar -xzf $dirname.tar.gz", $output, $return);
            print "OK<BR>";

            print "Copying : ";
            if (!file_exists(WIFIDOG_ABS_FILE_PATH."lib/jpgraph/jpgraph.php"))
                execVerbose("cp $dirname/src/* ".WIFIDOG_ABS_FILE_PATH."lib/jpgraph", $output, $return); # TODO : Utiliser JPGRAPH_REL_PATH

            print "OK<BR>";

            refreshButton();
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "feedpressreview"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        else {
            print<<< EndHTML
<p><A HREF="http://www.aditus.nu/jpgraph/">JpGraph</A> is a Object-Oriented Graph creating library for PHP.
JpGraph is not currently use by Wifidog (will be use for statistique graph in a later version). You can skip this installation if your not a developper.

<p>Do you want to install JpGraph ?
EndHTML;
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "feedpressreview"
                ),
                array (
                    "title" => "Install",
                    "page" => "jpgraph",
                    "action" => "install"
                ),
                array (
                    "title" => "Next",
                    "page" => "database"
                )
            ));
        }
        break;
        ###################################
    case 'database' :
        ### TODO : Valider en javascript que les champs soumit ne sont pas vide
        #          Pouvoir choisir le port de la DB ???
        print<<< EndHTML
<h1>Database access configuration</h1>
<BR>
<table border="1">
  <tr><td>Host</td><td><INPUT type="text" name="CONF_DATABASE_HOST" value="$CONF_DATABASE_HOST"></td></tr>
  <tr><td>DB Name</td><td><INPUT type="text" name="CONF_DATABASE_NAME" value="$CONF_DATABASE_NAME"></td></tr>
  <tr><td>Username</td><td><INPUT type="text" name="CONF_DATABASE_USER" value="$CONF_DATABASE_USER"></td></tr>
  <tr><td>Password</td><td><INPUT type="text" name="CONF_DATABASE_PASSWORD" value="$CONF_DATABASE_PASSWORD"></td></tr>
</table>

<p>By clicking Next, your configuration will be automaticaly saved

<script type="text/javascript">
  function submitDatabaseValue() {
    newConfig("CONF_DATABASE_HOST='" + document.myform.CONF_DATABASE_HOST.value + "'");
    newConfig("CONF_DATABASE_NAME='" + document.myform.CONF_DATABASE_NAME.value + "'");
    newConfig("CONF_DATABASE_USER='" + document.myform.CONF_DATABASE_USER.value + "'");
    newConfig("CONF_DATABASE_PASSWORD='" + document.myform.CONF_DATABASE_PASSWORD.value + "'");
  }
</script>

EndHTML;

        navigation(array (
            array (
                "title" => "Back",
                "page" => "simplepie"
            )
        )); #, array("title" => "Next", "page" => "testdatabase")));
        print<<< EndHTML
<p><A HREF="#" ONCLICK="javascript: document.myform.page.value='testdatabase'; submitDatabaseValue(); document.myform.submit();" CLASS="button">Next</A></p>

EndHTML;

        break;
        ###################################
    case 'testdatabase' :
        print "<h1>Database connection</h1>";
        /* TODO : Tester la version minimale requise de Postgresql                */

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
                navigation(array (
                    array (
                        "title" => "Back",
                        "page" => "database"
                    ),
                    array (
                        "title" => "Next",
                        "page" => "dbinit"
                    )
                ));
        break;
        ###################################
    case 'dbinit' :
        print "<h1>Database initialisation</h1>";

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

        $content_schema_array = file(WIFIDOG_ABS_FILE_PATH . "../sql/wifidog-postgres-schema.sql") or die("<em>Error</em>: Can not open $basepath/../sql/wifidog-postgres-schema.sql"); # Read SQL schema file
        $content_schema = implode("", $content_schema_array);
        $content_data_array = file(WIFIDOG_ABS_FILE_PATH . "../sql/wifidog-postgres-initial-data.sql"); # Read SQL initial data file
        $content_data = implode("", $content_data_array);

        $db_schema_version = ''; # Schema version query from database
        $file_schema_version = ''; # Schema version from define(REQUIRED_SCHEMA_VERSION) in schema_validate.php

                $conn_string = "host=$CONF_DATABASE_HOST dbname=$CONF_DATABASE_NAME user=$CONF_DATABASE_USER password=$CONF_DATABASE_PASSWORD";
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
                $sql = "SELECT * FROM schema_info WHERE tag='schema_version'";
                if ($result = @ pg_query($connection, $sql)) { # The @ remove warning display
                    $result_array = pg_fetch_all($result);
                    $db_shema_version = $result_array[0]['value'];

                    print "<p>On <em>$CONF_DATABASE_HOST</em>, Database <em>$CONF_DATABASE_NAME</em> exists and is ";
                    if ($db_shema_version == $file_schema_version) {
                        print "up to date (shema version <em>$db_shema_version</em>).";
                        navigation(array (
                            array (
                                "title" => "Back",
                                "page" => "database"
                            ),
                            array (
                                "title" => "Next",
                                "page" => "options"
                            )
                        ));
                    }
                    elseif ($db_shema_version < $file_schema_version) {
                        print "at schema version <em>$db_shema_version</em>. The required schema version is <em>$file_schema_version</em><p>Please upgrade the database";
                        navigation(array (
                            array (
                                "title" => "Back",
                                "page" => "database"
                            ),
                            array (
                                "title" => "Upgrade",
                                "page" => "schema_validate"
                            ),
                            array (
                                "title" => "Next",
                                "page" => "options"
                            )
                        ));
                    }
                    else {
                        print "Error : Unexpected result";
                    }
                    exit ();
                }

                print "<UL><LI>Creating wifidog database schema : ";
                $content_schema = preg_replace($patterns, $replacements, $content_schema); # Comment bad SQL lines

                $result = pg_query($connection, $content_schema) or die("<em>" . pg_last_error() . "</em> <=<BR>");
                print "OK";

                print "<LI>Creating wifidog database initial data : ";
                $content_data = preg_replace($patterns, $replacements, $content_data); # Comment bad SQL lines

                $result = pg_query($connection, $content_data) or die("<em>" . pg_last_error() . "</em> <=<BR>");
                print "OK</UL>";

                navigation(array (
                    array (
                        "title" => "Back",
                        "page" => "database"
                    ),
                    array (
                        "title" => "Next",
                        "page" => "options"
                    )
                ));
        break;

        ###################################
    case 'schema_validate' :
        print "<h1>Database schema upgrade</h1>\n";

        require_once (dirname(__FILE__) . '/include/common.php');

        require_once ('classes/AbstractDb.php');
        require_once ('classes/Session.php');
        require_once ('include/schema_validate.php');

        validate_schema();

        navigation(array (
            array (
                "title" => "Back",
                "page" => "dbinit"
            ),
            array (
                "title" => "Next",
                "page" => "options"
            )
        ));

        //navigation(array(array("title" => "Back", "page" => "dbinit")));
        break;

        ###################################
    case 'options' :
        # TODO : Tester que la connection SSL est fonctionnelle
        #        Options avancees : Supporter les define de [SMARTY|PHLICKR|JPGRAPH]_REL_PATH
        print<<< EndHTML
<h1>Available options</h1>
  <table border="1">

EndHTML;

        //echo '<pre>';print_r($optionsInfo);echo '</pre>';
        foreach ($optionsInfo as $name => $foo) { # Foreach generate all <table> fields
            $value = $configArray[$name]; # Value of option in config.php
            $title = $optionsInfo[$name]['title']; # Field Title
            $message = $optionsInfo[$name]['message']; # Message why option is disable
            if(empty($value))
                $message .= ", ERROR: unable to find the '$name' directive in the config file";
            $depend = @ eval ($optionsInfo[$name]['depend']); # Evaluate the dependencie
            $selectedTrue = '';
            $selectedFalse = ''; # Initialize value
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

        print<<< EndHTML
<p><A HREF="#" ONCLICK="javascript: document.myform.page.value='languages'; submitOptionsValue(); document.myform.submit();" CLASS="button">Next</A></p>
EndHTML;

        break;

        ###################################
    case 'languages' :
        print "<h1>Languages configuration</h1>";
        print<<< EndHTML
      <p>Not yet implemented ...</p>
      <p>Will allow selecting language to use.</p>
<em>Error message example</em> : <BR>
<DIV style="border:solid black;">Warning: language.php: Unable to setlocale() to fr, return value: , current locale: LC_CTYPE=en_US.UTF-8;LC_NUMERIC=C; [...]</DIV>
<p><em>I repeat</em> : This is an example of message you can see in the top of your working auth-server if language are not set correctly. To change these values please edit <em>config.php</em> in auth-server install directory. Look for "Available locales" and "Default language" header in config.php.
EndHTML;
        //    execVerbose("locale -a 2>&1", $output, $return);

        navigation(array (
            array (
                "title" => "Back",
                "page" => "options"
            ),
            array (
                "title" => "Next",
                "page" => "radius"
            )
        ));
        break;

        ###################################
    case 'radius' :
        print "<h1>Radius Authenticator configuration</h1>";
        print "<p>Not yet implemented ...";

        navigation(array (
            array (
                "title" => "Back",
                "page" => "languages"
            ),
            array (
                "title" => "Next",
                "page" => "admin"
            )
        ));
        break;

        ###################################
    case 'admin' :
        print "<h1>Administration account</h1>";
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
            require_once (dirname(__FILE__) . '/include/common.php');
            require_once (dirname(__FILE__) . '/classes/User.php');

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
            print "<p>Your administrator user account is <em>$username_db</em>";
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "radius"
                ),
                array (
                    "title" => "Next",
                    "page" => "network"
                )
            ));
        }
        else {
            print<<<EndHTML
        <p>
        <table BORDER="1">
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
            navigation(array (
                array (
                    "title" => "Back",
                    "page" => "radius"
                )
            ));
            print "<p><A HREF=\"#\" ONCLICK=\"javascript: submitValue();\" CLASS=\"button\">Next</A></p>\n";
        }
        break;

        ###################################
    case 'network' :
        print "<h1>Network</h1>";

        //$HOTSPOT_NETWORK_NAME          = $configArray['HOTSPOT_NETWORK_NAME'];
        //$HOTSPOT_NETWORK_URL           = $configArray['HOTSPOT_NETWORK_URL'];
        //$TECH_SUPPORT_EMAIL            = $configArray['TECH_SUPPORT_EMAIL'];
        //$VALIDATION_GRACE_TIME         = $configArray['VALIDATION_GRACE_TIME'];
        //$VALIDATION_EMAIL_FROM_ADDRESS = $configArray['VALIDATION_EMAIL_FROM_ADDRESS'];

        /**
         * @deprecated 2005-12-26 Needs to use network abstraction
         *
         *
         * <p>
        <table border="1">
        <tr>
        <td>Network Name</td><td><INPUT type="text" name="HOTSPOT_NETWORK_NAME" value="" size="30"></td>
        </tr>
        <tr>
        <td>Network URL</td><td><INPUT type="text" name="HOTSPOT_NETWORK_URL" value="" size="30"></td>
        </tr>
        <tr>
        <td>Tech Support Email</td><td><INPUT type="text" name="TECH_SUPPORT_EMAIL" value="" size="30"></td>
        </tr>
        <tr>
        <td>Validation Grace Time (min)</td><td><INPUT type="text" name="VALIDATION_GRACE_TIME" value="" size="30"></td>
        </tr>
        <tr>
        <td>Validation Email (send from)</td><td><INPUT type="text" name="VALIDATION_EMAIL_FROM_ADDRESS" value="" size="30"></td>
        </tr>
        </table>
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

        navigation(array (
            array (
                "title" => "Back",
                "page" => "admin"
            )
        ));

        print<<< EndHTML
<p><A HREF="#" ONCLICK="javascript: document.myform.page.value='hotspot'; submitOptionsValue(); document.myform.submit();" CLASS="button">Next</A></p>
EndHTML;
        #navigation(array(array("title" => "Back", "page" => "admin"), array("title" => "Next", "page" => "hotspot")));
        break;

        ###################################
    case 'hotspot' :
        print "<h1>Hotspot</h1>";
        print "<p>A default hotspot has already been created<p>Use administration interface to add more hotspots.";
        navigation(array (
            array (
                "title" => "Back",
                "page" => "network"
            ),
            array (
                "title" => "Next",
                "page" => "end"
            )
        ));
        break;

        ###################################
    case 'delete' :
        print<<<EndHTML
  <h1>Delete temporary files</h1>
  ...
EndHTML;
        #navigation(array(array("title" => "Back", "page" => "hotspot")));
        break;

        ###################################
    case 'end' :
        $url = 'http://' . $_SERVER['HTTP_HOST'] . SYSTEM_PATH;
        print<<<EndHTML
  <h1>Thanks for using Wifidog</h1>
  Redirection to your new WifiDog Authentification Server in 3 seconds
  <meta http-equiv="REFRESH" content="3;url=$url">
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
        #navigation(array(array("title" => "Back", "page" => "hotspot")));
        break;

        ###################################
    case 'toc' :
        print "<h1>Table of content</h1>";
        $contentArray = file(__file__); # Read myself
        print "<UL>\n";
        foreach ($contentArray as $line) {
            if (preg_match("/^  case '(\w+)':/", $line, $matchesArray)) { # Parse for "case" regex
                if ($matchesArray[1] == 'toc')
                    continue;
                print "<LI><A HREF=\"" . $_SERVER['SCRIPT_NAME'] . "?page=" . $matchesArray[1] . "\">" . $matchesArray[1] . "</A>\n"; # Display a Table of Content
            }
        }
        print "</UL>\n";
        break;
        ###################################
    case 'notes' :
        print<<<EndHTML
<!-- /* Editor highlighting trick -->
<pre>
<em>TODO</em>
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

<em>Change Log</em>
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

</pre>
<!-- Editor highlighting trick */ -->
EndHTML;
        break;
        ###################################
        /*  case 'phpinfo': // Use for debugging, to be removed
            print "<pre>";
            print_r(get_loaded_extensions());
            print "</pre><BR><BR>";
            phpinfo();
          break;*/

    default :
        $WIFIDOG_VERSION = $configArray['WIFIDOG_VERSION'];
        # TODO : Add links to auth-server web documents
        print<<<EndHTML
<h1>Welcome to WifiDog Auth-Server installation and configuration script.</h1>
<p>This installation still needs improvement, so please any report bug to the mailing list for better support.<BR/>
The current auth-server version is <em>$WIFIDOG_VERSION</em>.</p>

<p><strong>Before going any further</strong> with this installation you need to have/create a valid user and database.
<p>Here is a command line example for PostgreSQL (or use the way you like) :</p>

<em>Create the PostgreSQL databaser user for WifiDog</em> (createuser and createdb need to be in you PATH) :
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

<em>Security</em> : A password is needed to continue with the installation. You need to read the random password in <em>$password_file</em> file. No username needed, only the password. This password is only usefull for the installation, you will never use it in Auth-Server administration pages.
</pre>

<p>When you are ready click next</p>

EndHTML;

        navigation(array (
            array (
                "title" => "Next",
                "page" => "permission"
            )
        ));
}
?>

</form>
</body>
</html>

