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
 * @author     Philippe April
 * @copyright  2005 Philippe April
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/StatisticReport.php';

/* General report about a node */
class UserReport extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Individual user report");
    }

    /** Constructor
         * @param $statistics_object Mandatory to give the report it's context */
    protected function __construct(Statistics $statistics_object)
    {
        parent :: __construct($statistics_object);
    }

    /** Get the actual report.
     * Classes must override this, but must call the parent's method with what
     * would otherwise be their return value and return that instead.
     * @param $child_html The child method's return value
     * @return A html fragment
     */
    public function getReportUI($child_html = null)
    {
        global $db;
        $html = '';
        //$distinguish_users_by=$this->stats->getDistinguishUsersBy();
        //$candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("DISTINCT $distinguish_users_by, date_trunc('day', timestamp_in) AS date");

        $selected_users = $this->stats->getSelectedUsers();
        if ($selected_users)
        {
            foreach ($selected_users as $user_id => $userObject)
            {
                if ($userObject)
                {
                    $userinfo = null;
                    $user_id = $db->EscapeString($user_id);
                    $sql = "SELECT * FROM users WHERE user_id='{$user_id}'";
                    $db->ExecSqlUniqueRes($sql, $userinfo, false);
                    if ($userinfo == null)
                    {
                        throw new Exception(sprintf(_("User id: %s could not be found in the database"), $user_id));
                    }
                    global $account_status_to_text;
                    $userinfo['account_status_description'] = $account_status_to_text[$userinfo['account_status']];

                    $html .= "<fieldset class='pretty_fieldset'>\n";
                    $html .= "<legend>"._("Profile")."</legend>\n";

                    $html .= "<table>\n";

                    $html .= "<tr class='odd'>\n";
                    $html .= "  <th>"._("Username").":</th>\n";
                    $html .= "  <td>".$userinfo['username']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Real Name").":</th>\n";
                    $html .= "  <td>".$userinfo['real_name']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr class='odd'>\n";
                    $html .= "  <th>"._("Email").":</th>\n";
                    $html .= "  <td>".$userinfo['email']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Network").":</th>\n";
                    $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&network_id={$userinfo['account_origin']}'>{$userinfo['account_origin']}</a></td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr class='odd'>\n";
                    $html .= "  <th>"._("Unique ID").":</th>\n";
                    $html .= "  <td>".$userinfo['user_id']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Member since").":</th>\n";
                    $html .= "  <td>".strftime("%c", strtotime($userinfo['reg_date']))."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr class='odd'>\n";
                    $html .= "  <th>"._("Account Status").":</th>\n";
                    $html .= "  <td>".$userinfo['account_status_description']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Website").":</th>\n";
                    $html .= "  <td>".$userinfo['website']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "<tr class='odd'>\n";
                    $html .= "  <th>"._("Prefered Locale").":</th>\n";
                    $html .= "  <td>".$userinfo['prefered_locale']."</td>\n";
                    $html .= "</tr>\n";

                    $html .= "</table>\n";
                    $html .= "</fieldset>\n";
                }

                /******* Connections **********/
                $html .= "<fieldset class='pretty_fieldset'>\n";
                $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("*");
                $sql = "$candidate_connections_sql ORDER BY timestamp_in DESC";
                $db->ExecSql($sql, $connections, false);
                $html .= "<legend>".sprintf(_("%d Connections"), count($connections))."</legend>\n";

                // Variables init
                $even = 0;
                global $token_to_text;
                $total = array ();
                $total['incoming'] = 0;
                $total['outgoing'] = 0;
                $total['time_spent'] = 0;
                if (count($connections) == 0)
                {
                    $html .= _("No information found matching the report configuration");
                }
                else
                {
                    $html .= "<table class='smaller'>\n";
                    $html .= "<thead>\n";
                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Logged in")."</th>\n";
                    $html .= "  <th>"._("Time spent")."</th>\n";
                    $html .= "  <th>"._("Token status")."</th>\n";
                    $html .= "  <th>"._("Node")."</th>\n";
                    $html .= "  <th>"._("IP")."</th>\n";
                    $html .= "  <th>"._("D")."</th>\n";
                    $html .= "  <th>"._("U")."</th>\n";
                    $html .= "</tr>\n";
                    $html .= "</thead>\n";
                    foreach ($connections as $connection)
                    {

                        $timestamp_in = !empty ($connection['timestamp_in']) ? strtotime($connection['timestamp_in']) : null;
                        $timestamp_out = !empty ($connection['timestamp_out']) ? strtotime($connection['timestamp_out']) : null;

                        $nodeObject = Node :: getObject($connection['node_id']);
                        $total['incoming'] += $connection['incoming'];
                        $total['outgoing'] += $connection['outgoing'];

                        $connection['token_status_description'] = $token_to_text[$connection['token_status']];
                        $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                        if ($even == 0)
                            $even = 1;
                        else
                            $even = 0;
                        $html .= "  <td>".strftime("%c", $timestamp_in)."</td>\n";
                        if (!empty ($timestamp_in) && !empty ($timestamp_out))
                        {
                            $total['time_spent'] += ($timestamp_out - $timestamp_in);
                            $html .= "<td>".Utils :: convertSecondsToWords($timestamp_out - $timestamp_in)."</td>\n";
                        }
                        else
                        {
                            $html .= "<td>"._("N/A")."</td>\n";
                        }
                        $html .= "  <td>".$connection['token_status']."</td>\n";
                        $html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&node_id={$nodeObject->getId()}'>{$nodeObject->getName()}</a></td>\n";
                        $html .= "  <td>".$connection['user_ip']."</td>\n";
                        $html .= "  <td>".Utils :: convertBytesToWords($connection['incoming'])."</td>\n";
                        $html .= "  <td>".Utils :: convertBytesToWords($connection['outgoing'])."</td>\n";
                        $html .= "</tr>\n";
                    }
                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Total").":</th>\n";
                    $html .= "  <th>".Utils :: convertSecondsToWords($total['time_spent'])."</th>\n";
                    $html .= "  <td></td>\n";
                    $html .= "  <td></td>\n";
                    $html .= "  <td></td>\n";

                    $html .= "  <th>".Utils :: convertBytesToWords($total['incoming'])."</th>\n";
                    $html .= "  <th>".Utils :: convertBytesToWords($total['outgoing'])."</th>\n";
                    $html .= "</tr>\n";
                    $html .= "</table>\n";
                    $html .= "</fieldset>\n";
                }

                if ($this->stats->getDistinguishUsersBy() == 'user_id')
                {
                    /******* MAC addresses **********/
                    $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("user_mac,count(user_mac) as nb ");

                    $sql = "$candidate_connections_sql group by user_mac order by nb desc";
                    $db->ExecSql($sql, $rows, false);

                    $html .= "<fieldset class='pretty_fieldset'>\n";
                    $html .= "<legend>".sprintf(_("%d MAC addresses"), count($rows))."</legend>\n";
                    $html .= "<table>\n";
                    $html .= "<thead>\n";
                    $html .= "<tr>\n";
                    $html .= "  <th>"._("MAC")."</th>\n";
                    $html .= "  <th>"._("Count")."</th>\n";
                    $html .= "</tr>\n";
                    $html .= "</thead>\n";

                    $even = 0;
                    if ($rows)
                        foreach ($rows as $row)
                        {
                            $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                            if ($even == 0)
                                $even = 1;
                            else
                                $even = 0;
                            //$html .= "  <td><a href='?date_from={$_REQUEST['date_from']}&date_to={$_REQUEST['date_to']}&user_mac={$row['user_mac']}'>{$row['user_mac']}</a></td>\n";
                            $html .= "  <td>{$row['user_mac']}</td>\n";
                            $html .= "  <td>".$row['nb']."</td>\n";
                            $html .= "</tr>\n";
                        }

                    $html .= "</table>\n";
                    $html .= "</fieldset>\n";
                }
                else
                {
                    /******* Usernames **********/
                    $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("connections.user_id,username, count(connections.user_id) as nb ", true);

                    $sql = "$candidate_connections_sql group by connections.user_id, username order by nb desc, connections.user_id,username";
                    $db->ExecSql($sql, $rows, false);

                    $html .= "<fieldset class='pretty_fieldset'>\n";
                    $html .= "<legend>".sprintf(_("%d users"), count($rows))."</legend>\n";
                    $html .= "<table>\n";
                    $html .= "<thead>\n";
                    $html .= "<tr>\n";
                    $html .= "  <th>"._("Username")."</th>\n";
                    $html .= "  <th>"._("Count")."</th>\n";
                    $html .= "</tr>\n";
                    $html .= "</thead>\n";

                    $even = 0;
                    if ($rows)
                        foreach ($rows as $row)
                        {

                            $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                            if ($even == 0)
                                $even = 1;
                            else
                                $even = 0;
                            $html .= "  <td>{$row['username']}</td>\n";
                            $html .= "  <td>".$row['nb']."</td>\n";
                            $html .= "</tr>\n";
                        }

                    $html .= "</table>\n";
                    $html .= "</fieldset>\n";
                }

            } //End foreach
        } //End else

        return parent :: getReportUI($html);
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
