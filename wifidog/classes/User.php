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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/Network.php');
require_once ('classes/InterfaceElements.php');
require_once ('classes/ProfileTemplate.php');
require_once ('classes/Profile.php');
require_once ('classes/Permission.php');
/**
 * Abstract a User
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class User implements GenericObject {
    private $_row;
    private $id;
    /** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();

    /** Instantiate a user object
     * @param $id The user id of the requested user
     * @return a User object, or null if there was an error
     */
    public static function &getObject($id) {
        if(!isset(self::$instanceArray[$id]))
        {
            self::$instanceArray[$id] = new self($id);
        }
        return self::$instanceArray[$id];
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
        $sessCurrentUserId = $session->get(SESS_USER_ID_VAR);
        $user = null;
        if(!empty($sessCurrentUserId)){
            try {
                $user = self :: getObject($sessCurrentUserId);
                //$user = new User($session->get(SESS_USER_ID_VAR));
            } catch (Exception $e) {
                /**If any problem occurs, the user should be considered logged out*/
                $session->set(SESS_USER_ID_VAR, null);
            }
        }
        return $user;
    }

    /**
     * Associates the user passed in parameter with the session
     *
     * This should NOT be called by anything except the Authenticators
     *
     * @param object $user User a user object, or null
     *
     * @return bool True if everything went well setting the session

     */
    public static function setCurrentUser($user) {

        if (get_class($user) == 'User'){
            $userId = $user->getId();
            $passwordHash = $user->getPasswordHash();
        }
        else {
            $userId = null;
            $passwordHash = null;
        }

        try {
            $session = Session::getObject();
            $session->set(SESS_USER_ID_VAR, $userId);
            $session->set(SESS_PASSWORD_HASH_VAR, $passwordHash);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /** Instantiate a user object
     * @param $username The username of the user
     * @param $account_origin Network:  The account origin
     * @param &$errMsg An error message will be appended to this if the username is not empty, but the user doesn't exist.
     * @return a User object, or null if there was an error
     */
    public static function getUserByUsernameAndOrigin($username, Network $account_origin, &$errMsg = null) {
        $db = AbstractDb::getObject();
        $object = null;

        $username_str = $db->escapeString($username);
        $comparison = ($account_origin->getUsernamesCaseSensitive()? '=': 'ILike');
        $account_origin_str = $db->escapeString($account_origin->getId());
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE username {$comparison} '$username_str' AND account_origin = '$account_origin_str'", $user_info, false);

        if ($user_info != null) {
            $object = self::getObject($user_info['user_id']);
        }
        else if (!empty($username)) {
            $errMsg .= sprintf(_("There is no user with username %s"),$username);
        }
        return $object;
    }
    
		/** Instantiate a user object
     * @param $username The username of the user
     * @param $account_origin Network:  The account origin
     * @param &$errMsg An error message will be appended to this if the username is not empty, but the user doesn't exist.
     * @return a User object, or null if there was an error
     */
    public static function getUserByUsernameOrEmailAndOrigin($usernameOrEmail, Network $account_origin, &$errMsg = null) {
        $db = AbstractDb::getObject();
        $object = null;

        $username_str = $db->escapeString($usernameOrEmail);
        $comparison = ($account_origin->getUsernamesCaseSensitive()? '=': 'ILike');
        $account_origin_str = $db->escapeString($account_origin->getId());
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE (username {$comparison} '$username_str' OR email ILike '$username_str') AND account_origin = '$account_origin_str'", $user_info, false);

        if ($user_info != null) {
            $object = self::getObject($user_info['user_id']);
        }
        else if (!empty($usernameOrEmail)) {
            $errMsg .= sprintf(_("There is no user with username or email %s"),$usernameOrEmail);
        }
        return $object;
    }

    /** Instantiate a user object
     * @param $usernameOrEmail The username or the email address of the user
     * @param &$errMsg An error message will be appended to this if the username is not empty, but the user doesn't exist.
     * @return a User object, or null if there was an error
     */
    public static function getUserByUsernameOrEmail($usernameOrEmail, &$errMsg = null) {
        $db = AbstractDb::getObject();
        $object = null;

        $usernameOrEmail_str = $db->escapeString($usernameOrEmail);
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE username = '$usernameOrEmail_str' OR email ILike '$usernameOrEmail_str'", $user_info, false);

        if ($user_info != null) {
            $object = self::getObject($user_info['user_id']);
        }
        else if (!empty($usernameOrEmail)) {
            $errMsg .= sprintf(_("There is no user with username or email %s"),$usernameOrEmail);
        }
        return $object;
    }
 

   /** Instantiate a user object
     * @param $url The OpenId url
     * @return a User object, or null if none matched
     */
    public static function getUserByOpenIdUrl($url) {
        $db = AbstractDb::getObject();
        $object = null;

        $url_str = $db->escapeString($url);
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE open_id_url = '$url_str'", $user_rows, false);

        if ($user_rows != null) {
            $object = self::getObject($user_rows[0]['user_id']);
        }
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
        $db->execSqlUniqueRes("SELECT user_id FROM users WHERE email ILike '$email_str' AND account_origin = '$account_origin_str'", $user_info, false);

        if ($user_info != null)
        $object = self::getObject($user_info['user_id']);
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

        $object = self::getObject($id);
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
        $this->_row = $row;
        $this->id = $row['user_id'];
    } //End class

    function getId() {
        return $this->id;
    }

    /** Gets the Network to which the user belongs
     * @return Network object (never returns null)
     */
    public function getNetwork() {
        return Network :: getObject($this->_row['account_origin']);
    }

    /** Get a user display suitable for a user list.  Will include link to the user profile. */
    function getListUI() {
        $html = '';
        if ($this->isSplashOnlyUser()) {
            $html .= _("Guest");
        }
        else {
            $nickname = null;
            $avatar = null;
            $profile = $this->getAllProfiles();
            if(!empty($profile)) {
                // Use the first profile for now
                $profile = $profile[0];

                $nickname_fields = $profile->getFieldsBySemanticId("foaf:nick");
                // Try using the first nickname available
                if(!empty($nickname_fields)) {
                    $nickname_content = $nickname_fields[0]->getContentField();
                    if(!empty($nickname_content)) {
                        // Force non-verbose output
                        $str = $nickname_content->__toString(false);
                        if(!empty($str))
                        $nickname = $str;
                    }
                }

                $avatar_fields = $profile->getFieldsBySemanticId("foaf:img");
                // Try using the first avatar available
                if(!empty($avatar_fields)) {
                    $avatar_content = $avatar_fields[0]->getContentField();
                    if(!empty($avatar_content)) {
                        $avatar = $avatar_content->getUserUI();
                    }
                }
            }



            // Display the avatar
            if(empty($avatar))
            $html .= Avatar::getDefaultUserUI();
            else
            $html .= $avatar;

            // Display the nickname or the username
            $profiles=$this->getAllProfiles();
            if($profiles){
                $html .= "<a href='".BASE_URL_PATH."profile/?user_id=".$this->getId()."' title='".htmlentities(_("View this user's profile."), ENT_QUOTES)."' class='user_nickname'>";
            }
            if(empty($nickname))
            $html .= $this->getUserName();
            else
            $html .= $nickname;
            if($profiles){
                $html .= "</a>\n";
            }
            $profileTemplates = $this->getNetwork()->getAllProfileTemplates();
            /*if($this==User::getCurrentUser() && $profileTemplates) {
             $html .= "<div class='user_edit_profile_link'><br/>(<a href='".BASE_SSL_PATH."admin/generic_object_admin.php?object_id=".$this->getId()."&object_class=User&action=edit'>"._("edit profile")."</a>)</div>";
             }*/

        }
        return $html;
    }

    function getOpenIdUrl() {
        return $this->_row['open_id_url'];
    }

    function getUsername() {
        return $this->_row['username'];
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
            $otherUser = User::getUserByUsernameAndOrigin($value, $this->getNetwork());
            if (!is_null($otherUser)) {
                throw new exception(sprintf(_("Sorry, the username %s is not available"), $value));
            }
            $value = $db->escapeString($value);
            $retval = @ $db->execSqlUpdate("UPDATE users SET username = '{$value}' WHERE user_id='{$this->id}'", false);
            $this->refresh();
        }
        return $retval;
    }

    /** Add profile template to this user */
    public function addProfile(Profile $profile) {
        $db = AbstractDb::getObject();
        $profile_id = $db->escapeString($profile->getId());
        $sql = "INSERT INTO user_has_profiles (user_id, profile_id) VALUES ('$this->id','$profile_id')";
        return $db->execSqlUpdate($sql, false);
    }

    /** Remove profile template from this user */
    public function removeProfile(Profile $profile) {
        $db = AbstractDb::getObject();
        $profile_id = $db->escapeString($profile->getId());
        $sql = "DELETE FROM user_has_profiles WHERE user_id='$this->id' AND profile_id='$profile_id'";
        return $db->execSqlUpdate($sql, false);
    }

    /**Get an array of all Profiles linked to this user
     * @return an array of Profile or an empty arrray */
    public function getAllProfiles() {
        $db = AbstractDb::getObject();
        $retval = array ();
        $profile_rows = null;
        $sql = "SELECT profile_id FROM user_has_profiles NATURAL JOIN profiles WHERE user_id='$this->id' ORDER BY creation_date";
        $db->execSql($sql, $profile_rows, false);
        if ($profile_rows != null) {
            foreach ($profile_rows as $profile_row) {
                $retval[] = Profile :: getObject($profile_row['profile_id']);
            }
        }
        return $retval;
    }

    public function getEmail() {
        return $this->_row['email'];
    }

    public function setEmail($email) {
        $email_str = $this->mDb->escapeString($email);
        if (!($update = $this->mDb->execSqlUpdate("UPDATE users SET email='{$email_str}' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not update email address."));
        }
        $this->_row['email'] = $email; // unescaped
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
        return (($this->_row['is_invisible'] == 't') ? true : false);
    }

    /**What locale (language) does the user prefer? */
    public function getPreferedLocale() {
        $session = Session::getObject();
        $locale = $this->_row['prefered_locale'];
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
        $this->_row['prefered_locale'] = $locale;
    }

    /** get the hashed password stored in the database */
    public function getPasswordHash() {
        return $this->_row['pass'];
    }

    /** Get the account status.
     * @return Possible values are listed in common.php
     */
    function getAccountStatus() {
        return $this->_row['account_status'];
    }

    function setAccountStatus($status) {
        $db = AbstractDb::getObject();
        if($status != $this->getAccountStatus()) {
            $status_str = $db->escapeString($status);
            if (!($update = $db->execSqlUpdate("UPDATE users SET account_status='{$status_str}' WHERE user_id='{$this->id}'"))) {
                throw new Exception(_("Could not update status."));
            }
            $this->_row['account_status'] = $status;
        }
    }

    /** Is the user valid?  Valid means that the account is validated or hasn't exhausted it's validation period.
     $errmsg: Returs the reason why the account is or isn't valid */
    function isUserValid(& $errmsg = null) {
        global $account_status_to_text;
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
                $errmsg = sprintf(_("Sorry, your %.0f minutes grace period to retrieve your email and validate your account has now expired. You will have to connect to the internet and validate your account from another location."), $user_info['validation_grace_time']/60);
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

    public function DEPRECATEDisSuperAdmin() {
        $db = AbstractDb::getObject();
        //$this->session->dump();

        $db->execSqlUniqueRes("SELECT * FROM users JOIN server_stakeholders USING (user_id) WHERE (users.user_id='$this->id')", $user_info, false);
        if (!empty ($user_info)) {
            return true;
        } else {
            return false;
        }

    }

    /** Is this user the Splash Only User() */
    public function isSplashOnlyUser() {
        if ($this->_row['username'] == "SPLASH_ONLY_USER") {
            return true;
        } else {
            return false;
        }
    }

    function getValidationToken() {
        return $this->_row['validation_token'];
    }

    /** Retrieves the connection history necessary for abuse control

    * @return false if abuse control is disabled */

    static function getAbuseControlConnectionHistory($user = null, $mac = null, $node = null) {
        if (!$user) {
            $user = User::getCurrentUser();
        }
        if (!$node) {
            $node = Node::getCurrentNode();//Maybe this should be getCurrentRealNode, but it would make debuging harder
        }
        $network = $node->getNetwork();

        $db = AbstractDb::getObject();

        if ($network->getConnectionLimitWindow()) {
            //$sql =  " SELECT * from connections \n";//For debugging
            $sql =  " SELECT \n";
            $sql .= " SUM (incoming+outgoing) AS network_total_bytes, \n";
            $sql .= " SUM (CASE WHEN node_id = '".$node->getId()."' THEN (incoming+outgoing) END) AS node_total_bytes, \n";
            $sql .= " SUM (COALESCE(timestamp_out,last_updated) - timestamp_in) AS network_duration, \n";
            $sql .= " SUM (CASE WHEN node_id = '".$node->getId()."' THEN (COALESCE(timestamp_out,last_updated) - timestamp_in) END) AS node_duration \n";//For real //The coalesce is to make sure the substraction returns a value for active conections, since active connections do not yet have a timestamp_out.  Do NOT coalesce with CURRENT_TIMESTAMP, it could cause real problems for users in case of gateway crash.
            $sql .= " FROM connections \n";//For real
            $sql .= " JOIN nodes USING (node_id) \n";
            $sql .= " JOIN networks USING (network_id) \n";
            $sql .= " JOIN tokens ON (tokens.token_id = connections.token_id) \n";
            $sql .= " WHERE 1=1 \n";

            if ($mac) {
                //Catch some cheaters
                $mac = $db->escapeString($mac);
                $mac_sql_or = " OR connections.user_mac = '$mac' ";
            }
            else {
                $mac_sql_or = null;
            }
            $sql .= " AND (connections.user_id = '".$user->getId()."' $mac_sql_or ) \n";

            $sql .= " AND (timestamp_in > CURRENT_TIMESTAMP - networks.connection_limit_window OR tokens.token_status = '".TOKEN_INUSE."')";  //Get every connection within the window plus any still active connection, even if it started before the window

            $subselect = $sql;
            $sql =  " SELECT subselect.*, \n";
            $sql .= " networks.connection_limit_window, \n";
            $sql .= " networks.connection_limit_network_max_total_bytes, COALESCE(network_total_bytes>networks.connection_limit_network_max_total_bytes, false) AS network_total_bytes_exceeded_limit, \n";
            $sql .= " networks.connection_limit_node_max_total_bytes, COALESCE(node_total_bytes>networks.connection_limit_node_max_total_bytes, false) AS node_total_bytes_exceeded_limit, \n";
            $sql .= " networks.connection_limit_network_max_usage_duration, COALESCE(network_duration>networks.connection_limit_network_max_usage_duration, false) AS network_duration_exceeded_limit, \n";
            $sql .= " networks.connection_limit_node_max_usage_duration, COALESCE(node_duration>networks.connection_limit_node_max_usage_duration, false) AS node_duration_exceeded_limit \n";

            $sql .= " FROM ($subselect) AS subselect JOIN networks ON (network_id = '".$network->getId()."')";

            $db->execSqlUniqueRes($sql, $connection_limits_report, false);
            return $connection_limits_report;
        }
        else {
            return false;
        }
    }

    /** Takes the same paramaters as getAbuseControlConnectionHistory, and tells you if the abuse limits are busted

    * @return false if abuse control respected, else a string containing the reason(s) for the bust  */

    static function isAbuseControlViolated($user = null, $mac = null, $node = null) {
        $retval = false;
        $abuseControlReport = self::getAbuseControlConnectionHistory($user, $mac, $node);
        if($abuseControlReport) {
            if (!$user) {
                $user = User::getCurrentUser();
            }
            //pretty_print_r($abuseControlReport);
            if($node && Security::hasPermission(Permission::P('NODE_PERM_BYPASS_DYNAMIC_ABUSE_CONTROL'), $node, $user)) {
                $retval = false;
            }
            else {
                require_once('classes/Content/UIAllowedBandwidth/UIAllowedBandwidth.php');
                if($abuseControlReport['network_total_bytes_exceeded_limit']=='t') {
                    $retval .= sprintf(_("During the last %s period, you transfered %s bytes throughout the network, which exceeds the %s bytes limit for the entire network."), $abuseControlReport['connection_limit_window'], UIAllowedBandwidth::formatSize($abuseControlReport['network_total_bytes']), UIAllowedBandwidth::formatSize($abuseControlReport['connection_limit_network_max_total_bytes']));
                }
                if($abuseControlReport['node_total_bytes_exceeded_limit']=='t') {
                    $retval .= sprintf(_("During the last %s period, you transfered %s bytes at this node, which exceeds the %s bytes limit for this node."), $abuseControlReport['connection_limit_window'], UIAllowedBandwidth::formatSize($abuseControlReport['node_total_bytes']), UIAllowedBandwidth::formatSize($abuseControlReport['connection_limit_node_max_total_bytes']));
                }
                if($abuseControlReport['network_duration_exceeded_limit']=='t') {
                    $retval .= sprintf(_("During the last %s period, you were online for a duration of %s throughout the network, which exceeds the %s limit for the entire network."), $abuseControlReport['connection_limit_window'], $abuseControlReport['network_duration'], $abuseControlReport['connection_limit_network_max_usage_duration']);
                }
                if($abuseControlReport['node_duration_exceeded_limit']=='t') {
                    $retval .= sprintf(_("During the last %s period, you were online for a duration of %s at this node, which exceeds the %s limit for this node."), $abuseControlReport['connection_limit_window'], $abuseControlReport['node_duration'], $abuseControlReport['connection_limit_node_max_usage_duration']);
                }
            }
        }
        return $retval;
    }
    /** Generate a token in the connection table so the user can actually use the internet
    @return true on success, false on failure
    */
    function generateConnectionToken($mac = null) {
        if ($this->isUserValid()) {
            $db = AbstractDb::getObject();
            $session = Session::getObject();

            $token = self :: generateToken();
            if ($_SERVER['REMOTE_ADDR']) {
                $node_ip = $db->escapeString($_SERVER['REMOTE_ADDR']);
            }
            
            if ($session && $node_ip && $session->get(SESS_NODE_ID_VAR)) {
                //echo "$session && $node_ip && {$session->get(SESS_NODE_ID_VAR)}";
                $node_id = $db->escapeString($session->get(SESS_NODE_ID_VAR));
                $abuseControlFault = User::isAbuseControlViolated($this, $mac, Node::getObject($node_id));
                if($abuseControlFault) {
                    throw new Exception ($abuseControlFault);
                }
                $mac = (is_null($mac)?'': $db->escapeString($mac));
                /*
                 * Delete all unused tokens for this user, so we don't fill the database
                 * with them
                 */
                $sql = "DELETE FROM connections USING tokens "."WHERE tokens.token_id=connections.token_id AND token_status='".TOKEN_UNUSED."' AND user_id = '".$this->getId()."';\n";
                // TODO:  Try to find a reusable token before creating a brand new one!

                $sql .= "INSERT INTO tokens (token_owner, token_issuer, token_id, token_status) VALUES ('" . $this->getId() . "', '" . $this->getId() . "', '$token', '" . TOKEN_UNUSED . "');\n";
                $sql .= "INSERT INTO connections (user_id, token_id, timestamp_in, node_id, node_ip, last_updated, user_mac) VALUES ('" . $this->getId() . "', '$token', CURRENT_TIMESTAMP, '$node_id', '$node_ip', CURRENT_TIMESTAMP, '$mac')";
                $db->execSqlUpdate($sql, false);
                $retval = $token;
            }
            else {
                $retval = false;
            }
        }
        else {
            $retval = false;
        }
        return $retval;
    }
    
    /** Generate a token in the connection table so the user can actually use the internet
    @return true on success, false on failure
    */
    function generateConnectionTokenNoSession($node, $node_ip = null, $mac = null ) {
        if ($this->isUserValid()) {
            $db = AbstractDb::getObject();
            
            $token = self :: generateToken();
            if ($node_ip && $node) {
                //echo "$session && $node_ip && {$session->get(SESS_NODE_ID_VAR)}";
                $node_id = $node->getId();
                $abuseControlFault = User::isAbuseControlViolated($this, $mac, $node);
                if($abuseControlFault) {
                    throw new Exception ($abuseControlFault);
                }
                $mac = (is_null($mac)?'': $db->escapeString($mac));
                /*
                 * Delete all unused tokens for this user, so we don't fill the database
                 * with them
                 */
                $sql = "DELETE FROM connections USING tokens "."WHERE tokens.token_id=connections.token_id AND token_status='".TOKEN_UNUSED."' AND user_id = '".$this->getId()."';\n";
                // TODO:  Try to find a reusable token before creating a brand new one!

                $sql .= "INSERT INTO tokens (token_owner, token_issuer, token_id, token_status) VALUES ('" . $this->getId() . "', '" . $this->getId() . "', '$token', '" . TOKEN_UNUSED . "');\n";
                $sql .= "INSERT INTO connections (user_id, token_id, timestamp_in, node_id, node_ip, last_updated, user_mac) VALUES ('" . $this->getId() . "', '$token', CURRENT_TIMESTAMP, '$node_id', '$node_ip', CURRENT_TIMESTAMP, '$mac')";
                $db->execSqlUpdate($sql, false);
                $retval = $token;
            }
            else {
                $retval = false;
            }
        }
        else {
            $retval = false;
        }
        return $retval;
    }

    function setPassword($password) {
        $db = AbstractDb::getObject();

        $new_password_hash = User :: passwordHash($password);
        if (empty($password)) {
            throw new Exception(_("Password cannot be empty."));
        }

        if (!($update = $db->execSqlUpdate("UPDATE users SET pass='$new_password_hash' WHERE user_id='{$this->id}'"))) {
            throw new Exception(_("Could not change user's password."));
        }
        $this->_row['pass'] = $password;
    }

    function getAccountOrigin() {
        return $this->_row['account_origin'];
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
        require_once ('classes/Mail.php');
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
                require_once ('classes/Mail.php');
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
        require_once ('classes/Mail.php');
        $mail = new Mail();
        $mail->setSenderName(_("Registration system"));
        $mail->setSenderEmail($network->getValidationEmailFromAddress());
        $mail->setRecipientEmail($this->getEmail());
        $mail->setMessageSubject($network->getName() . _(" new password request"));
        $mail->setMessageBody(_("Hello,\nYou have requested that the authentication server send you a new password:\nUsername: ") . $this->getUsername() . _("\nPassword: ") . $new_password . _("\n\nHave a nice day,\nThe Team"));
        $mail->send();
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
        $networkSelector = Network :: getSelectUI($user_prefix);
        // Check if we need to add an "add" button
        if ($add_button_name && $add_button_value) {
            $userSelector = _("Username") . ": " . InterfaceElements :: generateInputText("select_user_" . $user_prefix . "_username", "", "", "input_text", array (
            "onkeypress" => "if ((event.which ? event.which : event.keyCode) == 13) {form.$add_button_name.click() }"
            ));
            $userSelector .= InterfaceElements :: generateInputSubmit($add_button_name, $add_button_value);
        } else {
            $userSelector = _("Search for Username or Email Address") . ": " . InterfaceElements :: generateInputText("select_user_" . $user_prefix . "_username");
        }
        $html = "<div class='user_select_user_ui_container'>".$networkSelector . "<br>" . $userSelector . "</div>\n";
        return $html;
    }

    /** Get the selected user, IF one was selected and is valid
     * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
     * @param &$errMsg An error message will be appended to this is the username is not empty, but the user doesn't exist.
     * @return the User object, or null if the user is invalid or none was selected
     */
    static function processSelectUserUI($user_prefix, &$errMsg) {
        $object = null;
        try {
            $network = Network :: processSelectUI($user_prefix);
            $name = "select_user_{$user_prefix}_username";
            if (!empty ($_REQUEST[$name])) {
                $username = $_REQUEST[$name];
                return self :: getUserByUsernameOrEmailAndOrigin($username, $network, $errMsg);
            } else
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getAdminUI() {
        $db = AbstractDb::getObject();
        $currentUser = self :: getCurrentUser();
        $userPreferencesItems = array();
        $finalHtml = '';
        if(Security::hasPermission(Permission::P('NETWORK_PERM_VIEW_STATISTICS'), $this->getNetwork())) {
            /* Statistics */
            $content = "<a href='".BASE_SSL_PATH."admin/stats.php?Statistics=".$this->getNetwork()->getId()."&distinguish_users_by=user_id&stats_selected_users=".$this->getUsername()."&UserReport=on&user_id=".$this->getId()."&action=generate'>"._("Get user statistics")."</a>\n";
            $administrationItems[] = InterfaceElements::genSectionItem($content);

            /* Account status */
            $title = _("Account Status");
            $help = _("Note that Error is for internal use only");
            $name = "user_" . $this->getId() . "_accountstatus";
            global $account_status_to_text;
            $content = FormSelectGenerator::generateFromKeyLabelArray($account_status_to_text, $this->getAccountStatus(), $name, null, false);
            $administrationItems[] = InterfaceElements::genSectionItem($content, $title, $help);

            $finalHtml .= InterfaceElements::genSection($administrationItems, _("Administrative options"));
        }

        if (($this == $currentUser && !$this->isSplashOnlyUser() )|| Security::hasPermission(Permission::P('NETWORK_PERM_EDIT_ANY_USER'), $this->getNetwork())) {
            /* Username */
            $title = _("Username");
            $name = "user_" . $this->getId() . "_username";
            $content = "<input type='text' name='$name' value='" . htmlentities($this->getUsername()) . "' size=30><br/>\n";
            $content .= _("Be careful when changing this: it's the username you use to log in!");
            $userPreferencesItems[] = InterfaceElements::genSectionItem($content, $title);


	    /* Email */
            $title = _("Email");
            $name = "email_" . $this->getId() . "_email";
            $content = "<input type='text' name='$name' disabled='disabled' value='" . htmlentities($this->getEmail()) . "' size=30><br/>\n";
            $content .= _("If you wish to change this address, please Email Support!");
            $userPreferencesItems[] = InterfaceElements::genSectionItem($content, $title);



            /* Change password */
            $changePasswordItems=array();
            if($this == $currentUser) {//Don't enter the old password if changing password for another user
                $title = _("Your current password");
                $name = "user_" . $this->getId() . "_oldpassword";
                $content = "<input type='password' name='$name' size='20'>\n";
                $changePasswordItems[] = InterfaceElements::genSectionItem($content, $title);
            }

            $title = _("Your new password");
            $name = "user_" . $this->getId() . "_newpassword";
            $content = "<input type='password' name='$name' size='20'>\n";
            $changePasswordItems[] = InterfaceElements::genSectionItem($content, $title);

            $title = _("Your new password (again)");
            $name = "user_" . $this->getId() . "_newpassword_again";
            $content = "<input type='password' name='$name' size='20'>\n";
            $changePasswordItems[] = InterfaceElements::genSectionItem($content, $title);

            $userPreferencesItems[] = InterfaceElements::genSection($changePasswordItems, _("Change my password"));

            $finalHtml .= InterfaceElements::genSection($userPreferencesItems, _("User preferences"), false, false, get_class($this));

            //N.B: For now, let pretend we have only one profile per use...
            $profiles = $this->getAllProfiles();
            $current_profile = null;
            if(!empty($profiles)) {
                $current_profile = $profiles[0];
            }

            if($current_profile != null) {
                $finalHtml .= $current_profile->getAdminUI();
                $name = "user_" . $this->getId() . "_delete_profile_".$current_profile->getId();
                $value = _("Completely delete my public profile");
                $finalHtml .= "<div class='admin_element_tools'>";
                $finalHtml .= '<input type="submit" class="submit" name="' . $name . '" value="' . $value . '">';
                $finalHtml .= "</div>";
            }
            else {                    // Get the list of profile templates for the users' network
                $profile_templates = ProfileTemplate::getAllProfileTemplates($this->getNetwork());
                if(!empty($profile_templates)) {
                    $name = "user_" . $this->getId() . "_add_profile";
                    $value = _("Create my public profile");
                    $finalHtml .= "<div class='admin_element_tools'>";
                    $finalHtml .= '<input type="submit" class="submit" name="' . $name . '" value="' . $value . '">';
                    $finalHtml .= "</div>";
                }
            }
        }

        return $finalHtml;
    }

    public function processAdminUI() {
        $db = AbstractDb::getObject();
        $currentUser = self :: getCurrentUser();
        if (Security::hasPermission(Permission::P('NETWORK_PERM_EDIT_ANY_USER'), $this->getNetwork())) {
            /* Account status */
            $name = "user_" . $this->getId() . "_accountstatus";
            $status = FormSelectGenerator::getResult($name, null);
            $this->setAccountStatus($status);
        }

        if ($this == $currentUser || Security::requirePermission(Permission::P('NETWORK_PERM_EDIT_ANY_USER'), $this->getNetwork())) {
            /* Username */
            $name = "user_" . $this->getId() . "_username";
            $this->setUsername($_REQUEST[$name]);

            /* Change password */
            $nameOldpassword = "user_" . $this->getId() . "_oldpassword";
            $nameNewpassword = "user_" . $this->getId() . "_newpassword";
            $nameNewpasswordAgain = "user_" . $this->getId() . "_newpassword_again";
            if($_REQUEST[$nameNewpassword]!=null){
                if ($this == $currentUser && $this->getPasswordHash() != User::passwordHash($_REQUEST[$nameOldpassword])) {
                    throw new Exception(_("Wrong password."));
                }
                if ($_REQUEST[$nameNewpassword] != $_REQUEST[$nameNewpasswordAgain]){
                    throw new Exception(_("Passwords do not match."));
                }
                $this->setPassword($_REQUEST[$nameNewpassword]);
            }

            // Pretend there is only one
            $profiles = $this->getAllProfiles();
            if(!empty($profiles)) {
                $current_profile = $profiles[0];
                if($current_profile != null) {
                    $current_profile->processAdminUI();
                    $name = "user_" . $this->getId() . "_delete_profile_".$current_profile->getId();
                    if(!empty($_REQUEST[$name])) {
                        $errmsg=null;
                        $current_profile->delete($errmsg);
                    }
                }
            }
            else {
                $name = "user_" . $this->getId() . "_add_profile";
                if(!empty($_REQUEST[$name])) {
                    // Get the list of profile templates for the users' network
                    $profile_templates = ProfileTemplate::getAllProfileTemplates($this->getNetwork());
                    if(!empty($profile_templates)) {
                        // Create a blank profile and link it to the user
                        $current_profile = Profile::createNewObject(null, $profile_templates[0]);
                        $this->addProfile($current_profile);
                    }
                }

            }

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
        $content_rows = null;
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
    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $network = Network::getCurrentNetwork();
        $server = Server::getServer();
        if(Security::hasAnyPermission(array(array(Permission::P('NETWORK_PERM_VIEW_ONLINE_USERS'), $network))))
        {
            $items[] = array('path' => 'users/online_users',
            'title' => _("Online Users"),
            'url' => BASE_URL_PATH."admin/online_users.php");
        }
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_SERVER_CONFIG'), $server))
        {
            $items[] = array('path' => 'users/import_nocat',
            'title' => _("Import NoCat user database"),
            'url' => BASE_URL_PATH."admin/import_user_database.php"
            );
        }
        if(Security::getObjectsWithPermission(Permission::P('NETWORK_PERM_EDIT_ANY_USER')))
        {
            $items[] = array('path' => 'users/user_manager',
            'title' => _("User manager"),
            'url' => BASE_URL_PATH."admin/user_log.php"
            );
        }
        if(Security::getObjectsWithPermission(Permission::P('NETWORK_PERM_VIEW_STATISTICS')))
        {
            $items[] = array('path' => 'users/statistics',
            'title' => _("Statistics"),
            'url' => BASE_URL_PATH."admin/stats.php"
            );
        }
        $items[] = array('path' => 'users',
        'title' => _('User administration'),
        'type' => MENU_ITEM_GROUPING);
        return $items;
    }

    /** Set Smarty template values.  Standardization routine. */
    public static function assignSmartyValues($smarty, $user = null) {
        if (!$user)
        $user = User :: getCurrentUser();
        $session = Session :: getObject();
        $smarty->assign('userOriginallyRequestedURL', $session ? $session->get(SESS_ORIGINAL_URL_VAR) : '');
        $smarty->assign('userId', $user ? $user->getId() : '');
        $smarty->assign('userName', $user ? $user->getUsername() : '');
        /**
         * Define user security levels for the template
         *
         * These values are used in the default template of WiFoDog but could be
         * used in a customized template to restrict certain links to specific
         * user access levels.  Note however that they will all be deprecateb by the
         * new roles system.
         */
        $smarty->assign('userIsValid', $user && !$user->isSplashOnlyUser() ? true : false);
        $smarty->assign('userDEPRECATEDisSuperAdmin', $user && $user->DEPRECATEDisSuperAdmin());

        if (isset ($_REQUEST['debug_request']) && ($user && $user->DEPRECATEDisSuperAdmin())) {
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
