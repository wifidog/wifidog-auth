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
  /**@file User.php
   * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>
   */
   
require_once BASEPATH.'include/common.php';

/** Abstract a User.  A User is an actual physical transmitter. */
class User {
  private $mRow;
  private $mId;
  
  /** Instantiate a user object 
   * @param $id The id of the requested user 
   * @return a User object, or null if there was an error
   */
  static function getUserByID($id) {
      $object = null;
      $object = new self("user_id", $id);
      return $object;
    }
  
  /** Instantiate a user object 
   * @param $id The id of the requested user 
   * @return a User object, or null if there was an error
   */
  static function getUserByEmail($id) {
      $object = null;
      $object = new self("email", $id);
      return $object;
    }

  /** Create a new User in the database 
   * @param $id The id to be given to the new user
   * @return the newly created User object, or null if there was an error
   */
  static function createUser($id, $email, $password) {
      global $db;

      $object = null;
      $id_str = $db->EscapeString($id);
      $email_str = $db->EscapeString($email);
      $password_hash = $db->EscapeString(User::passwordHash($password));
      $status = ACCOUNT_STATUS_VALIDATION;
      $token = User::generateToken();

      $db->ExecSqlUpdate("INSERT INTO users (user_id,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$email_str','$password_hash','$status','$token',NOW())");

      $object = new self('user_id', $id_str);
      return $object;
    }
  
/** @param $object_id The id of the user */
  function __construct($field_id, $object_id) {
    global $db;
    $object_id_str = $db->EscapeString($object_id);
    $sql = "SELECT * FROM users WHERE {$field_id}='{$object_id_str}'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    if ($row == null) {
        throw new Exception(_("{$field_id} '{$object_id_str}' could not be found in the database"));
    }
    $this->mRow = $row;  
    $this->mId  = $row['user_id'];
  }//End class
  
  function getName() {
    return $this->mId;
  }

  function getEmail() {
    return $this->mRow['email'];
  }

  function getPasswordHash() {
    return $this->mRow['pass'];
  }

  function getAccountStatus() {
    return $this->mRow['account_status'];
  }

  function getValidationToken() {
    return $this->mRow['validation_token'];
  }

  function setPassword($password) {
    global $db;

    $new_password_hash = $this->passwordHash($password);
	if (!($update = $db->ExecSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$this->mId}'"))) {
        throw new Exception(_("Could not change user's password."));
    }
    $this->mRow['pass'] = $password;
  }

  function setAccountStatus($status) {
    global $db;

    $status_str = $db->EscapeString($status);
	if (!($update = $db->ExecSqlUpdate("UPDATE users SET account_status='{$status_str}' WHERE user_id='{$this->mId}'"))) {
        throw new Exception(_("Could not update status."));
    }
    $this->mRow['account_status'] = $status;
  }

  /** Return all the users
   */
  static function getAllUsers() {
    global $db;

    $db->ExecSql("SELECT * FROM users", $objects, false);
    if ($objects == null) {
        throw new Exception(_("No users could not be found in the database"));
    }
    return $objects;
  }

  function sendLostUsername() {
    $user_id = $this->getName();
    $subject = LOST_USERNAME_EMAIL_SUBJECT;
    $from = "From: " . VALIDATION_EMAIL_FROM_ADDRESS;
    $body = "Hello,

You have requested that the authentication server send you your username:

Username: $user_id

Have a nice day,

The Team";

    mail($this->getEmail(), $subject, $body, $from);
  }


  function sendValidationEmail() {
    if ($this->getAccountStatus() != ACCOUNT_STATUS_VALIDATION) {
        throw new Exception(_("The user is not in validation period."));
	} else {
        if ($this->getValidationToken() == "") {
            throw new Exception(_("The validation token is empty."));
        } else {
            $subject = VALIDATION_EMAIL_SUBJECT;
            $url = "http://" . $_SERVER["SERVER_NAME"] . "/validate.php?username=" . $this->getName() . "&token=" . $this->getValidationToken();
            $body = "Hello!

Please follow the link below to validate your account.

$url

Thank you,

The Team";
            $from = "From: " . VALIDATION_EMAIL_FROM_ADDRESS;

            mail($this->getEmail(), $subject, $body, $from);
        }
    }
  }



  function sendLostPasswordEmail() {
    global $db;

    $new_password = $this->randomPass();
    $this->setPassword($new_password);

    $user_id = $this->getName();

    $subject = LOST_PASSWORD_EMAIL_SUBJECT;
    $body = "Hello,

You have requested that the authentication server send you a new password:

Username: $user_id
Password: $new_password

Have a nice day,

The Team";
    $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

    mail($this->getEmail(), $subject, $body, $from);
  }

  function userExists($id) {
    global $db;
    $id_str = $db->EscapeString($id);
    $sql = "SELECT * FROM users WHERE user_id='{$id_str}'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    return $row;
  }

  function emailExists($id) {
    global $db;
    $id_str = $db->EscapeString($id);
    $sql = "SELECT * FROM users WHERE email='{$id_str}'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    return $row;
  }

  public static function randomPass() {
   $rand_pass = ''; // makes sure the $pass var is empty.
   for ($j = 0; $j < 3; $j++) {
       $startnend = array(
           'b','c','d','f','g','h','j','k','l','m','n',
           'p','q','r','s','t','v','w','x','y','z',
       );
       $mid = array(
           'a','e','i','o','u','y',
       );
       $count1 = count($startnend) - 1;
       $count2 = count($mid) - 1;

       for ($i = 0; $i < 3; $i++) {
           if ($i != 1) {
               $rand_pass .= $startnend[rand(0, $count1)];
           } else {
               $rand_pass .= $mid[rand(0, $count2)];
           }
       }
   }
   return $rand_pass;
  }

    public static function generateToken() {
        return md5(uniqid(rand(),1));
    }

    /** Returns the hash of the password suitable for storing or comparing in the database.
    * @return The 32 character hash.
    */
    public static function passwordHash($password) {
        return base64_encode(pack("H*", md5($password)));
    }

}// End class
?>
