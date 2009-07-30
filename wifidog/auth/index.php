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
 * This is the main auth handler, be very carefull while editing this file!
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author     Philippe April
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2004-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load common include file
 */
require_once('../include/common.php');
require_once('classes/Network.php');
require_once('classes/User.php');
$db = AbstractDb::getObject();
$auth_response = ACCOUNT_STATUS_DENIED;
$auth_message = '';
$info=null;
$token = null;
if (!empty ($_REQUEST['token']))
{
    $token = $db->escapeString($_REQUEST['token']);
    $dbRetval = $db->execSqlUniqueRes("SELECT CURRENT_TIMESTAMP, *, CASE WHEN ((CURRENT_TIMESTAMP - reg_date) > networks.validation_grace_time) THEN true ELSE false END AS validation_grace_time_expired FROM connections JOIN tokens USING (token_id) JOIN users ON (users.user_id=connections.user_id) JOIN networks ON (users.account_origin = networks.network_id) WHERE connections.token_id='$token'", $info, false);
    if($dbRetval==false){
        $auth_message .= "| Error: couldn't retrieve the requested token: $token because of a SQL error. ";
        $auth_response = ACCOUNT_STATUS_ERROR;
    }
}
else {
    $auth_message .= "| Error: no connection token provided. ";
    $auth_response = ACCOUNT_STATUS_ERROR;
}

if ($info != null)
{
    // Retrieve the associated authenticator
    $network = Network :: getObject($info['account_origin']);
    $authenticator = $network->getAuthenticator();
    if (!$authenticator)
    {
        $auth_message .= "| Error: Unable to instantiate authenticator. ";
        $auth_response = ACCOUNT_STATUS_ERROR;
    }
    else
    {
        if ($_REQUEST['stage'] == STAGE_LOGIN)
        {
            if ($info['token_status'] == TOKEN_UNUSED)
            {
                /* This is for the 15 minutes validation period, the exact same code is also present in when the stage is counters.  If you update this one don't forget to update the other one! */
                if (($info['account_status'] == ACCOUNT_STATUS_VALIDATION) && ($info['validation_grace_time_expired'] == 't'))
                {
                    $auth_response = ACCOUNT_STATUS_VALIDATION_FAILED;
                    $auth_message .= "| The validation grace period which began at ".$info['reg_date']." has now expired. ";
                }
                else
                {
                    // Start accounting
                    if ($authenticator->acctStart($info['conn_id'], $auth_message))
                    $auth_response = ACCOUNT_STATUS_ALLOWED;
                    else
                    $auth_response = ACCOUNT_STATUS_DENIED;

                }
            }
            else
            if ($info['token_status'] == TOKEN_INUSE &&
            $info['gw_id'] && isset($_REQUEST['gw_id']) && $info['gw_id'] == $_REQUEST['gw_id'] &&
            $info['user_mac'] && isset($_REQUEST['mac']) && $info['user_mac'] == $_REQUEST['mac'] &&
            $info['user_ip'] && isset($_REQUEST['ip']) && $info['user_ip'] == $_REQUEST['ip'])
            {
                // This solves the bug where the user clicks twice before getting the portal page
                $auth_response = ACCOUNT_STATUS_ALLOWED;
            }
            else
            {
                $auth_message .= "| Tried to login with a token that wasn't TOKEN_UNUSED. ";
            }
        }
        else
        if ($_REQUEST['stage'] == STAGE_LOGOUT || $_REQUEST['stage'] == STAGE_COUNTERS)
        {
            if (!empty ($_REQUEST['incoming']) || !empty ($_REQUEST['outgoing']))
            {
                $incoming = $db->escapeString($_REQUEST['incoming']);
                $outgoing = $db->escapeString($_REQUEST['outgoing']);

                if (($incoming >= $info['incoming']) && ($outgoing >= $info['outgoing']))
                {
                    $authenticator->acctUpdate($info['conn_id'], $incoming, $outgoing);
                    $auth_message .= "| Updated counters. ";
                }
                else
                {
                    $auth_message .= "| Warning:  Incoming or outgoing counter is smaller than what is stored in the database; counters not updated. ";

                }
            }
            else
            {
                $auth_message .= "| Incoming or outgoing counter is missing; counters not updated. ";
            }

            if ($_REQUEST['stage'] == STAGE_LOGOUT)
            {
                $authenticator->logout($info['conn_id']);
                $auth_message .= "| User is now logged out. ";
            }

            if ($_REQUEST['stage'] == STAGE_COUNTERS)
            {
                if ($info['token_status'] == TOKEN_INUSE)
                {
                    /* This is for the 15 minutes validation period, the exact same code is also present when the stage is login.  If you update this one don't forget to update the other one! */
                    if (($info['account_status'] == ACCOUNT_STATUS_VALIDATION) && ($info['validation_grace_time_expired'] == 't'))
                    {
                        $auth_response = ACCOUNT_STATUS_VALIDATION_FAILED;
                        $auth_message .= "| The validation grace period which began at ".$info['reg_date']." has now expired. ";
                    }
                    else
                    {
                        /* TODO:  This is a bit hackish, is't a shortcut untill the Token architecture uniform connection limit calculations are in place. */
                        $abuseControlFault = User::isAbuseControlViolated(User::getObject($info['user_id']), $info['user_mac'], Node::getObject($info['node_id']));
                        if($abuseControlFault) {
                            $auth_response = ACCOUNT_STATUS_DENIED;
                            $auth_message .= "| $abuseControlFault ";
                        }
                        else {
                            $auth_response = $info['account_status'];
                        }
                    }
                }
                else
                {
                    $auth_response = ACCOUNT_STATUS_DENIED;
                    $auth_message .= "| Invalid token status: ".$token_to_text[$info['token_status']].". ";
                }

            }

        }
        else
        {
            $auth_message .= "| Error: Unknown stage. ";
            $auth_response = ACCOUNT_STATUS_ERROR;
        }
    }
}
else
{
    $auth_message .= "| Error: couldn't find the requested token: $token. ";
    $auth_response = ACCOUNT_STATUS_ERROR;
}

echo "Auth: $auth_response\n";
echo "Messages: $auth_message\n";

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>