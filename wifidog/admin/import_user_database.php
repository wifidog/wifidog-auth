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
 * @package    WiFiDogAuthServer
 * @copyright  2004 Technologies Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once('admin_common.php');

require_once('classes/MainUI.php');

$security = new Security();
$security->requireAdmin();

$html = '';

/** Affiche les informations sur le fichier envoy� par le client
 */
function PrintUploadedFileInfo($form_name_file)
{
  $html .=  "Nom du fichier envoy�:".$_FILES[$form_name_file]['name']."<br>";
  $html .=  "Taille: ".$_FILES[$form_name_file]['size']." octets"."<br>";
  $html .=  "Mime type: ".$_FILES[$form_name_file]['type']."<br>";
  $html .=  "Nom du fichier temporaire sur le serveur: ".$_FILES[$form_name_file]['tmp_name']."<br>";
  $html .=  "Erreurs au cours du transfert: ".$_FILES[$form_name_file]['error']."<br>";
}

$html .=  "<fieldset class=\"pretty_fieldset\">";
$html .=  "<legend>"._("NoCat user database import")."</legend>";

if(empty($_REQUEST['action']))
  {
    $html .=  "<form name=upload_file enctype='multipart/form-data' action='' method='post'>\n";

    $html .=  "<p>"._('Please select the NoCat passwd file you want to import.')."</p>\n";
    $html .=  "<input name='userfile' type='file' />\n";
    $html .=  "<input type='hidden' name='action' value='upload_file' />\n";
    $html .=  "<input type='hidden' name='MAX_FILE_SIZE' value='300000' />\n";
    $html .=  "<p>"._("Accept users with no email adresses (Normally, NoCat usernames are expected to be the user's email adress, and the username is generated from the prefix.")."\n";
    $html .=  "<input type='checkbox' name='accept_empty_email' value='true' /></p>\n";
    $html .=  "<p><input name='upload' type='submit' value='"._("Upload file")."' />\n";

    $html .=  "<input type='checkbox' name='import_confirm' value='true' />\n";
    $html .=  _("I am sure I want to import (Otherwise, the import will only be simulated).")."</p>\n";
    $html .=  "</form>\n";
  }
else if ($_REQUEST['action'] == 'upload_file')
  {
    if($_FILES['userfile']['tmp_name'])
      {

    $import_user = Array();
    /* $import_user[$username]['email']
     $import_user[$username]['passwd_hash']
     $import_user[$username]['original_username']
     $import_user[$username]['username_modified_because_of']
     $import_user[$username]['is_rejected']
     $import_user[$username]['reject_reason']
    */

    PrintUploadedFileInfo('userfile');

    $fp = fopen($_FILES['userfile']['tmp_name'], "rb");
    $output = null;

    $row = 1;
    while (!feof($fp))
      {
      $data = fgets ($fp);
      $num = count($data);
      $html .=  "<hr><p>Line $row: $data<br />\n";

      if(preg_match("/^(.*):(.*)$/", $data, $matches))
        {
          //$html .=  "<p><pre>". print_r($matches)."</pre></p>\n";
          $nocat_username = $matches[1];
          $nocat_password_hash=$matches[2];
          $matches = null;
          if(preg_match( "/^(.*)@.*$/", $nocat_username, $matches))
        {
          $email = $nocat_username;
          $original_username = $matches[1];
        }
          else
        {
          $html .=  "<p class=info>NoCat username isn't an email</p>";
          $email = '';
          $original_username = $nocat_username;
        }

          $html .=  "<p class=info>Generating temporary user from:  $original_username; Checking internal duplicates (duplicate usernames in the imported file)</p>\n";
          $username_modified_because_of=null;
          $username=$original_username;
          if(isset($import_user[$username]))
        {
          $index=1;
          while(isset($import_user[$username]))
            {
              $username_modified_because_of=$username;
              $html .=  "<p class=warning>Can't use $username because it was already generated from the imported file</p>\n";
              $username=$original_username."_$index";
              $index++;
            }
          $html .=  "<p class=info>Final username is now $username</p>\n";
        }
          else
        {
          $html .=  "<p class=info>Final username is still $username</p>\n";
        }

          $import_user[$username]['email']=$email;
          $import_user[$username]['passwd_hash']=convert_nocat_password_hash($nocat_password_hash);
          $import_user[$username]['original_username']=$original_username;
          $import_user[$username]['username_modified_because_of']=$username_modified_because_of;
          $import_user[$username]['is_rejected']=null;
          $import_user[$username]['reject_reason']=null;
        }
      else
        {
          $html .=  "<p class=info>Line skipped</p>\n";
        }
      $row++;
      }
    $html .=  "<hr><p>Total of ". ($row-1) ." lines read and ".count($import_user)." candidate users generated.<br />\n";
    foreach($import_user as $username => $user)
    {
      //$html .=  "<p>$username</pre></p>\n";
      //$html .=  "<p><pre>". print_r($user)."</pre></p>\n";
      $import_user[$username]['is_rejected']=false;

      if(!empty($user['email']))
        {
          $email_str = $db->EscapeString($user['email']);
          $db->ExecSqlUniqueRes("SELECT email FROM users WHERE email='$email_str'", $user_info_email, false);
          if($user_info_email!=null)
        {
          $import_user[$username]['is_rejected']=true;
          $import_user[$username]['reject_reason'] .= "<p class=error>"._('Sorry, a user account is already associated to the email address: ')."$user[email]</p>\n";
        }
        }
      else if(empty($_REQUEST['accept_empty_email']))
        {
          $import_user[$username]['is_rejected']=true;
          $import_user[$username]['reject_reason'] .= "<p class=error>"._('Sorry, the user must have a email adress.')."</p>\n";null;
        }
      else
        {
          $username_str = $db->EscapeString($username);
          $db->ExecSqlUniqueRes("SELECT username FROM users WHERE username='$username_str'", $user_info_username, false);
          if($user_info_username!=null)
        {
          $import_user[$username]['is_rejected']=true;
          $import_user[$username]['reject_reason'] .= "<p class=error>"._('Sorry, a user account already exists with the username: ')."$username</p>\n";
        }
        }

      if(!empty($_REQUEST['import_confirm']) && $_REQUEST['import_confirm']=='true' && $import_user[$username]['is_rejected']==false)
        {
          $status = ACCOUNT_STATUS_ALLOWED;
          $token = User::generateToken();
          $reg_date = iso8601_date(time());
          $password_hash = $db->EscapeString($user['passwd_hash']);
          $username =  $db->EscapeString($username);
          $email =  $db->EscapeString($user['email']);
          $sql = "INSERT INTO users (user_id, username,email,pass,account_status,validation_token,reg_date) VALUES ('".get_guid()."','$username','$email','$password_hash','{$status}','{$token}','{$reg_date}')";
          $update_successful = $db->ExecSqlUpdate($sql);
          if ($update_successful)
        {
          //send_validation_email($email);
          $showform=false;
        }
          else
        {
          $import_user[$username]['is_rejected']=true;
          $import_user[$username]['reject_reason'] .= "<p class=error>"._('SQL error on: ')."$sql</p>\n";
        }
        }
    }


    $html .=  "<h2>"._('Report')."</h2>\n";
    /* List rejected users */
    $html .=  "<table>\n";
    $count_reject=0;
    $count_success=0;
    foreach($import_user as $username => $user)
    {
      if($user['is_rejected']==true)
        {
          $count_reject++;
          $html .=  "<tr>\n";
          $html .=  "<td>$username</td><td>$user[reject_reason]</td>\n";
          $html .=  "</tr>\n";
        }
      else
        {
          $count_success++;
        }
    }
    $html .=  "<thead><tr><th colspan=2>$count_reject rejected users</th></tr>\n";
    $html .=  "<tr><th>Username</th><th>Reason for rejection</th></tr></thead>\n";
    $html .=  "</table>\n";

    /* List users imported with mangled usernames */
    $html .=  "<table>\n";
 $count_mangled=0;
    foreach($import_user as $username => $user)
    {
      if($user['is_rejected']==false&&!empty($user['username_modified_because_of']))
        {
          $count_mangled++;
          $html .=  "<tr>\n";
          $html .=  "<td>$username</td><td>$user[original_username]</td><td>$user[username_modified_because_of]</td>\n";
          $html .=  "</tr>\n";
        }
    }
    $html .=  "<thead><tr><th colspan=3>$count_mangled users were imported with modified usernames</th></tr>\n";
    $html .=  "<tr><th>Username</th><th>Original username</th><th>Changed because of user</th></tr></thead\n";
    $html .=  "</table>\n";

    $html .=  "<h2>$count_success user(s) successfully imported ($count_mangled of them had their username modified), $count_reject user(s)rejected</h2>\n";
      }
  }
        $html .=  "</div>\n";
        $html .=  "</fieldset>\n";

    $ui=new MainUI();
    $ui->setToolSection('ADMIN');
    $ui->setMainContent($html);
    $ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
