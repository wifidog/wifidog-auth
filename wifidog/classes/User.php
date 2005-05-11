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

	/** Instantiate a user object 
	 * @param $username The username of the user
	 * @param $account_origin The account origin
	 * @return a User object, or null if there was an error
	 */
	public static function getUserByUsernameAndOrigin($username, $account_origin)
	{
		global $db;
		$object = null;

		$username_str = $db->EscapeString($username);
		$account_origin_str = $db->EscapeString($account_origin);
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE username = '$username_str' AND account_origin = '$account_origin_str'", $user_info, false);

		if ($user_info != null)
			$object = new self($user_info['user_id']);
		return $object;
	}

	/** Instantiate a user object 
	 * @param $email The email of the user
	 * @param $account_origin The account origin
	 * @return a User object, or null if there was an error
	 */
	public static function getUserByEmailAndOrigin($email, $account_origin)
	{
		global $db;
		$object = null;

		$email_str = $db->EscapeString($email);
		$account_origin_str = $db->EscapeString($account_origin);
		$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE email = '$email_str' AND account_origin = '$account_origin_str'", $user_info, false);

		if ($user_info != null)
			$object = new self($user_info['user_id']);
		return $object;
	}

	/**
	 * Get the list of users associated with a username 
	 * Since we cannot guarantee the uniqueness of (user, e-mail) key this will
	 * return an array.
	 * 
	 * NB : This function will only extract users who authenticate
	 * through a LocalUserAuthenticator 
	 * (see AuthenticatorLocalUser::getAllLocalUserAccountOrigins)
	 * 
	 * @param $username : the username criterion
	 * @return array : array of User objects
	 */
	public static function getUsersByUsername($username)
	{
		$users_list = array ();

		// E-mail cannot be empty, will return an empty array.
		if (!empty ($username))
		{
			// Build SQL query, excluding users who do not authenticate through LocalUserAuth
			global $db;
			$username_str = $db->EscapeString($username);
			$sql = "SELECT user_id FROM users WHERE username = '$username_str'";
			$first = true;
			foreach (array_keys(AuthenticatorLocalUser :: getAllLocalUserAccountOrigins()) as $account_origin)
			{
				if ($first === true)
				{
					$sql .= " AND (account_origin = '$account_origin'";
					$first = false;
				}
				else
					$sql .= " OR account_origin = '$account_origin'";
			}
			if ($first === false)
				$sql .= ")";
			$db->ExecSql($sql, $users_rows, false);

			// Fill an array with User objects corresponding to those we just got
			if (!empty ($users_rows))
				foreach ($users_rows as $user_row)
					$users_list[] = new User($user_row['user_id']);
		}

		return $users_list;
	}

	/**
	 * Get the list of users associated with an e-mail address
	 * Since we cannot guarantee the unicity of (user, e-mail) key
	 * this will return an array.
	 * 
	 * NB : This function will only extract users who authenticate
	 * through a LocalUserAuthenticator 
	 * (see AuthenticatorLocalUser::getAllLocalUserAccountOrigins)
	 * 
	 * @param $email : the e-mail criterion
	 * @return array : array of User objects
	 */
	public static function getUsersByEmail($email)
	{
		$users_list = array ();

		// E-mail cannot be empty, will return an empty array.
		if (!empty ($email))
		{
			// Build SQL query, excluding users who do not authenticate through LocalUserAuth
			global $db;
			$email_str = $db->EscapeString($email);
			$sql = "SELECT user_id FROM users WHERE email = '$email_str'";
			$first = true;
			foreach (array_keys(AuthenticatorLocalUser :: getAllLocalUserAccountOrigins()) as $account_origin)
			{
				if ($first === true)
				{
					$sql .= " AND (account_origin = '$account_origin'";
					$first = false;
				}
				else
					$sql .= " OR account_origin = '$account_origin'";
			}
			if ($first === false)
				$sql .= ")";
			$db->ExecSql($sql, $users_rows, false);

			// Fill an array with User objects corresponding to those we just got
			if (!empty ($users_rows))
				foreach ($users_rows as $user_row)
					$users_list[] = new User($user_row['user_id']);
		}

		return $users_list;
	}

	/** Returns the hash of the password suitable for storing or comparing in the database.  This hash is the same one as used in NoCat
	 * @return The 32 character hash.
	 */
	public static function passwordHash($password)
	{
		return base64_encode(pack("H*", md5($password)));
	}

	/** Create a new User in the database 
	 * @param $id The id to be given to the new user
	 * @return the newly created User object, or null if there was an error
	 */
	static function createUser($id, $username, $account_origin, $email, $password)
	{
		global $db;

		$object = null;
		$id_str = $db->EscapeString($id);
		$username_str = $db->EscapeString($username);
		$account_origin_str = $db->EscapeString($account_origin);
		$email_str = $db->EscapeString($email);
		/**
		 * utf8_decode is used for backward compatibility with old passwords
		 * containing special characters. 
		 * Conversion from UTF-8 to ISO-8859-1 is done to match the MD5 hash
		 */
		$password_hash = $db->EscapeString(User :: passwordHash(utf8_decode($password)));
		$status = ACCOUNT_STATUS_VALIDATION;
		$token = User :: generateToken();

		$db->ExecSqlUpdate("INSERT INTO users (user_id,username, account_origin,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$username_str','$account_origin_str','$email_str','$password_hash','$status','$token',NOW())");

		$object = new self($id);
		return $object;
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
			throw new Exception(_("User id: ").$object_id_str._(" could not be found in the database"));
		}
		$this->mRow = $row;
		$this->id = $row['user_id'];
	} //End class

	function getId()
	{
		return $this->id;
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
				$sql = "SELECT CASE WHEN ((NOW() - reg_date) > interval '".VALIDATION_GRACE_TIME." minutes') THEN true ELSE false END AS validation_grace_time_expired FROM users WHERE (user_id='{$this->id}')";
				$db->ExecSqlUniqueRes($sql, $user_info, false);

				if ($user_info['validation_grace_time_expired'] == 't')
				{
					$errmsg = _("Sorry, your ").VALIDATION_GRACE_TIME._(" minutes grace period to retrieve your email and validate your account has now expired. You will have to connect to the internet and validate your account from another location or create a new account. For help, please ").'<a href="'.BASEPATH.'faq.php'.'">'._("click here.").'</a>';
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
		$db->ExecSqlUniqueRes("SELECT * FROM node_owners WHERE user_id='{$this->getId()}'", $row, false);
		if ($row != null)
			return true;
		return false;

	}

	function getValidationToken()
	{
		return $this->mRow['validation_token'];
	}

	function getInfoArray()
	{
		return $this->mRow;
	}

	/** Generate a token in the connection table so the user can actually use the internet 
	@return true on success, false on failure 
	*/
	function generateConnectionToken()
	{
		if ($this->isUserValid())
		{
			global $db;
			$token = self :: generateToken();
			if ($_SERVER['REMOTE_ADDR'])
			{
				$node_ip = $db->EscapeString($_SERVER['REMOTE_ADDR']);
			}
			if (isset ($_REQUEST['gw_id']) && $_REQUEST['gw_id'])
			{
				$node_id = $db->EscapeString($_REQUEST['gw_id']);
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

		$new_password_hash = User :: passwordHash(utf8_decode($password));
		if (!($update = $db->ExecSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$this->id}'")))
		{
			throw new Exception(_("Could not change user's password."));
		}
		$this->mRow['pass'] = $password;
	}

	function getConnections()
	{
		global $db;
		$db->ExecSql("SELECT * FROM connections,nodes WHERE user_id='{$this->id}' AND nodes.node_id=connections.node_id ORDER BY timestamp_in", $connections, false);
		return $connections;
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

	/**
	 * Logout the current user
	 * Destroying session and cleaning tokens
	 */
	function logout()
	{
		global $session;
		$session->destroy();
		try
		{
			$connections = $this->getConnections();
			if ($connections)
				foreach ($connections as $connection)
					if ($connection['token_status'] == TOKEN_UNUSED || $connection['token_status'] == TOKEN_INUSE)
						Network :: getCurrentNetwork()->getAuthenticator()->logout(array ('conn_id' => $connection['conn_id']), $errmsg);
		}
		catch (Exception $e)
		{
		}
	}

	function sendLostUsername()
	{
		$username = $this->getUsername();
		$headers = 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/plain; charset=UTF-8'."\r\n";
		$headers .= "From: ".VALIDATION_EMAIL_FROM_ADDRESS;
		$subject = HOTSPOT_NETWORK_NAME._(" lost username request");
		$body = _("Hello,\nYou have requested that the authentication server send you your username:\nUsername: ").$username._("\n\nHave a nice day,\nThe Team");

		//TODO: Find a way to use correctly mb_encode_mimeheader 
		$subject = mb_convert_encoding($subject, "ISO-8859-1", "AUTO");

		mail($this->getEmail(), $subject, $body, $headers);
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
				$headers = 'MIME-Version: 1.0'."\r\n";
				$headers .= 'Content-type: text/plain; charset=UTF-8'."\r\n";
				$headers .= "From: ".VALIDATION_EMAIL_FROM_ADDRESS;
				$subject = HOTSPOT_NETWORK_NAME._(" new user validation");
				$url = "http://".$_SERVER["SERVER_NAME"]."/validate.php?user_id=".$this->getId()."&token=".$this->getValidationToken();
				$body = _("Hello,\nPlease follow the link below to validate your account.\n").$url._("\n\nThank you,\nThe Team.");

				//TODO: Find a way to use correctly mb_encode_mimeheader 
				$subject = mb_convert_encoding($subject, "ISO-8859-1", "AUTO");

				mail($this->getEmail(), $subject, $body, $headers);
			}
		}
	}

	function sendLostPasswordEmail()
	{
		global $db;

		$new_password = $this->randomPass();
		$this->setPassword($new_password);
		$username = $this->getUsername();

		$headers = 'MIME-Version: 1.0'."\r\n";
		$headers .= 'Content-type: text/plain; charset=UTF-8'."\r\n";
		$headers .= "From: ".VALIDATION_EMAIL_FROM_ADDRESS;
		$subject = HOTSPOT_NETWORK_NAME._(" new password request");
		$body = _("Hello,\nYou have requested that the authentication server send you a new password:\nUsername: ").$username._("\nPassword: ").$new_password._("\n\nHave a nice day,\nThe Team");

		//TODO: Find a way to use correctly mb_encode_mimeheader 
		$subject = mb_convert_encoding($subject, "ISO-8859-1", "AUTO");

		mail($this->getEmail(), $subject, $body, $headers);
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
		$name = "select_user_{$user_prefix}_username";
		$html .= "Username: \n";
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
		$network = Network :: processSelectNetworkUI($user_prefix);
		$name = "select_user_{$user_prefix}_username";
		$username = $_REQUEST[$name];
		return self :: getUserByUsernameAndOrigin($username, $network->GetId());
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