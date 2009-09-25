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
 * @subpackage Statistics
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/StatisticReport.php');

/**
 * Report on user connections
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class ConnectionLog extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Connection Log");
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
        $db = AbstractDb::getObject();
        $html = '';

        /* User visits */
        // Only Super admin
        if (!User :: getCurrentUser()->DEPRECATEDisSuperAdmin())
        {
            $html .= "<p class='error'>"._("Access denied")."</p>";
        }
        else
        {
            $distinguish_users_by = $this->stats->getDistinguishUsersBy();

            if ($distinguish_users_by == 'user_id')
            {
                $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("connections.$distinguish_users_by, count(distinct connections.user_mac) as nb_mac, COUNT(DISTINCT connections.user_id) AS nb_users, username,count(connections.$distinguish_users_by) as nb_cx,max(timestamp_in) as last_seen", true);
                $sql = "$candidate_connections_sql GROUP BY connections.$distinguish_users_by,username ORDER BY nb_cx desc,username";

            }
            else
            {
                $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("connections.$distinguish_users_by, count(distinct connections.user_mac) as nb_mac, COUNT(DISTINCT connections.user_id) AS nb_users, count(connections.$distinguish_users_by) as nb_cx,max(timestamp_in) as last_seen", true);
                $sql = "$candidate_connections_sql GROUP BY connections.$distinguish_users_by ORDER BY nb_cx desc";
            }
            $db->execSql($sql, $rows, false);

            $html .= "<fieldset>";
            $html .= "<legend>"._("Number of unique Users:").count($rows)."</legend>";
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            if ($distinguish_users_by == 'user_id')
            {
                $html .= "<th>"._("Username")."</th>";
                $html .= "<th>"._("MAC Count")."</th>";
            }
            else
            {
                $html .= "<th>MAC</th>";
                $html .= "<th>Users count</th>";
            }

            $html .= "<th>"._("Cx Count")."</th>";
            $html .= "<th>"._("Last seen")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            foreach ($rows as $row)
            {
                $html .= "<tr>\n";
                if ($distinguish_users_by == 'user_id')
                {
                    $html .= "  <td>{$row['username']}</td>\n";
                    $html .= "  <td>".$row['nb_mac']."</td>\n";
                }
                else
                {
                    $html .= "  <td>{$row['user_mac']}</td>\n";
                    $html .= "  <td>".$row['nb_users']."</td>\n";
                }

                $html .= "  <td>".$row['nb_cx']."</td>\n";
                $html .= "  <td>".strftime("%Y-%m-%d %H:%M:%S", strtotime($row['last_seen']))."</td>\n";
                $html .= "</tr>\n";
            }

            $html .= "</table>";
            $html .= "</fieldset>";

            $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("users.username, nodes.name, EXTRACT('EPOCH' FROM date_trunc('second',timestamp_out-timestamp_in)) AS time_spent, EXTRACT('EPOCH' FROM timestamp_in) AS timestamp_in, connections.user_id, user_mac ", true);

            $sql = "$candidate_connections_sql ORDER BY timestamp_in DESC";
            $db->execSql($sql, $rows, false);

            $number_of_connections = count($rows);

            $html .= "<fieldset>";
            $html .= "<legend>"._("Number of non-unique connections:").$number_of_connections."</legend>";
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "<th>"._("Node name")."</th>";
            $html .= "<th>"._("Username")."</th>";
            $html .= "<th>"._("MAC")."</th>";
            $html .= "<th>"._("Date")."</th>";
            $html .= "<th>"._("Time spent")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            $even = 0;
            if ($rows)
            {
                foreach ($rows as $row)
                {
                    $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                    if ($even == 0)
                        $even = 1;
                    else
                        $even = 0;
                    $html .= "  <td>{$row['name']}</td>\n";
                    $html .= "  <td>{$row['username']}</td>\n";
                    $html .= "  <td>{$row['user_mac']}</td>\n";
                    $html .= "  <td>".strftime("%c", $row['timestamp_in'])."</td>\n";
                    $html .= "  <td>";
                    if (!empty ($row['time_spent']))
                    {
                        $html .= Utils :: convertSecondsToWords($row['time_spent']);
                    }
                    else
                    {
                        $html .= _("Unknown");
                    }
                    $html .= "</td>\n";
                    $html .= "</tr>\n";
                }
            }

            $html .= "</table>\n";
            $html .= "</fieldset>\n";
        }

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


