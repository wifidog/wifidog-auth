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
 * @author     Benoit Grégoire
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/StatisticReport.php');

/**
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Philippe April
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 */
class ActiveUserReport extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Breakdown of how many users actually use the network");
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

        /* First connection per node */
        $html .= "<fieldset>";
        $html .= "<legend>"._("User activity")."</legend>";
        $node_usage_stats = null;
        $distinguish_users_by = $this->stats->getDistinguishUsersBy();

        /* Now retrieves the oldest connection matching the report restriction, and only keep it if it's really the user's first connection */
        $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("DISTINCT ON(connections.$distinguish_users_by) connections.$distinguish_users_by, timestamp_in, (NOW() - interval '1 month') ", true);
        //$db->execSql($candidate_connections_sql, $tmp, true);

        //Format is $breakdown_col_array['column_alias']=array("Column label", 'interval is SQL format');
        $breakdown_col_array['last_day']=array(_("Last day"), '1 day');
        $breakdown_col_array['last_week']=array(_("Last week"), '1 week');
        $breakdown_col_array['last_month']=array(_("Last month"), '1 month');
        $breakdown_col_array['last_3_month']=array(_("Last 3 month"), '3 month');
        $breakdown_col_array['last_6_months']=array(_("Last 6 months"), '6 months');
        $breakdown_col_array['last_year']=array(_("Last year"), '1 year');
        $breakdown_col_array['forever']=array(_("Ever"), '2000 year');
        $first = true;
        $col_sql = null;
        foreach ($breakdown_col_array as $col_name => $col_info) {
            $first?$first=false:$col_sql .= ", ";
            $col_sql .= "COUNT(CASE WHEN (timestamp_in > (NOW() - interval '{$col_info[1]}')) THEN TRUE END) AS $col_name \n";
        }
        $network_constraint = $this->stats->getSqlNetworkConstraint('networks.network_id');
        $user_constraint = $this->stats->getSqlUserConstraint();
        
        /* For validated users */
        $last_connection_table_sql = "$candidate_connections_sql AND users.account_status = ".ACCOUNT_STATUS_ALLOWED." GROUP BY connections.$distinguish_users_by, timestamp_in ORDER BY connections.$distinguish_users_by, timestamp_in DESC \n";
        //$db->execSql($last_connection_table_sql, $node_usage_stats, false);

        $breakdown_sql = "SELECT \n $col_sql FROM ($last_connection_table_sql) AS last_connection_table\n";
        $db->execSqlUniqueRes($breakdown_sql, $validated_user_breakdown_row, false);

        $num_validated_users_sql = "SELECT COUNT(users.user_id) \n";
        $num_validated_users_sql .= "FROM users JOIN networks on (users.account_origin = networks.network_id) \n";
        $num_validated_users_sql .= "WHERE 1=1 {$network_constraint} {$user_constraint} AND users.account_status = ".ACCOUNT_STATUS_ALLOWED;
        $db->execSqlUniqueRes($num_validated_users_sql, $num_validated_users_row, false);

        /* For non-validated users */
        $last_connection_table_sql = "$candidate_connections_sql AND users.account_status != ".ACCOUNT_STATUS_ALLOWED." GROUP BY connections.$distinguish_users_by, timestamp_in ORDER BY connections.$distinguish_users_by, timestamp_in DESC \n";
        //$db->execSql($last_connection_table_sql, $node_usage_stats, false);

        $breakdown_sql = "SELECT \n $col_sql FROM ($last_connection_table_sql) AS last_connection_table\n";
        $db->execSqlUniqueRes($breakdown_sql, $unvalidated_user_breakdown_row, false);

        $num_unvalidated_users_sql = "SELECT COUNT(users.user_id) \n";
        $num_unvalidated_users_sql .= "FROM users JOIN networks on (users.account_origin = networks.network_id) \n";
        $num_unvalidated_users_sql .= "WHERE 1=1 {$network_constraint} {$user_constraint} AND users.account_status != ".ACCOUNT_STATUS_ALLOWED;
        $db->execSqlUniqueRes($num_unvalidated_users_sql, $num_unvalidated_users_row, false);
        if ($validated_user_breakdown_row)
        {
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "  <th colspan=2>".sprintf(_("Activity report for the %d validated users"),$num_validated_users_row['count'])."</th>";
            $html .= "</tr>";
            $html .= "<tr>";
            $html .= "  <th>"._("Period")."</th>";
            $html .= "  <th>"._("# of users who used the network")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            $total = 0;
            $even = 0;

            foreach ($breakdown_col_array as $col_name => $col_info) {
                $first?$first=false:$col_sql .= ", ";
                $col_sql .= "COUNT(CASE WHEN (timestamp_in > (NOW() - interval '{}')) THEN TRUE END) AS $col_name \n";
                $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                if ($even == 0)
                $even = 1;
                else
                $even = 0;
                $html .= "  <td>{$col_info[0]}</td>\n";
                $html .= "  <td>".$validated_user_breakdown_row[$col_name]."</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        if ($unvalidated_user_breakdown_row)
        {
            $html .= "<table>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "  <th colspan=2>".sprintf(_("Activity report for the %d non-validated users"),$num_unvalidated_users_row['count'])."</th>";
            $html .= "</tr>";
            $html .= "<tr>";
            $html .= "  <th>"._("Period")."</th>";
            $html .= "  <th>"._("# of users who used the network")."</th>";
            $html .= "</tr>";
            $html .= "</thead>";

            $total = 0;
            $even = 0;

            foreach ($breakdown_col_array as $col_name => $col_info) {
                $first?$first=false:$col_sql .= ", ";
                $col_sql .= "COUNT(CASE WHEN (timestamp_in > (NOW() - interval '{}')) THEN TRUE END) AS $col_name \n";
                $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                if ($even == 0)
                $even = 1;
                else
                $even = 0;
                $html .= "  <td>{$col_info[0]}</td>\n";
                $html .= "  <td>".$unvalidated_user_breakdown_row[$col_name]."</td>";
                $html .= "</tr>";
            }
            $html .= "<tfoot>";
            $html .= "<tr>";
            $html .= "</tr>";
            $html .= "</tfoot>";
            $html .= "</table>";
        }
        $html .= "<p class='warningmsg'>"._("warning:  This report does not count connections at Splash-Only nodes")."</p>";
        
        $html .= "</fieldset>";

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


