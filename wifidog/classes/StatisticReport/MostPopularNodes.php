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
 * @author     Philippe April
 * @copyright  2005-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/StatisticReport.php');

/**
 * Reports on the most popular nodes (currently by visits, will be extended
 * in the future)
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Philippe April
 * @copyright  2005-2006 Philippe April
 */
class MostPopularNodes extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Most popular nodes, by visit");
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
        $node_usage_stats = null;

        $distinguish_users_by = $this->stats->getDistinguishUsersBy();
        $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("DISTINCT connections.$distinguish_users_by, connections.node_id, nodes.name, date_trunc('day', timestamp_in) as rounded_date");
        //$db->execSql($candidate_connections_sql, $tmp, true);

        $daily_visit_table_name = "daily_visit_table_name_".session_id();
        $daily_visit_table_sql = "CREATE TEMP TABLE  $daily_visit_table_name AS ($candidate_connections_sql);\n  \n";
        $daily_visit_table_sql .= "CREATE INDEX {$daily_visit_table_name}_idx ON $daily_visit_table_name (node_id)";
        $db->execSqlUpdate($daily_visit_table_sql, false);
        $daily_visit_table_sql = "SELECT COUNT ($distinguish_users_by) AS total_visits, node_id, name FROM $daily_visit_table_name GROUP BY node_id, name ORDER BY total_visits DESC;";
        $db->execSql($daily_visit_table_sql, $node_usage_stats, false);
        $daily_visit_table_sql = "DROP TABLE $daily_visit_table_name;";
        $db->execSqlUpdate($daily_visit_table_sql, false);

        //		$candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("nodes.name,connections.node_id,COUNT(connections.node_id) AS connections ");
        //		$sql = "$candidate_connections_sql GROUP BY connections.node_id,nodes.name ORDER BY connections DESC";
        //		$db->execSql($sql, $node_usage_stats, false);

        if ($node_usage_stats)
        {
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "  <th>"._("Node")."</th>";
            $html .= "  <th>"._("Visits")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            $total = 0;
            $even = 0;

            foreach ($node_usage_stats as $row)
            {
                $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                if ($even == 0)
                    $even = 1;
                else
                    $even = 0;
                $html .= "  <td>{$row['name']}</td>\n";
                $html .= "  <td>".$row['total_visits']."</td>";
                $html .= "</tr>";
                $total += $row['total_visits'];
            }
            $html .= "<tfoot>";
            $html .= "<tr>";
            $html .= "  <th>"._("Total").":</th>";
            $html .= "  <th>".$total."</th>";
            $html .= "</tr>";
            $html .= "<tr>";
            $html .= "  <td colspan=2>"._("Note:  A visit is like counting connections, but only counting one connection per day for each user at a single node").":</td>";
            $html .= "</tr>";
            $html .= "</tfoot>";
            $html .= "</table>";
        }
        else
        {
            $html .= _("No information found matching the report configuration");
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


