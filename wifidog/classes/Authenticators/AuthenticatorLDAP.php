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
 * @author     Ricardo Jose Guevara Ochoa <rjguevara@gmail.com>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Ricardo Jose Guevara Ochoa
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: AuthenticatorLocalUser.php 915 2006-01-23 05:26:20Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * Load include files
 */
require_once('classes/Authenticator.php');
require_once('classes/Dependency.php');
require_once('classes/Security.php');
require_once('classes/User.php');

/**
 * Internal wifidog user database authentication source using LDAP
 *
 * @package    WiFiDogAuthServer
 * @subpackage Authenticators
 * @author     Ricardo Jose Guevara Ochoa <rjguevara@gmail.com>
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Ricardo Jose Guevara Ochoa
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class AuthenticatorLDAP extends Authenticator
{
    /**
     * Hostname of the LDAP  server
     *
     * @var string

     */
    private $mldap_hostname;

    /**
     * The Relative Distinguished Name of the LDAP server
     *
     * @var string

     */
    private $mldap_rdn;

    /**
     * The password of the LDAP server
     *
     * @var string

     */
    private $mldap_pass;

    /**
     * The base dn of the server
     *
     * @var string

     */
    private $mldap_o;

    /**
     * It's the field that will be used in the LDAP search, i.e.: uid, mail,
     * name server
     *
     * @var string

     */
    private $mldap_filter;

    /**
     * AuthenticatorLDAP constructor
     *
     * Example: new AuthenticatorLDAP(IDRC_ACCOUNT_ORIGIN, '192.168.0.11',
     * 'company.com', 'password', 'mail');
     *
     * @param string $account_orgin The network ID
     * @param string $host          Hostname of the LDAP  server
     * @param string $rdn           The Relative Distinguished Name of the LDAP
     *                              server
     * @param string $rdn           The Relative Distinguished Name of the LDAP
     *                              server
     * @param string $pass          The password of the LDAP server
     * @param string $o             The base dn of the LDAP server
     * @param string $filter        It's the field that will be used in the
     *                              LDAP search, i.e.: uid, mail, name server
     *
     * @return void
     */
	public function __construct($account_orgin, $host, $rdn, $pass, $o, $filter)
    {
        // Call parent constructor
        parent::__construct($account_orgin);

		$this->mldap_hostname = $host;
		$this->mldap_filter = $filter;
		$this->mldap_o = $o;
		$this->mldap_rdn = trim($rdn);
		$this->mldap_pass = trim($pass);
    }

    /**
     * Callback function used to LDAP accounts
     *
     * @param string $username    Username of user
     * @param string $password    Clear text password of user
     * @param string $ldap_server Hostname of LDAP server
     * @param strong $o           The base dn of the LDAP server
     * @param string $f           It's the field that will be used in the
     *                            LDAP search, i.e.: uid, mail, name server
     * @param string $errmsg      Reference of error message
     *
     * @return bool True if the parameter refers to a Local User account origin

     */
	private function checkLdapUser($username, $password, $ldap_server, $o, $f, &$errmsg = null )
	{
	    // Init values
		$rtval = true;

		// Check if php-ldap extension is loaded
		if (Dependency::check("ldap", $errmsg)) {
    		if ($connect = @ldap_connect($ldap_server)) {
    		    // if connected to ldap server
    			ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);

    			// bind to ldap connection
                if (strlen(trim($this->mldap_rdn)) == 0) {
    				if (($bind = @ldap_bind($connect)) == false) {
    					$errmsg = _("Error while connecting to the LDAP server.");
    					return false;
    				}
    			} else {
    				if (($bind = @ldap_bind($connect, $this->mldap_rdn, $this->mldap_pass )) == false) {
    					$errmsg = _("Error while connecting to the LDAP server.");
    					return false;
    				}
    			}

    			// search for user
    			if (($res_id = ldap_search($connect, "o=$o", "$f=$username")) == false)  {
    				$errmsg = _("Error while obtaining your LDAP information.");

    				return false;
    			}

    			if (ldap_count_entries($connect, $res_id) != 1) {
    				$errmsg = _("Error while obtaining your username or password from the LDAP server.");

    				return false;
    			}

    			if (($entry_id = ldap_first_entry($connect, $res_id)) == false) {
    				$errmsg = _("Error while obtaining your username or password from the LDAP server.");

    				return false;
    			}

    			if (($user_dn = ldap_get_dn($connect, $entry_id)) == false) {
    				$errmsg = _("Error while obtaining your username or password from the LDAP server.");

    				return false;
    			}

    			//Authenticate the User
    			if (($link_id = ldap_bind($connect, $user_dn, $password)) == false) {
    				$errmsg = _("Error in username or password.");

    				return false;
    			}

    			return true;
    		} else {
    			$errmsg = _("Error connecting to the LDAP Server.");
    		}

    		ldap_close($connect);
		} else {
		    $rtval = false;
		}
	}

    /**
     * Attempts to login a user against the authentication source
     *
     * If successfull, returns a User object
     *
     * @param string $username A valid identifying token for the source. Not
     *                         necessarily unique.
     * @param string $password Clear text password.
     * @param string $errmsg   Reference of error message
     *
     * @return object The actual User object if login was successfull, false
     *                otherwise.
     */
	public function login($username, $password, &$errmsg = null)
	{
	    
		$db = AbstractDb::getObject();

		// Init values
		$retval = false;
		$username = $db->EscapeString($username);
		$password = $db->EscapeString($password);

		// Check if php-ldap extension is loaded
		if (Dependency::check("ldap", $errmsg)) {
    		if ($this->checkLdapUser($username, $password, $this->mldap_hostname, $this->mldap_o, $this->mldap_filter, $errmsg)) {
    			//LDAP Authentication Successful
    			$sql = "SELECT user_id, pass FROM users WHERE (username='$username') AND account_origin='".$this->getNetwork()->getId()."'";

    			$db->ExecSqlUniqueRes($sql, $user_info, false);

    			if ($user_info != null) {
    				$user = User::getObject($user_info['user_id']);

    				if ($user->isUserValid($errmsg)) {
    					$retval = $user;
    					User::setCurrentUser($user);
    					$errmsg = _("Login successfull");
    				} else {
    					$retval = false;
            			//Error already been set
    				}
    			} else {
    				$user = User::createUser(get_guid(), $username, $this->getNetwork(), "", "");
    				$retval = &$user;
    				$user->setAccountStatus(ACCOUNT_STATUS_ALLOWED);
    				User::setCurrentUser($user);
    				$errmsg = _("Login successfull");
    			}
    		} else {
    			return false;
    			//Error already been set
    		}
		}

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

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

