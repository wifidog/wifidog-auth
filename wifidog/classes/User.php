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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/Network.php');
require_once ('classes/Mail.php');
require_once ('classes/InterfaceElements.php');

/**
 * Abstract a User
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class User implements GenericObject {
    private $mRow;
    private $id;

    /** Instantiate a user object
     * @param $id The user id of the requested user
     * @return a User object, or null if there was an error
     */
    public static function getObject($id) {
        $object = null;
        $object = new self($id);
        return $object;
    }

    static function createNewObject() {
        echo "<h1>Use User::createUser() instead</h1>";
    }
    /** Get an interface to create a new object.
    * @return html markup
    */
    public static function getCreateNewObjectUI() {
        return null;
    }

    /** Process the new object interface.
     *  Will       return the new object if the user has the credentials
     * necessary (Else an exception is thrown) and and the form was fully
     * filled (Else the object returns null).
     * @return the node object or null if no new node was created.
     */
    static function processCreateNewObjectUI() {
        return self :: createNewObject();
    }
    /**
     * Instantiate the current user
     *
     * @return mixed A User object, or null if there was an error
    
     */
    public static function getCurrentUser() {
        require_once ('classes/Session.php');
        $session = Session::getObject();
        $user = null;
        try {
            $user = self :: getObject($session->get(SESS_USER_ID_VAR));
            //$user = new User($session->get(SESS_USER_ID_VAR));
        } catch (Exception $e) {
            /**If any problem occurs, the user should be considered logged out*/
            $session->set(SESS_USER_ID_VAR, null);
        }
        return $user;
    }

    /**
     * Associates the user passed in parameter with the session
     *
     * This should NOT be called by anything except the Authenticators
     *
     * @param object $user User a user object
     *
     * @return bool True if everything went well setting the session
    
     */
    public static function setCurrentUser(User $user) {
        try {
            $session = Session::getObject();
            $session->set(SESS_USER_ID_VAR, $user->getId());
            $session->set(SESS_PASSWORD_HASH_VAR, $user->getPasswordHash());
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the server the user is connected to
     *
     * @return string Hostname of server
    
     */
    public static function getCurrentServer() {
        return $_SERVER['SERVER_NAME'];
    }

    /** Instantiate a user object
     * @param $username The username of the user
     * @param $account_origin Network:  The account origin
     * @return a User object, or null if there was an error
     */
    public static function getUserByUsernameAndOrigin($username, Network $account_origin) {
        $db = AbstractDb::getObject();
        $object = null;

        $username_str = $db->escapeString($username);
        $account_origin_str = $db->escapeString($account_origin->getId());
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE username = '$username_str' AND account_origin = '$account_origin_str'", $user_info, false);

        if ($user_info != null)
            $object = new self($user_info['user_id']);
        return $object;
    }

    /** Instantiate a user object
     * @param $email The email of the user
     * @param $account_origin Network:  The account origin
     * @return a User object, or null if there was an error
     */
    public static function getUserByEmailAndOrigin($email, Network $account_origin) {
        $db = AbstractDb::getObject();
        $object = null;

        $email_str = $db->escapeString($email);
        $account_origin_str = $db->escapeString($account_origin->getId());
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE email = '$email_str' AND account_origin = '$account_origin_str'", $user_info, false);

        if ($user_info != null)
            $object = new self($user_info['user_id']);
        return $object;
    }

    /** Returns the hash of the password suitable for storing or comparing in the database.  This hash is the same one as used in NoCat
     * @return The 32 character hash.
     */
    public static function passwordHash($password) {
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
    static function createUser($id, $username, Network $account_origin, $email, $password) {
        $db = AbstractDb::getObject();

        $object = null;
        $id_str = $db->escapeString($id);
        $username_str = $db->escapeString($username);
        $account_origin_str = $db->escapeString($account_origin->getId());
        $email_str = $db->escapeString($email);

        $password_hash = $db->escapeString(User :: passwordHash($password));
        $status = ACCOUNT_STATUS_VALIDATION;
        $token = User :: generateToken();

        $db->execSqlUpdate("INSERT INTO users (user_id,username, account_origin,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$username_str','$account_origin_str','$email_str','$password_hash','$status','$token',CURRENT_TIMESTAMP)");

        $object = new self($id);
        return $object;
    }

    /*    public static function purgeUnvalidatedUsers($days_since_creation)
        {
            $db = AbstractDb::getObject();
            $days_since_creation = $db->escapeString($days_since_creation);
    
            //$db->execSqlUpdate("INSERT INTO users (user_id,username, account_origin,email,pass,account_status,validation_token,reg_date) VALUES ('$id_str','$username_str','$account_origin_str','$email_str','$password_hash','$status','$token',CURRENT_TIMESTAMP)");
        }*/

    /** @param $object_id The id of the user */
    function __construct($object_id) {
        $db = AbstractDb::getObject();
        $this->mDb = & $db;
        $object_id_str = $db->escapeString($object_id);
        $sql = "SELECT * FROM users WHERE user_id='{$object_id_str}'";
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null) {
            throw new Exception(sprintf(_("User id: %s could not be found in the database"), $object_id_str));
        }
        $this->mRow = $row;
        $this->id = $row['user_id'];
    } //End class

    function getId() {
        return $this->id;
    }

    /** Gets the Network to which the user belongs
     * @return Network object (never returns null)
     */
    public function getNetwork() {
        return Network :: getObject($this->mRow['account_origin']);
    }

    /** Get a user display suitable for a user list.  Will include link to the user profile. */
    function getListUI() {
        /*    $roles = array ();
        
        if ($current_node->isOwner($online_user)) {
        $roles[] = _("owner");
        }
        
        if ($current_node->isTechnicalOfficer($online_user)) {
        $roles[] = _("technical officer");
        }
        
        if ($roles) {
        $rolenames = join($roles, ",");
        }*/
        $html = '';
        if ($this->isSplashOnlyUser()) {
            $html .= _("Guest");
        }
        else {
        	$html .= $this->getUserName();
        }
        return $html;
    }

    function getUsername() {
        return $this->mRow['username'];
    }

    /** Set the user's username
    * @param $value The new value
    * @return true on success, false on failure
    * @throws exception if the user tries to set a duplicate username
    */
    function setUsername($value) {
        $retval = true;
        if ($value != $this->getUsername()) {
            $db = AbstractDb::getObject();
            $value = $db->escapeString($value);
            $retval = @ $db->execSqlUpdate("UPDATE users SET username = '{$value}' WHERE user_id='{$this->id}'", false);
            if (!$retval) {
                throw new exception(sprintf(_("Sorry, the username %s is not available"), $value));
            }
            $this->refresh();
        }
        return $retval;
    }

    public function getEmail() {
        return $this->mRow['email'];
    }

    public function setEmail($email) {
        $email_str = $this->mDb->escapeString($email);
        if (!($update = $this->mDb->execSqlUpdate("UPDATE users SET email='{$email_str}' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not update email address."));
        }
        $this->mRow['email'] = $email; // unescaped
    }

    function setIsInvisible($value) {
        $retval = true;
        if ($value != $this->isAdvertised()) {
            $db = AbstractDb::getObject();
            $value ? $value = 'TRUE' : $value = 'FALSE';
            $retval = $db->execSqlUpdate("UPDATE users SET is_invisible = {$value} WHERE user_id = '{$this->getId()}'", false);
            $this->refresh();
        }
        return $retval;
    }

    public function isInvisible() {
        return (($this->mRow['is_invisible'] == 't') ? true : false);
    }

    /**What locale (language) does the user prefer? */
    public function getPreferedLocale() {
        $session = Session::getObject();
        $locale = $this->mRow['prefered_locale'];
        if (empty ($locale) && !empty ($session))
            $locale = $session->get(SESS_LANGUAGE_VAR);
        if (empty ($locale))
            $locale = DEFAULT_LANG;
        return $locale;
    }

    public function setPreferedLocale($locale) {
        $locale_str = $this->mDb->escapeString($locale);
        if (!($update = $this->mDb->execSqlUpdate("UPDATE users SET prefered_locale='{$locale_str}' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not update username locale."));
        }
        $this->mRow['prefered_locale'] = $locale;
    }

    /** get the hashed password stored in the database */
    public function getPasswordHash() {
        return $this->mRow['pass'];
    }

    /** Get the account status.
     * @return Possible values are listed in common.php
    */
    function getAccountStatus() {
        return $this->mRow['account_status'];
    }

    function setAccountStatus($status) {
        $db = AbstractDb::getObject();

        $status_str = $db->escapeString($status);
        if (!($update = $db->execSqlUpdate("UPDATE users SET account_status='{$status_str}' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not update status."));
        }
        $this->mRow['account_status'] = $status;
    }

    /** Is the user valid?  Valid means that the account is validated or hasn't exhausted it's validation period.
     $errmsg: Returs the reason why the account is or isn't valid */
    function isUserValid(& $errmsg = null) {
        $db = AbstractDb::getObject();
        $retval = false;
        $account_status = $this->getAccountStatus();
        if ($account_status == ACCOUNT_STATUS_ALLOWED) {
            $retval = true;
        } else
            if ($account_status == ACCOUNT_STATUS_VALIDATION) {
                $sql = "SELECT CASE WHEN ((CURRENT_TIMESTAMP - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired, EXTRACT(EPOCH FROM networks.validation_grace_time) as validation_grace_time FROM users  JOIN networks ON (users.account_origin = networks.network_id) WHERE (user_id='{$this->id}')";
                $db->execSqlUniqueRes($sql, $user_info, false);

                if ($user_info['validation_grace_time_expired'] == 't') {
                    $errmsg = sprintf(_("Sorry, your %.0f minutes grace period to retrieve your email and validate your account has now expired. You will have to connect to the internet and validate your account from another location. For more help, please %s click here %s."), $user_info['validation_grace_time']/60, '<a href="' . BASE_URL_PATH . 'faq.php' . '">', '</a>');
                    $retval = false;
                } else {
                    $errmsg = _("Your account is currently valid.");
                    $retval = true;
                }
            } else {
                $errmsg = _("Sorry, your account is not valid: ") . $account_status_to_text[$account_status];
                $retval = false;
            }
        return $retval;
    }

    public function isSuperAdmin() {
        $db = AbstractDb::getObject();
        //$this->session->dump();

        $db->execSqlUniqueRes("SELECT * FROM users NATURAL JOIN administrators WHERE (users.user_id='$this->id')", $user_info, false);
        if (!empty ($user_info)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Tells if the current user is owner of at least one hotspot.
     */
    public function isOwner() {
        $db = AbstractDb::getObject();
        $db->execSql("SELECT * FROM node_stakeholders WHERE is_owner = true AND user_id='{$this->getId()}'", $row, false);
        if ($row != null)
            return true;
        return false;

    }

    public function isNobody() {
        $db = AbstractDb::getObject();
        $db->execSqlUniqueRes("SELECT DISTINCT user_id FROM (SELECT user_id FROM network_stakeholders WHERE user_id='{$this->getId()}' UNION SELECT user_id FROM node_stakeholders WHERE user_id='{$this->getId()}' UNION SELECT user_id FROM administrators WHERE user_id='{$this->getId()}') as tmp", $row, false);
        if ($row == null)
            return true;
        return false;
    }

    /** Is this user the Splash Only User() */
    public function isSplashOnlyUser() {
        if ($this->mRow['username'] == "SPLASH_ONLY_USER") {
            return true;
        } else {
            return false;
        }
    }

    function getValidationToken() {
        return $this->mRow['validation_token'];
    }

    /** Generate a token in the connection table so the user can actually use the internet
    @return true on success, false on failure
    */
    function generateConnectionToken() {
        if ($this->isUserValid()) {
            $db = AbstractDb::getObject();
            $session = Session::getObject();

            $token = self :: generateToken();
            if ($_SERVER['REMOTE_ADDR']) {
                $node_ip = $db->escapeString($_SERVER['REMOTE_ADDR']);
            }

            if ($session && $node_ip && $session->get(SESS_GW_ID_VAR)) {
                $node_id = $db->escapeString($session->get(SESS_GW_ID_VAR));
                $db->execSqlUpdate("INSERT INTO connections (user_id, token, token_status, timestamp_in, node_id, node_ip, last_updated) VALUES ('" . $this->getId() . "', '$token', '" . TOKEN_UNUSED . "', CURRENT_TIMESTAMP, '$node_id', '$node_ip', CURRENT_TIMESTAMP)", false);
                $retval = $token;
            } else
                $retval = false;
        } else {
            $retval = false;
        }
        return $retval;
    }

    function setPassword($password) {
        $db = AbstractDb::getObject();

        $new_password_hash = User :: passwordHash($password);
        if (!($update = $db->execSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not change user's password."));
        }
        $this->mRow['pass'] = $password;
    }

    function getAccountOrigin() {
        return $this->mRow['account_origin'];
    }

    /** Return all the users
     */
    static function getAllUsers() {
        $db = AbstractDb::getObject();

        $db->execSql("SELECT * FROM users", $objects, false);
        if ($objects == null) {
            throw new Exception(_("No users could not be found in the database"));
        }
        return $objects;
    }

    function sendLostUsername() {
        $network = $this->getNetwork();
        $mail = new Mail();
        $mail->setSenderName(_("Registration system"));
        $mail->setSenderEmail($network->getValidationEmailFromAddress());
        $mail->setRecipientEmail($this->getEmail());
        $mail->setMessageSubject($network->getName() . _(" lost username request"));
        $mail->setMessageBody(_("Hello,\nYou have requested that the authentication server send you your username:\nUsername: ") . $this->getUsername() . _("\n\nHave a nice day,\nThe Team"));
        $mail->send();
    }

    function sendValidationEmail() {
        if ($this->getAccountStatus() != ACCOUNT_STATUS_VALIDATION) {
            throw new Exception(_("The user is not in validation period."));
        } else {
            if ($this->getValidationToken() == "") {
                throw new Exception(_("The validation token is empty."));
            } else {
                $network = $this->getNetwork();

                $mail = new Mail();
                $mail->setSenderName(_("Registration system"));
                $mail->setSenderEmail($network->getValidationEmailFromAddress());
                $mail->setRecipientEmail($this->getEmail());
                $mail->setMessageSubject($network->getName() . _(" new user validation"));
                $url = BASE_SSL_PATH . "validate.php?user_id=" . $this->getId() . "&token=" . $this->getValidationToken();
                $mail->setMessageBody(_("Hello,\nPlease follow the link below to validate your account.\n") . $url . _("\n\nThank you,\nThe Team."));
                $mail->send();
            }
        }
    }

    function sendLostPasswordEmail() {
        $network = $this->getNetwork();
        $new_password = $this->randomPass();
        $this->setPassword($new_password);

        $mail = new Mail();
        $mail->setSenderName(_("Registration system"));
        $mail->setSenderEmail($network->getValidationEmailFromAddress());
        $mail->setRecipientEmail($this->getEmail());
        $mail->setMessageSubject($network->getName() . _(" new password request"));
        $mail->setMessageBody(_("Hello,\nYou have requested that the authentication server send you a new password:\nUsername: ") . $this->getUsername() . _("\nPassword: ") . $new_password . _("\n\nHave a nice day,\nThe Team"));
        $mail->send();
    }

    static function userExists($id) {
        $db = AbstractDb::getObject();
        $id_str = $db->escapeString($id);
        $sql = "SELECT * FROM users WHERE user_id='{$id_str}'";
        $db->execSqlUniqueRes($sql, $row, false);
        return $row;
    }

    public static function emailExists($id) {
        $db = AbstractDb::getObject();
        $id_str = $db->escapeString($id);
        $sql = "SELECT * FROM users WHERE email='{$id_str}'";
        $db->execSqlUniqueRes($sql, $row, false);
        return $row;
    }

    public static function randomPass() {
        $rand_pass = ''; // makes sure the $pass var is empty.
        for ($j = 0; $j < 3; $j++) {
            $startnend = array (
                'b',
                'c',
                'd',
                'f',
                'g',
                'h',
                'j',
                'k',
                'l',
                'm',
                'n',
                'p',
                'q',
                'r',
                's',
                't',
                'v',
                'w',
                'x',
                'y',
                'z',
                
            );
            $id = array (
                'a',
                'e',
                'i',
                'o',
                'u',
                'y',
                
            );
            $count1 = count($startnend) - 1;
            $count2 = count($id) - 1;

            for ($i = 0; $i < 3; $i++) {
                if ($i != 1) {
                    $rand_pass .= $startnend[rand(0, $count1)];
                } else {
                    $rand_pass .= $id[rand(0, $count2)];
                }
            }
        }
        return $rand_pass;
    }

    public static function generateToken() {
        return md5(uniqid(rand(), 1));
    }

    /**
     * Get an interface to add a user to a list
     *
     * @param string $user_prefix      A identifier provided by the programmer
     *                                 to recognise it's generated HTML form
     * @param string $add_button_name  Name of optional "add" button
     * @param string $add_button_value Value of optional "add" button
     *
     * @return string HTML markup
    
     */
    public static function getSelectUserUI($user_prefix, $add_button_name = null, $add_button_value = null) {
        
        $db = AbstractDb::getObject();

        $_networkSelector = InterfaceElements :: generateDiv(Network :: getSelectNetworkUI($user_prefix), "admin_section_network_selector", "admin_section_network_selector_" . $user_prefix);

        // Check if we need to add an "add" button
        if ($add_button_name && $add_button_value) {
            $_userSelector = _("Username") . ": " . InterfaceElements :: generateInputText("select_user_" . $user_prefix . "_username", "", "", "input_text", array (
                "onkeypress" => "if ((event.which ? event.which : event.keyCode) == 13) {form.$add_button_name.click() }"
            ));
            $_userSelector .= InterfaceElements :: generateInputSubmit($add_button_name, $add_button_value);
        } else {
            $_userSelector = _("Username") . ": " . InterfaceElements :: generateInputText("select_user_" . $user_prefix . "_username");
        }
        $_html = InterfaceElements :: generateDiv($_networkSelector . $_userSelector, 'user_select_user_ui_container');

        return $_html;
    }

    /** Get the selected user, IF one was selected and is valid
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @return the User object, or null if the user is invalid or none was selected
     */
    static function processSelectUserUI($user_prefix) {
        $object = null;
        try {
            $network = Network :: processSelectNetworkUI($user_prefix);
            $name = "select_user_{$user_prefix}_username";
            if (!empty ($_REQUEST[$name])) {
                $username = $_REQUEST[$name];
                return self :: getUserByUsernameAndOrigin($username, $network);
            } else
                return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getAdminUI() {
        $db = AbstractDb::getObject();
        $currentUser = self :: getCurrentUser();
        $html = '';
        $html .= "<fieldset class='admin_container " . get_class($this) . "'>\n";
        $html .= "<ul class='admin_element_list'>\n";
        if (($this == $currentUser && !$this->isSplashOnlyUser() )|| $this->getNetwork()->hasAdminAccess($currentUser)) {
            //username
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>" . _("Username") . " : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $name = "user_" . $this->getId() . "_username";
            $html .= "<input type='text' name='$name' value='" . htmlentities($this->getUsername()) . "' size=30>\n";
            $html .= _("Be carefull when changing this: it's the username you use to log in!");
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        /*
                $html .= "<li class='admin_element_item_container'>\n";
                $html .= "<div class='admin_element_label'>"._("Real name")." : </div>\n";
                $html .= "<div class='admin_element_data'>\n";
                $name = "user_".$this->getId()."_real_name";
                $html .= "<input type='text' name='$name' value='".htmlentities($this->getRealName())."' size=30 readonly>\n";
                $html .= "</div>\n";
                $html .= "</li>\n";
        
                $html .= "<li class='admin_element_item_container'>\n";
                $html .= "<div class='admin_element_label'>"._("Website URL")." : </div>\n";
                $html .= "<div class='admin_element_data'>\n";
                $name = "user_".$this->getId()."_website";
                $html .= "<input type='text' name='$name' value='".htmlentities($this->getWebsiteURL())."' size=30 readonly>\n";
                $html .= "</div>\n";
                $html .= "</li>\n";
        */
        $html .= "</fieldset>\n";
        return $html;
    }

    public function processAdminUI() {
        $db = AbstractDb::getObject();
        $currentUser = self :: getCurrentUser();
        if ($this == $currentUser || $this->getNetwork()->hasAdminAccess($currentUser)) {
            //username
            $name = "user_" . $this->getId() . "_username";
            $this->setUsername($_REQUEST[$name]);
        }
    }

    public function delete(& $errmsg) {
    }

    public function getUserUI() {
        $html = "";
        $html .= $this->getRealName();

        return $html;
    }

    /** Add content to this user ( subscription ) */
    public function addContent(Content $content) {
        $db = AbstractDb::getObject();
        $content_id = $db->escapeString($content->getId());
        $sql = "INSERT INTO user_has_content (user_id, content_id) VALUES ('$this->id','$content_id')";
        $db->execSqlUpdate($sql, false);
        return true;
    }

    /** Remove content from this node */
    public function removeContent(Content $content) {
        $db = AbstractDb::getObject();
        $content_id = $db->escapeString($content->getId());
        $sql = "DELETE FROM user_has_content WHERE user_id='$this->id' AND content_id='$content_id'";
        $db->execSqlUpdate($sql, false);
        return true;
    }

    /**Get an array of all Content linked to this node
    * @return an array of Content or an empty arrray */
    function getAllContent() {
        $db = AbstractDb::getObject();
        $retval = array ();
        $sql = "SELECT * FROM user_has_content WHERE user_id='$this->id' ORDER BY subscribe_timestamp";
        $db->execSql($sql, $content_rows, false);
        if ($content_rows != null) {
            foreach ($content_rows as $content_row) {
                $retval[] = Content :: getObject($content_row['content_id']);
            }
        }
        return $retval;
    }

    /** Reloads the object from the database.  Should normally be called after a set operation */
    protected function refresh() {
        $this->__construct($this->id);
    }

    /** Set Smarty template values.  Standardization routine. */
    public static function assignSmartyValues($smarty, $user = null) {
        if (!$user)
            $user = User :: getCurrentUser();
        $smarty->assign('username', $user ? $user->getListUI() : '');
        $smarty->assign('userId', $user ? $user->getId() : '');
        /**
         * Define user security levels for the template
         *
         * These values are used in the default template of WiFoDog but could be
         * used in a customized template to restrict certain links to specific
         * user access levels.
         */
        $smarty->assign('isValidUser', $user && !$user->isSplashOnlyUser() ? true : false);
        $smarty->assign('isSuperAdmin', $user && $user->isSuperAdmin());
        $smarty->assign('isOwner', $user && $user->isOwner());

        if (isset ($_REQUEST['debug_request']) && ($user && $user->isSuperAdmin())) {
            // Tell Smarty everything it needs to know
            $smarty->assign('debugRequested', true);
            $smarty->assign('debugOutput', print_r($_REQUEST, true));
        }
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */