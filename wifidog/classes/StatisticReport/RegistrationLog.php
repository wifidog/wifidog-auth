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
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2005 Benoit Gregoire, Technologies Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

require_once('classes/StatisticReport.php');

/* Report on new user registration */
class RegistrationLog extends StatisticReport
{
    /** Get the report's name.  Must be overriden by the report class
     * @return a localised string */
    public static function getReportName()
    {
        return _("Registration Log (New user's first connection)");
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
        /* Users who signed up here */

        $distinguish_users_by = $this->stats->getDistinguishUsersBy();

        /* The following query will retreive the list of the REAL first connection of each user, no matter where or when.*/
        $sql_real_first_connections = $this->stats->getSqlRealFirstConnectionsQuery();
        //$db->ExecSql($sql_real_first_connections, $tmp, true);
        $real_first_connections_table_name = "real_first_conn_table_name_".session_id();

        $real_first_connections_table_sql = "CREATE TABLE  $real_first_connections_table_name AS ($sql_real_first_connections);\n";
        $real_first_connections_table_sql .= "CREATE INDEX {$real_first_connections_table_name}_idx ON $real_first_connections_table_name (conn_id); \n";
        $db->ExecSqlUpdate($real_first_connections_table_sql, false);

        /* Now retrieves the oldest connection matching the report restriction, and only keep it if it's really the user's first connection */
        $candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("connections.$distinguish_users_by,users.username,users.reg_date, conn_id, nodes.name ", true);
        //$db->ExecSql($candidate_connections_sql, $tmp, true);

        $first_connection_table_name = "first_conn_table_name_".session_id();
        $registration_node_table_sql = "CREATE TEMP TABLE  $first_connection_table_name AS ($candidate_connections_sql);\n  \n";
        //$registration_node_table_sql .= "CREATE INDEX {$first_connection_table_name}_idx ON $first_connection_table_name (conn_id)";
        $db->ExecSqlUpdate($registration_node_table_sql, false);

        //$sql = "select FROM connections,nodes,users where timestamp_in IN (SELECT MIN(timestamp_in) as first_connection FROM connections GROUP BY user_id) ${date_constraint} AND users.user_id=connections.user_id AND connections.node_id='{$node_id}' AND nodes.node_id='{$node_id}' AND reg_date >= creation_date ORDER BY reg_date DESC";
        $sql = "SELECT * FROM $first_connection_table_name JOIN $real_first_connections_table_name USING (conn_id) ORDER BY reg_date DESC";
        $rows = null;
        $db->ExecSql($sql, $rows, false);

        $registration_node_table_sql = "DROP TABLE $first_connection_table_name;";
        $db->ExecSqlUpdate($registration_node_table_sql, false);
        $real_first_connections_table_sql = "DROP TABLE $real_first_connections_table_name;";
        $db->ExecSqlUpdate($real_first_connections_table_sql, false);
        $html .= "<fieldset class='pretty_fieldset'>";
        $html .= "<legend>"._("Users who signed up here")."</legend>";
        $html .= "<table>";
        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "<th>"._("Node")."</th>";
        if ($distinguish_users_by == 'user_id')
        {
            $html .= "<th>"._("Username")."</th>";
        }
        else
        {
            $html .= "<th>"._("MAC address")."</th>";
        }

        $html .= "<th>"._("Registration date")."</th>";
        $html .= "</tr>";
        $html .= "</thead>";

        $even = 0;
        $total = 0;
        if ($rows)
        {
            foreach ($rows as $row)
            {
                $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
                if ($even == 0)
                    $even = 1;
                else
                    $even = 0;

                $total ++;
                $html .= "  <td>{$row['name']}</td>\n";
                if ($distinguish_users_by == 'user_id')
                {
                    $html .= "  <td>{$row['username']}</td>\n";
                }
                else
                {
                    $html .= "  <td>{$row['user_mac']}</td>\n";
                }
                $html .= "  <td>".strftime("%c", strtotime($row['reg_date']))."</td>\n";
                $html .= "</tr>\n";
            }
        }

        $html .= "<tr>\n";
        $html .= "  <th>"._("Total").":</th>\n";
        $html .= "  <th>".$total."</th>\n";
        $html .= "</tr>\n";
        $html .= "</table>\n";
        $html .= "</fieldset>\n";

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
