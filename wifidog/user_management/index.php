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
   * @author Copyright (C) 2004 Benoit Grégoire, Philippe April.
   */
define('BASEPATH','../');
require_once (BASEPATH.'/include/common.php');
require_once (BASEPATH.'classes/Style.php');
require_once (BASEPATH.'include/user_management_menu.php');

function display_register_form()
{
  if(!empty($_REQUEST['username']))
    {
      $username = $_REQUEST['username'];
    }
  else
    {
      $username = '';
    }

  if(!empty($_REQUEST['validate']))
    {
      $validate = $_REQUEST['validate'];
    }
  else
    {
      $validate = '';
    }
  if(!empty($_REQUEST['pass']))
    {
      $pass = $_REQUEST['pass'];
    }
  else
    {
      $pass = '';
    }
  if(!empty($_REQUEST['pass_again']))
    {    $pass_again = $_REQUEST['pass_again'];
    }
  else
    {
      $pass_again = '';
    }
  if(!empty($_REQUEST['email']))
    {
      $email = $_REQUEST['email'];
    }
  else
    {
      $email = '';
    }
  echo "<h1>"._('Register a free account with')." ".HOTSPOT_NETWORK_NAME."</h1>\n";
  echo "<form method='post'>\n";
  echo "Your desired username: <input type='text' name='username' value='$username'><br>\n";
  echo "Your email address: <input type='text' name='email' value='$email'><br>\n";
  echo "Your password: <input type='password' name='pass' value='$pass'><br>\n";
  echo "Your password(again): <input type='password' name='pass_again' value='$pass_again'><br>\n";
  echo "<input type='hidden' name='action' value='create_new_account'><br>\n";
  echo "<input type='submit'>\n";
  echo "</form>\n";
}

function display_validation_email_form()
{
  if(!empty($_REQUEST['username']))
    {
      $username = $_REQUEST['username'];
    }
  else
    {
      $username = '';
    }
  echo "<h1>"._('Re-send validation email')."</h1>\n";
  echo "<form method='post'>\n";
  echo "Your username: <input type='text' name='username' value='$username'><br>\n";
  echo "<input type='hidden' name='action' value='send_validation_email'><br>\n";
  echo "<input type='submit'>\n";
  echo "</form>\n";
}


/** Send the email offering the link to validate a new account
 */
function send_validation_email($email)
{
  global $db;
  $user_info=null;
  $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
  if($user_info==null)
    {
      echo "<p class=error>send_validation_email(): Error: Unable to locate $email in the database</p>\n";
    }
  else
    {
      if($user_info['account_status']!=ACCOUNT_STATUS_VALIDATION)
	{
	  /* Note:  Do not display the username here, for privacy reasons */
	  echo "<p class=error>send_validation_email(): Error: The user account_status is $user_info[account_status] instead of ".ACCOUNT_STATUS_VALIDATION." (ACCOUNT_STATUS_VALIDATION)</p>";
	}
      else
	{
	  if(empty($user_info['validation_token']))
	    {
	      echo "<p class=error>send_validation_email(): Error: The validation_token is empty</p>\n";
	    }
	  else
	    {
	      $subject = VALIDATION_EMAIL_SUBJECT;
              $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?action=validate&username=" . $_REQUEST["username"] . "&validation_token=" . $user_info["validation_token"];
	      $body = "Hello

 Please follow the link below to validate your account.

 $url

 Thank you,

 The Team";
	      $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

	      mail($email, $subject, $body, $from);
	         echo "<p>"._('An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retreive your email and validate your account.  You may now open a browser window and go to any remote internet address to obtain the login page.')."</p>\n";
	    }
	}
    }
}


function display_change_password_form()
{
  if(!empty($_REQUEST['username']))
    {
      $username = $_REQUEST['username'];
    }
  else
    {
      $username = '';
    }

  if(!empty($_REQUEST['pass']))
    {
      $pass = $_REQUEST['pass'];
    }
  else
    {
      $pass = '';
    }
  if(!empty($_REQUEST['new_pass']))
    {
      $new_pass = $_REQUEST['new_pass'];
    }
  else
    {
      $new_pass = '';
    }
  if(!empty($_REQUEST['new_pass_again']))
    {    $new_pass_again = $_REQUEST['new_pass_again'];
    }
  else
    {
      $new_pass_again = '';
    }
  echo "<h1>"._('Change password')."</h1>\n";
  echo "<form method='post'>\n";
  echo "Your username: <input type='text' name='username' value='$username'><br>\n";
  echo "Your old password: <input type='password' name='pass' value='$pass'><br>\n";
  echo "Your new password: <input type='password' name='new_pass' value='$new_pass'><br>\n";
  echo "Your password(again): <input type='password' name='new_pass_again' value='$new_pass_again'><br>\n";
  echo "<input type='hidden' name='action' value='change_password'><br>\n";
  echo "<input type='submit'>\n";
  echo "</form>\n";
}

function display_lost_username_form()
{
  if(!empty($_REQUEST['email']))
    {
      $email = $_REQUEST['email'];
    }
  else
    {
      $email = '';
    }
  echo "<h1>"._('Lost username')."</h1>\n";
  echo "<form method='post'>\n";
  echo "<p>"._('Please enter your email address:')." <input type='text' name='email' value='$email'></p>\n";
  echo "<input type='hidden' name='action' value='mail_lost_username'>\n";
  echo "<p><input type='submit'></p>\n";
  echo "</form>\n";
}
/** Send the email offering the link to validate a new account
 */
function send_lost_username_email($email)
{
  global $db;
  $db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE email='$email'", $user_info, false);
  if($user_info==null)
    {
      echo "<p class=error>send_lost_username_email(): Error: Unable to locate $email in the database</p>\n";
    }
  else
    {
      $subject = LOST_USERNAME_EMAIL_SUBJECT;
      $body = "Hello,

 You have requested that the authentication server send you your username:

 Username: $user_info[user_id]

 Have a nice day,

 The Team";
      $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

      mail($email, $subject, $body, $from);
      echo "<p>"._('Your username has been mailed to you.')."</p>\n";
    }
}



function display_lost_password_form()
{
  if(!empty($_REQUEST['username']))
    {
      $username = $_REQUEST['username'];
    }
  else
    {
      $username = '';
    }
  if(!empty($_REQUEST['email']))
    {
      $email = $_REQUEST['email'];
    }
  else
    {
      $email = '';
    }

  echo "<h1>"._('Lost password')."</h1>\n";
  echo "<form method='post'>\n";
  echo "<p>"._('Please enter either your username or your email:')."</p>\n";
  echo "<p>"._('Username:')." <input type='text' name='username' value='$username'></p>\n";
  echo "<p>"._('Email address:')." <input type='text' name='email' value='$email'></p>\n";

  echo "<p>"._('I realize that after this operation, my old password will be destroyed and the system will mail me a new one. Click here to confirm:')." <input type='checkbox' name='confirm_new_password' value='true'></p>\n";
  echo "<input type='hidden' name='action' value='mail_new_password'>\n";
  echo "<p><input type='submit'></p>\n";
  echo "</form>\n";
}

/** Generate a random, eay to type and dictate password.
*/
function randompass()
{
   $rand_pass = ''; // makes sure the $pass var is empty.
   for( $j = 0; $j < 3; $j++ )
   {
       $startnend = array(
           'b','c','d','f','g','h','j','k','l','m','n',
           'p','q','r','s','t','v','w','x','y','z',
       );
       $mid = array(
           'a','e','i','o','u','y',
       );
       $count1 = count( $startnend ) - 1;
       $count2 = count( $mid ) - 1;

       for( $i = 0; $i < 3; $i++)
       {
           if( $i != 1 )
           {
               $rand_pass .= $startnend[rand( 0, $count1 )];
           }
           else
           {
               $rand_pass .= $mid[rand( 0, $count2 )];
           }
       }
   }
   return $rand_pass;
}
/** Send the email with the new password
 @param $new_passord the new password that was set
*/
function send_lost_password_email($email, $new_passord)
{
  global $db;
  $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
  if($user_info==null)
    {
      echo "<p class=error>send_lost_password_email(): Error: Unable to locate $email in the database</p>\n";
    }
  else
    {
      $subject = LOST_PASSWORD_EMAIL_SUBJECT;
      $body = "Hello,

 You have requested that the authentication server send you a new password:

 Username: $user_info[user_id]
 Password: $new_passord

 To protect your account, it is recommended that you change your password immediately.

 Thank you,

 The Team";
      $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

      mail($email, $subject, $body, $from);
            echo "<p>"._('Your password has been mailed to you.')."</p>\n";
    }
}



$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' New account registration');
$showform=true;
echo "<div class='content'>\n";

if(empty($_REQUEST['action']))
  {

  }
else
  {
    if(!empty($_REQUEST['username'])) 
      {
	$username = $db->EscapeString(trim($_REQUEST['username']));
      }
    else
      {
	$username = '';
      }
    if(!empty($_REQUEST['email']))
      {
	$email = $email = $db->EscapeString(trim($_REQUEST['email']));
      }
    else
      {
	$email = '';
      }
     

    /* Lost username */
    if ($_REQUEST['action']=='lost_username_form')
      {
	display_lost_username_form();
      }//End action==lost_info_form
    else if ($_REQUEST['action']=='mail_lost_username')
      {
	$user_info=null;
	if($email)
	  {
	    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
	    if($user_info==null)
	      {
		echo "<p class=warning>"._("Unable to find $email in the database.")."</p>\n";
	      }
	  }
	else
	  {
	    echo "<p class=warning>"._('You must specify your email address.')."</p>\n";
	  }
	 
	if($user_info==null)
	  {
	    display_lost_username_form();
	  }
	else
	  {
	    send_lost_username_email($user_info['email']);
	  }
      }//End action==mail_lost_username
     


    /* Lost password */
    else if ($_REQUEST['action']=='lost_password_form')
      {
	display_lost_password_form();
      }//End action==lost_info_form
     
    else if ($_REQUEST['action']=='mail_new_password')
      {
	$user_info=null;
	if(empty($_REQUEST['confirm_new_password']) || $_REQUEST['confirm_new_password']!='true')
	  {
	    echo "<p class=warning>"._("This will destroy your previous password, you must confirm this operation.")."</p>\n";
	  }
	else
	  {
	    if($username)
	      {
		$db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username'", $user_info, false);
		if($user_info==null)
		  {
		    echo "<p class=warning>"._("Unable to find $username in the database.")."</p>\n";
		  }
	      }
	    else if($email)
	      {
		$db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
		if($user_info==null)
		  {
		    echo "<p class=warning>"._("Unable to find $email in the database.")."</p>\n";
		  }
	      }
	    else
	      {
		echo "<p class=error>"._('Your must specify either your username or your email.')."</p>\n";
	      }
	  }

	if($user_info==null)
	  {
	    display_lost_password_form();
	  }
	else
	  {
	    $new_password=randompass();
	    $password_hash = get_password_hash($new_password);
$update_successful = $db->ExecSqlUpdate("UPDATE users  SET pass='$password_hash' WHERE user_id='$user_info[user_id]'");
	    if ($update_successful)
	      {
		send_lost_password_email($user_info['email'], $new_password);
		$showform=false;
	      }
	    else
	      {
		echo "<p class=warning>"._('Internal error.')."</p>\n";
	      }
	  }
      }//End action==mail_new_password



    /* Change password */
    else if ($_REQUEST['action']=='change_password_form')
      {
	display_change_password_form();
      }
    else if ($_REQUEST['action']=='change_password')
      {
	$pass = $db->EscapeString(trim($_REQUEST['pass']));
	$new_pass = $db->EscapeString(trim($_REQUEST['new_pass']));

	$preconditions_ok = false;
	$db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username'", $user_info, false);
	if($user_info==null)
	  {
	    		    echo "<p class=warning>"._("Unable to find $username in the database.")."</p>\n";
	  }
	else
	  {
	    $user_info=null;
	    $password_hash = get_password_hash($pass);
	    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username' AND pass='$password_hash'", $user_info, false);
	    if($user_info==null)
	      {
			    		    echo "<p class=warning>"._("Wrong password for $username.")."</p>\n";
	      }
	    else
	      {
		if ($_REQUEST['new_pass'] != $_REQUEST['new_pass_again']) 
		  {
		    echo "<p class=warning>"._('The two passwords do not match.')."</p>\n";
		  }
		else
		  {
		    if (empty($new_pass)) 
		      {
			echo "<p class=warning>"._('Sorry, empty passwords are not allowed.')."</p>\n";
		      }
		    else
		      {
			$preconditions_ok = true;
		      }
		  }
	      }
	  }

	if(  $preconditions_ok == true)
	  {
	  $password_hash = get_password_hash($new_pass);
	    $update_successful = $db->ExecSqlUpdate("UPDATE users  SET pass='$password_hash' WHERE user_id='$user_info[user_id]'");
	    if ($update_successful)
	      {
		echo "<p class=ok>"._('Your password was successfully changed.')."</p>\n";
	      }
	    else
	      {
		echo "<p class=warning>"._('Internal error.')."</p>\n";
	      }
	  }
	else
	  {
	display_change_password_form();
	  }
      }//End action==change_password
      


    /*********** New account and validation ********/

    else if ($_REQUEST['action']=='register_new_account_form')
      {
	display_register_form();
      }
    else if ($_REQUEST['action']=='create_new_account')
      {
	$pass = $db->EscapeString(trim($_REQUEST['pass']));
	/* Check for dublicate email in the database */
	$preconditions_ok = false;
	$db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username'", $user_info_username, false);
	if($user_info_username!=null)
	  {
	    echo "<p class=warning>"._('Sorry, a user account is already associated to this username.  You will have to chose another.')."</p>\n";
	  }
	else
	  {
	    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info_email, false);
	    if($user_info_email!=null)
	      {
		echo "<p class=warning>"._('Sorry, a user account is already associated to the email adress: ')."</p>\n";
		echo "<p>"._('If it really is your email, I can');
		echo " <a href='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?email=" . $_REQUEST["email"] . "&action=mail_lost_username'>" . _('send you your username by email')."</a>\n";
		echo _(', or even ')."\n";
		echo " <a href='http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?email=" . $_REQUEST["email"] . "&action=mail_new_password'>" . _('send you a new password by email')."</a>"."</p>\n";
	      }
	    else
	      {
		if ($_REQUEST['pass'] != $_REQUEST['pass_again']) 
		  {
		    echo "<p class=warning>"._('The two passwords do not match.')."</p>\n";
		  }
		else
		  {
		    if (empty($_REQUEST['pass'])) 
		      {
			echo "<p class=warning>"._('Sorry, empty passwords are not allowed.')."</p>\n";
		      }
		    else
		      {
			$preconditions_ok = true;
		      }
		  }
	      }
	  }
	if(  $preconditions_ok == true)
	  {
	    $status = ACCOUNT_STATUS_VALIDATION;
	    $token = gentoken();
	    $reg_date = time();
	    $password_hash = get_password_hash($pass);
	    $update_successful = $db->ExecSqlUpdate("INSERT INTO users (user_id,email,pass,account_status,validation_token,reg_date) VALUES ('$username','$email','$password_hash','{$status}','{$token}','{$reg_date}')");
	    if ($update_successful)
	      {
		send_validation_email($email);
		$showform=false;
	      }
	    else 
	      {
		echo "<p class=warning>"._('Internal error.')."</p>\n";
	      }
	  }
	if($showform==true)
	  {
	    //No action was performed successfully
	    display_register_form();
	  }
      }//End action==create_new_account
      
    elseif ($_REQUEST['action']=='validate')
    {
      $validation_token = $db->EscapeString($_REQUEST['validation_token']);
      $db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username' AND validation_token='$validation_token'", $user_info);
      if ($user_info!=null)
	{
	  if($user_info['account_status']==ACCOUNT_STATUS_ALLOWED)
	    {
		  echo "<p class=ok>"._('Your account was already activated.')."</p>\n";
	    }
	  else
	    {
	      $status = $db->EscapeString(ACCOUNT_STATUS_ALLOWED);
	      $update_successful = $db->ExecSqlUpdate("UPDATE users SET account_status='{$status}' WHERE user_id='$username' AND validation_token='$validation_token'");
	      if ($update_successful)
		{
		  echo "<p class=ok>"._('Your account has succesfully activated! Enjoy!')."</p>\n";
		  $showform=false;
		} 
	      else 
		{
		  echo "<p class=warning>"._('Internal error.')."</p>\n";
		}
	    }
	}
      else
	{
	  echo "<p class=error>"._("Sorry, validation token $validation_token is not valid!")."</p>\n";
	}
      }//End action==validate


    else if ($_REQUEST['action']=='validation_email_form')
      {
	display_validation_email_form();
      }//end action==validation_email_form

    else if ($_REQUEST['action']=='send_validation_email')
      {
	$db->ExecSqlUniqueRes("SELECT * FROM users WHERE user_id='$username'", $user_info, false);
	if($user_info==null)
	  {
	    echo "<p class=warning>"._("Unable to find $username in the database.")."</p>\n";
	  }
	else
	  {
	    send_validation_email($user_info['email']);
	  }
      }//end action==send_validation_email
  }
echo "</div>\n";
echo "<div id='navLeft'>\n";
echo get_user_management_menu();
echo "</div>\n";
echo $style->GetFooter();
?>
