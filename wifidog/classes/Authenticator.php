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
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load Network class
 */
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/Session.php');
require_once('classes/User.php');

/**
 * Abstract class to represent an authentication source
 *
 * @package    WiFiDogAuthServer
 * @subpackage Authenticators
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
abstract class Authenticator
{
    /**
     * Object of current network
     *
     * @var object
     *
     * @access private
     */
    private $mNetwork;

    /**
     * Constructor
     *
     * @param string $network_id Id of network
     *
     * @return void
     *
     * @access public
     */
    public function __construct($network_id)
    {
        $this->mNetwork = Network::getObject($network_id);
    }

    /**
     * Returns object of current network
     *
     * @return object Object of current network
     *
     * @access public
     */
    public function getNetwork()
    {
        return $this->mNetwork;
    }

    /**
     * Attempts to login a user against the authentication source
     *
     * If successfull, returns a User object.
     *
     * @access public
     */
    public function login()
    {
        // Must be defined in child class
    }

    /**
     * Logs out the user
     *
     * @param string $conn_id The connection id for the connection to work on.
     *                        If  it is not present, the behaviour depends if
     *                        the network supports multiple logins. If it does
     *                        not, all connections associated with the current
     *                        user will be destroyed. If it does, only the
     *                        connections tied to the current node will be
     *                        destroyed.
     *
     * @return void
     *
     * @access public
     */
    public function logout($conn_id = null)
    {
        // Define globals
        global $db;
        global $session;

        $conn_id = $db->escapeString($conn_id);

        if (!empty ($conn_id)) {
            $db->execSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);

            $user = User::getObject($info['user_id']);
            $network = $user->getNetwork();
            $splash_user_id = $network->getSplashOnlyUser()->getId();
            $this->acctStop($conn_id);
        } else {
            $user = User::getCurrentUser();
            $network = $user->getNetwork();
            $splash_user_id = $network->getSplashOnlyUser()->getId();

            if ($splash_user_id != $user->getId() && $node = Node::getCurrentNode()) {
                // Try to destroy all connections tied to the current node
                $sql = "SELECT conn_id FROM connections WHERE user_id = '{$user->getId()}' AND node_id='{$node->getId()}' AND token_status='".TOKEN_INUSE."';";
                $conn_rows = null;
                $db->execSql($sql, $conn_rows, false);

                if ($conn_rows) {
                    foreach ($conn_rows as $conn_row) {
                        $this->acctStop($conn_row['conn_id']);
                    }
                }
            }
        }

        if ($splash_user_id != $user->getId() && $network->getMultipleLoginAllowed() === false) {
            /*
             * The user isn't the splash_only user and the network config does
             * not allow multiple logins. Logging in with a new token implies
             * that all other active tokens should expire
             */
            $sql = "SELECT conn_id FROM connections WHERE user_id = '{$user->getId()}' AND token_status='".TOKEN_INUSE."';";
            $conn_rows = null;
            $db->execSql($sql, $conn_rows, false);

            if ($conn_rows) {
                foreach ($conn_rows as $conn_row) {
                    $this->acctStop($conn_row['conn_id']);
                }
            }
        }

        // Try to destroy current session
        if (method_exists($session, "destroy")) {
            $session->destroy();
        }
    }

    /**
     * Start accounting traffic for the user
     *
     * @param string $conn_id The connection id for the connection to work on
     *
     * @return void
     *
     * @access public
     */
    public function acctStart($conn_id)
    {
        // Define globals
        global $db;

        $conn_id = $db->escapeString($conn_id);
        $db->execSqlUniqueRes("SELECT NOW(), *, CASE WHEN ((NOW() - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.conn_id='$conn_id'", $info, false);
        $network = Network::getObject($info['network_id']);
        $splash_user_id = $network->getSplashOnlyUser()->getId();
        $auth_response = $info['account_status'];

        // Login the user
        $mac = $db->escapeString($_REQUEST['mac']);
        $ip = $db->escapeString($_REQUEST['ip']);
        $sql = "UPDATE connections SET "."token_status='".TOKEN_INUSE."',"."user_mac='$mac',"."user_ip='$ip',"."last_updated=NOW()"."WHERE conn_id='{$conn_id}';";
        $db->execSqlUpdate($sql, false);

        if ($splash_user_id != $info['user_id'] && $network->getMultipleLoginAllowed() === false) {
            /*
             * The user isn't the splash_only user and the network config does
             * not allow multiple logins. Logging in with a new token implies
             * that all other active tokens should expire
             */
            $token = $db->escapeString($_REQUEST['token']);
            $sql = "SELECT * FROM connections WHERE user_id = '{$info['user_id']}' AND token_status='".TOKEN_INUSE."' AND token!='$token';";
            $conn_rows = array ();
            $db->execSql($sql, $conn_rows, false);

            if (isset ($conn_rows)) {
                foreach ($conn_rows as $conn_row) {
                    $this->acctStop($conn_row['conn_id']);
                }
            }
        }

        /*
         * Delete all unused tokens for this user, so we don't fill the database
         * with them
         */
        $sql = "DELETE FROM connections "."WHERE token_status='".TOKEN_UNUSED."' AND user_id = '{$info['user_id']}';";
        $db->execSqlUpdate($sql, false);
    }

    /**
     * Update traffic counters
     *
     * @param string $conn_id  The connection id for the connection to work on
     * @param int    $incoming Incoming traffic in bytes
     * @param int    $outgoing Outgoing traffic in bytes
     *
     * @return void
     *
     * @access public
     */
    public function acctUpdate($conn_id, $incoming, $outgoing)
    {
        // Define globals
        global $db;

        // Write traffic counters to database
        $conn_id = $db->escapeString($conn_id);
        $db->execSqlUpdate("UPDATE connections SET "."incoming='$incoming',"."outgoing='$outgoing',"."last_updated=NOW() "."WHERE conn_id='{$conn_id}'");
    }

    /**
     * Final update and stop accounting
     *
     * @param string $conn_id The connection id (the token id) for the
     *                        connection to work on
     *
     * @return void
     *
     * @access public
     * */
    public function acctStop($conn_id)
    {
        // Define globals
        global $db;

        // Stop traffic counters update
        $conn_id = $db->escapeString($conn_id);
        $db->execSqlUpdate("UPDATE connections SET "."timestamp_out=NOW(),"."token_status='".TOKEN_USED."' "."WHERE conn_id='{$conn_id}';\n", false);
    }

    /**
     * Property method that tells if the class allows registration
     *
     * @return bool Returns if the class allows registration
     *
     * @access public
     */
    public function isRegistrationPermitted()
    {
        return false;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

