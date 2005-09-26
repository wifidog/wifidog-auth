<?php


/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                                  *
 * This program is distributed in the hope that it will be useful,  *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of   *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    *
 * GNU General Public License for more details.                     *
 *                                                                  *
 * You should have received a copy of the GNU General Public License*
 * along with this program; if not, contact:                        *
 *                                                                  *
 * Free Software Foundation           Voice:  +1-617-542-5942       *
 * 59 Temple Place - Suite 330        Fax:    +1-617-542-2652       *
 * Boston, MA  02111-1307,  USA       gnu@gnu.org                   *
 *                                                                  *
 \********************************************************************/
/**@file UserRegistrationReport.php
 * @author Copyright (C) 2005 Technologies Coeus inc. and Philippe April
 */

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/StatisticReport.php';

/** Reports on the most popular nodes (currently by visits, will be extended in the future) */
class UserRegistrationReport extends StatisticReport
{
	/** Get the report's name.  Must be overriden by the report class 
	 * @return a localised string */
	public static function getReportName()
	{
		return _("User registration report");
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
		/* Monthly registration graph */
		$graph = StatisticGraph :: getObject('RegistrationsPerMonth');
		$html .= $graph->getReportUI($this->stats);
		/* End Monthly registration graph */

		/* Cumulative registration graph */
		$graph = StatisticGraph :: getObject('RegistrationsCumulative');
		$html .= $graph->getReportUI($this->stats);
		/* End cumulative registration graph */

		/* First connection per node */
		$html .= "<fieldset class='pretty_fieldset'>";
		$html .= "<legend>"._("First connection per node")."</legend>";
		$node_usage_stats = null;
		$distinguish_users_by = $this->stats->getDistinguishUsersBy();

		/* The following query will retreive the list of the REAL first connection of each user, no matter where or when.*/
		$sql_real_first_connections = $this->stats->getSqlRealFirstConnectionsQuery();
		//$db->ExecSql($sql_real_first_connections, $tmp, true);
		$real_first_connections_table_name = "real_first_conn_table_name_".session_id();

		$real_first_connections_table_sql = "CREATE TABLE  $real_first_connections_table_name AS ($sql_real_first_connections);\n";
		//$real_first_connections_table_sql .= "CREATE INDEX {$real_first_connections_table_name}_idx ON $real_first_connections_table_name (conn_id); \n";
		$db->ExecSqlUpdate($real_first_connections_table_sql, false);

		/* Now retrieves the oldest connection matching the report restriction, and only keep it if it's really the user's first connection */
		$candidate_connections_sql = $this->stats->getSqlCandidateConnectionsQuery("DISTINCT ON(connections.$distinguish_users_by) connections.$distinguish_users_by, conn_id, connections.node_id, nodes.name,timestamp_in ");
		//$db->ExecSql($candidate_connections_sql, $tmp, true);

		$first_connection_table_sql = "$candidate_connections_sql ORDER BY connections.$distinguish_users_by, connections.node_id, nodes.name, timestamp_in DESC\n";
		//$db->ExecSql($first_connection_table_sql, $node_usage_stats, true);

		$first_connection_table_name = "first_connection_table_name_".session_id();
		$registration_node_table_sql = "CREATE TEMP TABLE  $first_connection_table_name AS ($first_connection_table_sql);\n  \n";
		//$registration_node_table_sql .= "CREATE INDEX {$first_connection_table_name}_idx ON $first_connection_table_name (node_id)";
		$db->ExecSqlUpdate($registration_node_table_sql, false);
		$registration_node_table_sql = "SELECT COUNT ($first_connection_table_name.$distinguish_users_by) AS total_first_connections, node_id, name FROM $first_connection_table_name JOIN $real_first_connections_table_name ON ($first_connection_table_name.conn_id=$real_first_connections_table_name.conn_id) GROUP BY node_id, name ORDER BY total_first_connections DESC;";
		$db->ExecSql($registration_node_table_sql, $node_usage_stats, false);
		$registration_node_table_sql = "DROP TABLE $first_connection_table_name;";
		$db->ExecSqlUpdate($registration_node_table_sql, false);

		$real_first_connections_table_sql = "DROP TABLE $real_first_connections_table_name;";
		$db->ExecSqlUpdate($real_first_connections_table_sql, false);

		if ($node_usage_stats)
		{
			$html .= "<table>";
			$html .= "<thead>";
			$html .= "<tr>";
			$html .= "  <th>"._("Node")."</th>";
			$html .= "  <th>"._("# of new user first connection")."</th>";
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
				$html .= "  <td>".$row['total_first_connections']."</td>";
				$html .= "</tr>";
				$total += $row['total_first_connections'];
			}
			$html .= "<tfoot>";
			$html .= "<tr>";
			$html .= "  <th>"._("Total").":</th>";
			$html .= "  <th>".$total."</th>";
			$html .= "</tr>";
			$html .= "<tr>";
			$html .= "  <td colspan=2>"._("Note:  This is actually a list of how many new user's first connection occured at each hotspot, taking report restrictions into account.").":</td>";
			$html .= "</tr>";
			$html .= "</tfoot>";
			$html .= "</table>";
		}
		else
		{
			$html .= _("No information found matching the report configuration");
		}
		/* End first connection per node */

		return parent :: getReportUI($html);
	}

} //End class
?>