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
 * @subpackage Authenticators
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load include files
 */
require_once('classes/Authenticator.php');
require_once('classes/Security.php');
require_once('classes/User.php');

/**
 * Internal wifidog user database authentication source
 *
 * @package    WiFiDogAuthServer
 * @subpackage Authenticators
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class AuthenticatorLocalUser extends Authenticator
{

    /**
     * Constructor
     *
     * @param string $account_orgin Id of origin network
     *
     * @return void
     */
    public function __construct($account_orgin)
    {
        // Call parent constructor
        parent::__construct($account_orgin);
    }

    /**
     * Callback function used to discriminate Local User account origins
     *
     * @param array $account_origin Id of origin network
     *
     * @return bool True if the parameter refers to a Local User account origin

     */
    private static function isLocalUserAccountOrigin($account_origin)
    {
        return get_class($account_origin['authenticator']) == "AuthenticatorLocalUser";
    }

    /**
     * Attempts to login a user against the authentication source
     *
     * If successfull, returns a User object
     *
     * @param string $username A valid identifying token for the source. Not
     *                         necessarily unique. For local user, bots username
     *                         and email are valid.
     * @param string $password Clear text password.
     * @param string $errmsg   Reference of error message
     * @param int $errno       Reference to error code
     *
     * @return object The actual User object if login was successfull, false
     *                otherwise.
     */
    public function login($username, $password, &$errmsg = null, &$errno = 0)
    {
        //echo "DEBUG:  login($username, $password, $errmsg)<br/>";
        $db = AbstractDb::getObject();

        // Init values
        $retval = false;

        $username = $db->escapeString($username);
        if (empty($username)) {
            $errmsg .= sprintf(getErrorText(ERR_NO_USERNAME));
            $errno = ERR_NO_USERNAME;
            $retval = false;
        }
        else{
            /* gbastien: this is not reusable!!, why not use password directly? */
            //$password_hash = User::passwordHash($_REQUEST['password']);
            $password_hash = User::passwordHash($password);
            $password = $db->escapeString($password);
            
            $username = ($this->getNetwork()->getUsernamesCaseSensitive()? $username: strtolower($username));
            $compareto = ($this->getNetwork()->getUsernamesCaseSensitive()? 'username' : 'lower(username)');
            $sql = "SELECT user_id FROM users WHERE ($compareto = '$username' OR lower(email) = '$username') AND account_origin='".$this->getNetwork()->getId()."' AND pass='$password_hash'";
            $db->execSqlUniqueRes($sql, $user_info, false);

            if ($user_info != null) {
                $user = User::getObject($user_info['user_id']);

                if ($user->isUserValid($errmsg, $errno)) {
                    $retval = &$user;
                    $errmsg = _("Login successfull");
                } else {
                    $retval = false;
                    //Reason for refusal is already in $errmsg
                }
            } else {
                /*
                 * This is only used to discriminate if the problem was a
                 * non-existent user or a wrong password.
                 */
                $user_info = null;
                $db->execSqlUniqueRes("SELECT * FROM users WHERE ($compareto = '$username' OR lower(email) = '$username') AND account_origin='".$this->getNetwork()->getId()."'", $user_info, false);

                if ($user_info == null) {
                    $errmsg = getErrorText(ERR_UNKNOWN_USERNAME);
                    $errno = ERR_UNKNOWN_USERNAME;
                } else {
                    $errmsg = getErrorText(ERR_WRONG_PASSWORD);
                    $errno = ERR_WRONG_PASSWORD;
                }

                $retval = false;
            }
        }
        User::setCurrentUser($retval);
        return $retval;
    }

    /**
     * Start accounting traffic for the user
     *
     * @param string $conn_id The connection id for the connection to work on
     * @param string $errmsg  Reference of error message
     *
     * @return bool Returns always true
     */
    public function acctStart($conn_id, &$errmsg = null)
    {
        // Call parent method
        parent::acctStart($conn_id);

        return true;
    }

    /**
     * Update traffic counters
     *
     * @param string $conn_id  The connection id for the connection to work on
     * @param int    $incoming Incoming traffic in bytes
     * @param int    $outgoing Outgoing traffic in bytes
     * @param string $errmsg   Reference of error message
     *
     * @return bool Returns always true
     */
    public function acctUpdate($conn_id, $incoming, $outgoing, &$errmsg = null)
    {
        // Call parent method
        parent::acctUpdate($conn_id, $incoming, $outgoing);

        return true;
    }

    /**
     * Final update and stop accounting
     *
     * @param string $conn_id The connection id (the token id) for the
     *                        connection to work on
     * @param string $errmsg  Reference of error message
     *
     * @return bool Returns always true
     */
    public function acctStop($conn_id, &$errmsg = null)
    {
        // Call parent method
        parent::acctStop($conn_id);

        return true;
    }

    /**
     * If the authenticator allows new users to register new account, this must return the URL at which they must do so.
     *
     * @return text The URL to register at or NULL.  NULL means that the Authenticator does not allow new users to self-register.
     */
    public function getSignupUrl()
    {
        return BASE_NON_SSL_PATH.'signup.php';
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

