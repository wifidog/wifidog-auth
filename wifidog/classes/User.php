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
require_once BASEPATH.'classes/Network.php';
require_once BASEPATH.'classes/Mail.php';

/** Abstract a User. */
class User implements GenericObject
{
	private $mRow;
	private $id;

	/** Instantiate a user object 
	 * @param $id The user id of the requested user 
	 * @return a User object, or null if there was an error
	 */
	public static function getObject($id)
	{
		$object = null;
		$object = new self($id);
		return $object;
	}

	static function createNewObject()
	{
		echo "<h1>Use User::createUser() instead</h1>";
	}
	/** Get an interface to create a new object.
	* @return html markup
	*/
	public static function getCreateNewObjectUI()
	{
		return null;
	}

	/** Process the new object interface. 
	 *  Will       return the new object if the user has the credentials
	 * necessary (Else an exception is thrown) and and the form was fully
	 * filled (Else the object returns null).
	 * @return the node object or null if no new node was created.
	 */
	static function processCreateNewObjectUI()
	{
		return self :: createNewObject();
	}
	/** Instantiate the current user
	 * @return a User object, or null if there was an error
	 */
	public static function getCurrentUser()
	{
		require_once BASEPATH.'classes/Session.php';
		$session = new Session();
		$user = null;
		try
		{
			$user = self :: getObject($session->get(SESS_USER_ID_VAR));
			//$user = new User($session->get(SESS_USER_ID_VAR));
		}
		catch (Exception $e)
		{
			/**If any problem occurs, the user should be considered logged out*/
			$session->set(SESS_USER_ID_VAR, null);
		}
		return $user;
	}

	/** Associates the user passed in parameter with the session.  This should NOT be called by anything except the Authenticators
	 * @param User a user object
	 * @return boolean true if everything went well setting the session...
	 */
	public static function setCurrentUser(User $user)
	{
		try
		{
			$session = new Session();
			$session->set(SESS_USER_ID_VAR, $user->getId());
			$session->set(SESS_PASSWORD_HASH_VAR, $user->getPasswordHash());
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/** Instantiate a user object 
	 * @param $username The username of the user
	 * @param $account_origin Network:  The account origin
	 * @return a User object, or null if there was an error
	 */
	public static function getUserByUsernameAndOrigin($username, Network $account_origin)
	{
		global $db;
		$object = null;

		$username_str = $db->EscapeString($username);
		$account_origin_str = $db->EscapeString($account_origin->getId());
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE username = '$username_str' AND account_origin = '$account_origin_str'", $user_info, false);

		if ($user_info != null)
			$object = new self($user_info['user_id']);
		return $object;
	}

	/** Instantiate a user object 
	 * @param $email The email of the user
	 * @param $account_origin Network:  The account origin
	 * @return a User object, or null if there was an error
	 */
	public static function getUserByEmailAndOrigin($email, Network $account_origin)
	{
		global $db;
		$object = null;

		$email_str = $db->EscapeString($email);
		$account_origin_str = $db->EscapeString($account_origin->getId());
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE email = '$email_str' AND account_origin = '$account_origin_str'", $user_info, false);

		if ($user_info != null)
			$object = new self($user_info['user_id']);
		return $object;
	}

	/** Returns the hash of the password suitable for storing or comparing in the database.  This hash is the same one as used in NoCat
	 * @return The 32 character hash.
	 */
	public static function passwordHash($password)
	{
		/**
		 * utf8_decode is used for backward compatibility with old passwords
		 * containing special characters. 
		 * Conversion from UTF-8 to ISO-8859-1 is done to match the MD5 hash
		 */
		return base64_encode(pack("H*", md5(utf8_decode($password))));
	}

	/** Create a new User in the database 
	 * @param $id The id to be given to the new user
	 * @return the newly created User object, or null if there was an error
	 */
	static function createUser($id, $username, Network $account_origin, $email, $password)
	{
		global $db;

		$object = null;
		$id_str = $db->EscapeString($id);
		$username_str = $db->EscapeString($username);
		$account_origin_str = $db->EscapeString($account_origin->getId());
		$email_str = $db->EscapeString($email);

		$password_hash = $db->EscapeString(User :: passwordHash($password));
		$status = ACCOUNT_STATUS_VALIDATION;
		$token = User :: generateToken();

		$db->ExecSqlUpdate("INSERT INTO users (user_id,username, account_origin,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$username_str','$account_origin_str','$email_str','$password_hash','$status','$token',NOW())");

		$object = new self($id);
		return $object;
	}

	public static function purgeUnvalidatedUsers($days_since_creation)
	{
		global $db;
		$days_since_creation = $db->EscapeString($days_since_creation);

		//$db->ExecSqlUpdate("INSERT INTO users (user_id,username, account_origin,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$username_str','$account_origin_str','$email_str','$password_hash','$status','$token',NOW())");
	}

	/** @param $object_id The id of the user */
	function __construct($object_id)
	{
		global $db;
		$object_id_str = $db->EscapeString($object_id);
		$sql = "SELECT * FROM users WHERE user_id='{$object_id_str}'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception(sprintf(_("User id: %s could not be found in the database"), $object_id_str));
		}
		$this->mRow = $row;
		$this->id = $row['user_id'];
	} //End class

	function getId()
	{
		return $this->id;
	}

	/** Gets the Network to which the user belongs 
	 * @return Network object (never returns null)
	 */
	public function getNetwork()
	{
		return Network :: getObject($this->mRow['account_origin']);
	}

	/** Get a user display suitable for a user list.  Will include link to the user profile. */
	function getUserListUI()
	{
		$html = '';
		$html .= $this->getUserName();
		return $html;
	}

	function getUsername()
	{
		return $this->mRow['username'];
	}

	public function getEmail()
	{
		return $this->mRow['email'];
	}

	public function getRealName()
	{
		return $this->mRow['real_name'];
	}

	public function setRealName()
	{
	}

	public function getWebsiteURL()
	{
		return $this->mRow['website'];
	}

	public function setWebsiteURL()
	{
	}

	/**What locale (language) does the user prefer?
	 * @todo Save in the database */
	public function getPreferedLocale()
	{
		global $session;
		//return $this->mRow['prefered_locale'];
		$locale = $session->get('SESS_LANGUAGE_VAR');
		if (empty ($locale))
		{
			$locale = DEFAULT_LANG;
		}
		return $locale;
	}

	/** get the hashed password stored in the database */
	public function getPasswordHash()
	{
		return $this->mRow['pass'];
	}

	/** Get the account status.  
	 * @return Possible values are listed in common.php
	*/
	function getAccountStatus()
	{
		return $this->mRow['account_status'];
	}

	function setAccountStatus($status)
	{
		global $db;

		$status_str = $db->EscapeString($status);
		if (!($update = $db->ExecSqlUpdate("UPDATE users SET account_status='{$status_str}' WHERE user_id='{$this->id}'")))
		{
			throw new Exception(_("Could not update status."));
		}
		$this->mRow['account_status'] = $status;
	}

	/** Is the user valid?  Valid means that the account is validated or hasn't exhausted it's validation period. 
	 $errmsg: Returs the reason why the account is or isn't valid */
	function isUserValid(& $errmsg = null)
	{
		global $db;
		$retval = false;
		$account_status = $this->getAccountStatus();
		if ($account_status == ACCOUNT_STATUS_ALLOWED)
		{
			$retval = true;
		}
		else
			if ($account_status == ACCOUNT_STATUS_VALIDATION)
			{
				$sql = "SELECT CASE WHEN ((NOW() - reg_date) > interval networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired, networks.validation_grace_time FROM users  JOIN networks ON (users.account_origin = networks.network_id) WHERE (user_id='{$this->id}')";
				$db->ExecSqlUniqueRes($sql, $user_info, false);

				if ($user_info['validation_grace_time_expired'] == 't')
				{
					$errmsg = sprintf(_("Sorry, your %s minutes grace period to retrieve your email and validate your account has now expired. You will have to connect to the internet and validate your account from another location or create a new account. For help, please %s click here %s."), $user_info['validation_grace_time_expired'], '<a href="'.BASEPATH.'faq.php'.'">', '</a>');
					$retval = false;
				}
				else
				{
					$errmsg = _("Your account is currently valid.");
					$retval = true;
				}
			}
			else
			{
				$errmsg = _("Sorry, your account is not valid: ").$account_status_to_text[$account_status];
				$retval = false;
			}
		return $retval;
	}

	public function isSuperAdmin()
	{
		global $db;
		//$this->session->dump();

		$db->ExecSqlUniqueRes("SELECT * FROM users NATURAL JOIN administrators WHERE (users.user_id='$this->id')", $user_info, false);
		if (!empty ($user_info))
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	/**
	 * Tells if the current user is owner of at least one hotspot.
	 */
	public function isOwner()
	{
		global $db;
		$db->ExecSqlUniqueRes("SELECT * FROM node_stakeholders WHERE is_owner = true AND user_id='{$this->getId()}'", $row, false);
		if ($row != null)
			return true;
		return false;

	}

	public function isNobody()
	{
		global $db;
		$db->ExecSqlUniqueRes("SELECT DISTINCT user_id FROM (SELECT user_id FROM network_stakeholders WHERE user_id='{$this->getId()}' UNION SELECT user_id FROM node_stakeholders WHERE user_id='{$this->getId()}' UNION SELECT user_id FROM administrators WHERE user_id='{$this->getId()}') as tmp", $row, false);
		if ($row == null)
			return true;
		return false;
	}

	function getValidationToken()
	{
		return $this->mRow['validation_token'];
	}

	/** Generate a token in the connection table so the user can actually use the internet 
	@return true on success, false on failure 
	*/
	function generateConnectionToken()
	{
		if ($this->isUserValid())
		{
			global $db;
			global $session;

			$token = self :: generateToken();
			if ($_SERVER['REMOTE_ADDR'])
			{
				$node_ip = $db->EscapeString($_SERVER['REMOTE_ADDR']);
			}

			if ($session && $node_ip && $session->get(SESS_GW_ID_VAR))
			{
				$node_id = $db->EscapeString($session->get(SESS_GW_ID_VAR));
				$db->ExecSqlUpdate("INSERT INTO connections (user_id, token, token_status, timestamp_in, node_id, node_ip, last_updated) VALUES ('".$this->getId()."', '$token', '".TOKEN_UNUSED."', NOW(), '$node_id', '$node_ip', NOW())", false);
				$retval = $token;
			}
			else
				$retval = false;
		}
		else
		{
			$retval = false;
		}
		return $retval;
	}

	function setPassword($password)
	{
		global $db;

		$new_password_hash = User :: passwordHash($password);
		if (!($update = $db->ExecSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$this->id}'")))
		{
			throw new Exception(_("Could not change user's password."));
		}
		$this->mRow['pass'] = $password;
	}

	function getAccountOrigin()
	{
		return $this->mRow['account_origin'];
	}

	/** Return all the users
	 */
	static function getAllUsers()
	{
		global $db;

		$db->ExecSql("SELECT * FROM users", $objects, false);
		if ($objects == null)
		{
			throw new Exception(_("No users could not be found in the database"));
		}
		return $objects;
	}

	function sendLostUsername()
	{
		$network = $this->getNetwork();
		$mail = new Mail();
		$mail->setSenderName(_("Registration system"));
		$mail->setSenderEmail($network->getValidationEmailFromAddress());
		$mail->setRecipientEmail($this->getEmail());
		$mail->setMessageSubject($network->getName()._(" lost username request"));
		$mail->setMessageBody(_("Hello,\nYou have requested that the authentication server send you your username:\nUsername: ").$this->getUsername()._("\n\nHave a nice day,\nThe Team"));
		$mail->send();
	}

	function sendValidationEmail()
	{
		if ($this->getAccountStatus() != ACCOUNT_STATUS_VALIDATION)
		{
			throw new Exception(_("The user is not in validation period."));
		}
		else
		{
			if ($this->getValidationToken() == "")
			{
				throw new Exception(_("The validation token is empty."));
			}
			else
			{
				$network = $this->getNetwork();

				$mail = new Mail();
				$mail->setSenderName(_("Registration system"));
				$mail->setSenderEmail($network->getValidationEmailFromAddress());
				$mail->setRecipientEmail($this->getEmail());
				$mail->setMessageSubject($network->getName()._(" new user validation"));
				$url = "http://".$_SERVER["SERVER_NAME"]."/validate.php?user_id=".$this->getId()."&token=".$this->getValidationToken();
				$mail->setMessageBody(_("Hello,\nPlease follow the link below to validate your account.\n").$url._("\n\nThank you,\nThe Team."));
				$mail->send();
			}
		}
	}

	function sendLostPasswordEmail()
	{
		$network = $this->getNetwork();
		$new_password = $this->randomPass();
		$this->setPassword($new_password);

		$mail = new Mail();
		$mail->setSenderName(_("Registration system"));
		$mail->setSenderEmail($network->getValidationEmailFromAddress());
		$mail->setRecipientEmail($this->getEmail());
		$mail->setMessageSubject($network->getName()._(" new password request"));
		$mail->setMessageBody(_("Hello,\nYou have requested that the authentication server send you a new password:\nUsername: ").$this->getUsername()._("\nPassword: ").$new_password._("\n\nHave a nice day,\nThe Team"));
		$mail->send();
	}

	static function userExists($id)
	{
		global $db;
		$id_str = $db->EscapeString($id);
		$sql = "SELECT * FROM users WHERE user_id='{$id_str}'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		return $row;
	}

	public static function emailExists($id)
	{
		global $db;
		$id_str = $db->EscapeString($id);
		$sql = "SELECT * FROM users WHERE email='{$id_str}'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		return $row;
	}

	public static function randomPass()
	{
		$rand_pass = ''; // makes sure the $pass var is empty.
		for ($j = 0; $j < 3; $j ++)
		{
			$startnend = array ('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z',);
			$id = array ('a', 'e', 'i', 'o', 'u', 'y',);
			$count1 = count($startnend) - 1;
			$count2 = count($id) - 1;

			for ($i = 0; $i < 3; $i ++)
			{
				if ($i != 1)
				{
					$rand_pass .= $startnend[rand(0, $count1)];
				}
				else
				{
					$rand_pass .= $id[rand(0, $count2)];
				}
			}
		}
		return $rand_pass;
	}

	public static function generateToken()
	{
		return md5(uniqid(rand(), 1));
	}

	/** Get an interface to add a user to a list
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	* @return html markup
	*/
	static function getSelectUserUI($user_prefix)
	{
		global $db;
		$html = '';
		$html .= Network :: getSelectNetworkUI($user_prefix);
		$html .= "<br>";
		$name = "select_user_{$user_prefix}_username";
		$html .= _("Username: ")."\n";
		$html .= "<input type='text' name='$name' value=''>\n";
		return $html;
	}

	/** Get the selected user, IF one was selected and is valid
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the User object, or null if the user is invalid or none was selected
	 */
	static function processSelectUserUI($user_prefix)
	{
		$object = null;
		try
		{
			$network = Network :: processSelectNetworkUI($user_prefix);
			$name = "select_user_{$user_prefix}_username";
			if (!empty ($_REQUEST[$name]))
			{
				$username = $_REQUEST[$name];
				return self :: getUserByUsernameAndOrigin($username, $network);
			}
			else
				return null;
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getAdminUI()
	{
		global $db;
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>User instance</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Username")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		//$name = "user_".$this->getId()."_username";
		//$html .= "<input type='text' name='$name' value='".htmlentities($this->getUsername())."' size=30 readonly>\n";
		$html .= $this->getUsername()."\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		//TODO: implement this when Network abstraction is completed
		/*
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "user_".$this->getId()."_username";
		// Show network name here
		$html .= "</div>\n";
		$html .= "</div>\n";*/

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Real name")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "user_".$this->getId()."_real_name";
		$html .= "<input type='text' name='$name' value='".htmlentities($this->getRealName())."' size=30 readonly>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Website URL")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "user_".$this->getId()."_website";
		$html .= "<input type='text' name='$name' value='".htmlentities($this->getWebsiteURL())."' size=30 readonly>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "</div>\n";
		return $html;
	}

	public function processAdminUI()
	{
	}

	public function delete(& $errmsg)
	{
	}

	public function getUserUI()
	{
	}

	/** Add content to this user ( subscription ) */
	public function addContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "INSERT INTO user_has_content (user_id, content_id) VALUES ('$this->id','$content_id')";
		$db->ExecSqlUpdate($sql, false);
		return true;
	}

	/** Remove content from this node */
	public function removeContent(Content $content)
	{
		global $db;
		$content_id = $db->EscapeString($content->getId());
		$sql = "DELETE FROM user_has_content WHERE user_id='$this->id' AND content_id='$content_id'";
		$db->ExecSqlUpdate($sql, false);
		return true;
	}

	/**Get an array of all Content linked to this node
	* @return an array of Content or an empty arrray */
	function getAllContent()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM user_has_content WHERE user_id='$this->id' ORDER BY subscribe_timestamp";
		$db->ExecSql($sql, $content_rows, false);
		if ($content_rows != null)
		{
			foreach ($content_rows as $content_row)
			{
				$retval[] = Content :: getObject($content_row['content_id']);
			}
		}
		return $retval;
	}

} // End class
?>