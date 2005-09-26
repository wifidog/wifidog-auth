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
 \*******************************************************************/
/**@file Statistics.php
 * @author Copyright (C) 2004 Technologies Coeus inc.
 */

require_once BASEPATH.'include/common.php';

/* Gives various statistics about the status of the network or of a specific node */
class Statistics
{
	/** An array of the of the selected networks.  
	 * The key is the network_id, the value is the Network object. */
	private $report_selected_networks = array ();
	private $report_date_min; /**< Minimum timestamp */
	/** Maximum timestamp */
	private $report_date_max;
	/** How to distinguish unique users.  Either 'user_mac' (MAC adresses) or 'user_id' */
	private $report_distinguish_users_by = 'user_id';

	/* Options on how to distinguish users ,see constructor */
	private $user_distinguish_by_options = array ();

	/** An array of the node_ids to which the statistics should be restricted. 
		 * The key is the network_id, the value is the Network object.*/
	private $report_selected_nodes = array ();
	/** An array of the selected reports to be generated. 
	 *  The key is the classname, the value is the report object */
	private $report_selected_reports = array ();

	/** An array of the selected users to which the reports should be restricted. 
	 *  The  key is the user_id or user_mac, the value is the User object,
	 *  or null if the key is a MAC address */
	private $report_selected_users = array ();
	function __construct()
	{
		$network = Network :: GetCurrentNetwork();
		$this->report_selected_networks[$network->getId()] = $network;
		$this->user_distinguish_by_options = array ('user_id' => _("Usernames"), 'user_mac' => _("MAC adresses"));

	}

	/** Get the list of available report types 
	 * @return an array of class names */
	public function getAvailableReports()
	{
		$dir = BASEPATH.'classes/StatisticReport';
		if ($handle = opendir($dir))
		{
			$tab = Array ();
			/* This is the correct way to loop over the directory. */
			while (false !== ($file = readdir($handle)))
			{
				if ($file != '.' && $file != '..')
				{
					$matches = null;
					if (preg_match("/^(.*)\.php$/", $file, $matches) > 0)
					{
						//pretty_print_r($matches);
						$filename = $dir.'/'.$matches[0];
						require_once ($filename);
						$classname = $matches[1];
						if (call_user_func(array ($classname, 'isAvailable'), $this))
						{
							$name = call_user_func(array ($classname, 'getReportName'), $this);

							$tab[$classname] = $name;
						}
					}
				}
			}
			closedir($handle);
		}
		else
		{
			throw new Exception(_('Unable to open directory ').$dir);
		}
		$tab = str_ireplace('.php', '', $tab);
		asort($tab);
		return $tab;
	}

	/** UI for selecting the date ragnge for the report 
	 * @return html markup */
	private function getDateRangeUI()
	{
		$html = '';
		$html .=<<<EOF
<script language="javascript">
    function change_value(value, from, to) {
        if (value != "") {
            var values_array = value.split(",");
            from.value = values_array[0];
            if (values_array[1]) {
                to.value = values_array[1];
            }
        }
    }
</script>
EOF;
		$from_presets = array (_("No restriction...") => "", _("yesterday") => strftime("%Y-%m-%d 00:00", strtotime("-1 day")), _("today") => strftime("%Y-%m-%d 00:00", time()), _("2 days ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 days")), _("3 days ago") => strftime("%Y-%m-%d 00:00", strtotime("-3 days")), _("1 week ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 week")), _("2 weeks ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 weeks")), _("3 weeks ago") => strftime("%Y-%m-%d 00:00", strtotime("-3 weeks")), _("1 month ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 month")), _("2 months ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 months")), _("6 months ago") => strftime("%Y-%m-%d 00:00", strtotime("-6 months")), _("1 year ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 year")), _("-") => "", _("Select from and to...") => "", _("yesterday (whole day)") => strftime("%Y-%m-%d 00:00", strtotime("-1 day")).",".strftime("%Y-%m-%d 11:59", strtotime("-1 day")), _("today (whole day)") => strftime("%Y-%m-%d 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()), _("this month") => strftime("%Y-%m-01 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()), _("last month") => strftime("%Y-%m-01 00:00", strtotime("-1 month")).",".strftime("%Y-%m-01 00:00", time()), _("this year") => strftime("%Y-01-01 00:00", time()).",".strftime("%Y-%m-%d %H:%M", time()), _("forever") => "1970-01-01 00:00,".strftime("%Y-%m-%d %H:%M", time()));

		$to_presets = array (_("No restriction...") => "", _("yesterday") => strftime("%Y-%m-%d 11:59", strtotime("-1 day")), _("today") => strftime("%Y-%m-%d 11:59", time()), _("2 days ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 days")), _("3 days ago") => strftime("%Y-%m-%d 11:59", strtotime("-3 days")), _("1 week ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 week")), _("2 weeks ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 weeks")), _("3 weeks ago") => strftime("%Y-%m-%d 11:59", strtotime("-3 weeks")), _("1 month ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 month")), _("2 months ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 months")), _("6 months ago") => strftime("%Y-%m-%d 11:59", strtotime("-6 months")), _("1 year ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 year")), _("-") => "", _("Select from and to...") => "", _("yesterday (whole day)") => strftime("%Y-%m-%d 11:59", strtotime("-1 day")).",".strftime("%Y-%m-%d 00:00", strtotime("-1 day")), _("today (whole day)") => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-%m-%d 00:00", time()), _("this month") => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-%m-01 00:00", time()), _("last month") => strftime("%Y-%m-01 00:00", time()).",".strftime("%Y-%m-01 00:00", strtotime("-1 month")), _("this year") => strftime("%Y-%m-%d %H:%M", time()).",".strftime("%Y-01-01 00:00", time()), _("forever") => strftime("%Y-%m-%d %H:%M", time()).",1970-01-01 00:00");

		$html .= "<table>";
		$html .= "<tr>";
		$html .= "    <th>"._("From").":</th>";
		$html .= "    <td><input type='text' name='date_from' value='{$this->report_date_min}'></td>";
		$html .= "    <td>";
		$html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_from,this.form.date_to);\">";

		foreach ($from_presets as $label => $value)
		{
			//echo "<p>$value, $this->report_date_min </p>";
			$value == $this->report_date_min ? $selected = 'SELECTED' : $selected = '';
			$html .= "<option value=\"{$value}\" $selected>{$label}";
		}

		$html .= "    </select>\n";
		$html .= "    </td>\n";
		$html .= "</tr>\n";
		$html .= "<tr>\n";
		$html .= "    <th>"._("To").":</th>\n";
		$html .= "    <td><input type=\"text\" name=\"date_to\" value=\"{$this->report_date_max}\"></td>\n";
		$html .= "    <td>\n";
		$html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_to,this.form.date_from);\">\n";

		foreach ($to_presets as $label => $value)
		{
			$value == $this->report_date_max ? $selected = 'SELECTED' : $selected = '';
			$html .= "<option value=\"{$value}\" $selected>{$label}";
		}

		$html .= "    </select>\n";
		$html .= "    </td>\n";
		$html .= "</tr>\n";
		$html .= "</table>\n";
		return $html;

	}
	/** Process the date range selection UI */
	private function processDateRangeUI()
	{
		$this->report_date_min = $_REQUEST['date_from'];
		$this->report_date_max = $_REQUEST['date_to'];
	}

	/** Get the actual SQL fragment to restrict a report to a specific date
	 * @param $column The column in the database that must be higher or equal to
	 * the min date and lower or equal to the max date
	 * @return SQL AND clauses */
	public function getSqlDateConstraint($column = 'timestamp_in')
	{
		global $db;
		$column = $db->EscapeString($column);
		$sql = '';
		if ($date_min = $db->EscapeString($this->report_date_min))
		{
			$sql .= " AND $column >= '$date_min' ";
		}
		if ($date_max = $db->EscapeString($this->report_date_max))
		{
			$sql .= " AND $column <= '$date_max' ";
		}
		return $sql;
	}

	/** Get the actual SQL fragment to restrict a report to the specific node(s) selected
	 * @param $column The column in the database that must match the node_id
	 * @return SQL AND clauses */
	public function getSqlNodeConstraint($column)
	{
		global $db;
		$column = $db->EscapeString($column);
		$sql = '';
		if (count($this->report_selected_nodes) > 0)
		{
			$sql .= " AND (";
			$first = true;
			foreach ($this->report_selected_nodes as $node_id => $node)
			{
				$node_id = $db->EscapeString($node_id);
				$first ? $sql .= "" : $sql .= " OR ";
				$sql .= "$column = '$node_id'";
				$first = false;
			}
			$sql .= ") \n";
		}
		return $sql;
	}
	/** Get the actual SQL fragment to restrict a report to a specific network(s) selected
	 * @param $column The column in the database that must match the network_id
	 * @return SQL AND clauses */
	public function getSqlNetworkConstraint($column)
	{
		global $db;
		$column = $db->EscapeString($column);
		$sql = '';
		if (count($this->report_selected_networks) > 0)
		{
			$sql .= " AND (";
			$first = true;
			foreach ($this->report_selected_networks as $network_id => $network)
			{
				$network_id = $db->EscapeString($network_id);
				$first ? $sql .= "" : $sql .= " OR ";
				$sql .= "$column = '$network_id'";
				$first = false;
			}
			$sql .= ") \n";
		}
		return $sql;
	}
	/** Get the actual SQL fragment to restrict a report to the specific user(s) selected
	* @return SQL AND clauses */
	public function getSqlUserConstraint()
	{
		global $db;
		$column = $db->EscapeString($this->report_distinguish_users_by);
		$sql = '';
		if (count($this->report_selected_users) > 0)
		{
			$sql .= " AND (";
			$first = true;
			foreach ($this->report_selected_users as $id => $user)
			{
				$id = $db->EscapeString($id);
				$first ? $sql .= "" : $sql .= " OR ";
				$sql .= "$column = '$id'";
				$first = false;
			}
			$sql .= ") \n";
		}
		return $sql;
	}

	/** Get the actual SQL fragment to get the candidates rows from the connections table, 
	 * once obeying all the report configuration constraints.  Only connections
	 * with actuall data transferred is considered.  Connections is always
	 * joined to the nodes table, but not to network or users.
	 * @param $select_columns The selected columns, will be inserted between
	 * between SELECT and FROM
	 * @param $join_users true or false, Should we join with the users table?
	 * @return SQL select statemnt.  You can append additional AND and GROUP BY
	 * clauses */
	public function getSqlCandidateConnectionsQuery($select_columns = '*', $join_users = false)
	{
		$sql = '';
		$date_constraint = $this->getSqlDateConstraint('timestamp_in');
		$node_constraint = $this->getSqlNodeConstraint('connections.node_id');
		$network_constraint = $this->getSqlNetworkConstraint('nodes.network_id');
		$user_constraint = $this->getSqlUserConstraint();
		$join_users_sql = '';
		if ($join_users)
		{
			$join_users_sql = "JOIN users ON (connections.user_id = users.user_id)";
		}
		$sql .= "SELECT $select_columns \n";

		$sql .= "FROM connections  \n";
		$sql .= "JOIN nodes ON (connections.node_id = nodes.node_id) \n";
		$sql .= "$join_users_sql \n";
		$sql .= "WHERE (incoming!=0 OR outgoing!=0) \n";
		$sql .= " {$date_constraint} {$node_constraint} {$network_constraint} {$user_constraint}";
		return $sql;
	}

	/** Get the actual SQL fragment to get all the conn_id of the all users first successfull connections from the connections table.  Only connections
	 * with actuall data transferred is considered. It will ignore all report
	 * configuration except getDistinguishUsersBy() and selected users, because
	 * doing otherwise would not give the real first connection.
	 * @return SQL query */
	public function getSqlRealFirstConnectionsQuery($select_columns = '*', $join_users = false)
	{
		$sql = '';
		$distinguish_users_by = $this->getDistinguishUsersBy();
		$user_constraint = $this->getSqlUserConstraint();

		$sql .= "SELECT DISTINCT ON(connections.$distinguish_users_by) connections.conn_id  \n";
		$sql .= "FROM connections  \n";
		$sql .= "WHERE (incoming!=0 OR outgoing!=0) \n";
		$sql .= " {$user_constraint}";
		$sql .= "  ORDER BY connections.$distinguish_users_by, timestamp_in DESC";

		return $sql;
	}

	/** Get an interface to pick to which nodes the statistics apply.
	* @return html markup
	*/
	private function getSelectedNodesUI()
	{
		global $db;
		$html = '';
		$name = "statistics_selected_nodes[]";
		$user = User :: getCurrentUser();
		if ($user->isSuperAdmin())
		{
			$sql_join = '';
		}
		else
		{
			$user_id = $db->EscapeString($user->getId());
			$sql_join = " JOIN node_stakeholders ON (nodes.node_id=node_stakeholders.node_id AND user_id='$user_id') ";
		}
		$sql = "SELECT node_id, name from nodes $sql_join WHERE 1=1 ORDER BY node_id";
		$node_rows = null;
		$db->ExecSql($sql, $node_rows, false);
		$html .= "<select multiple size = 6 name='$name'>\n";

		/*count($this->report_selected_nodes)==0?$selected=' SELECTED ':$selected='';
					$html.= "<option value='' $selected>"._("Statistics for all nodes")."</option>\n";
		*/
		if ($node_rows != null)
		{
			foreach ($node_rows as $node_row)
			{
				$html .= "<option ";
				if (array_key_exists($node_row['node_id'], $this->report_selected_nodes))
				{
					$html .= " SELECTED ";
				}

				$nom = $node_row['node_id'].": ".$node_row['name'];
				$nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
				$primary_key = htmlentities($node_row['node_id'], ENT_QUOTES, 'UTF-8');
				$html .= "value='$primary_key'>$nom</option>\n";
			}
		}
		$html .= "</select>\n";
		return $html;
	}

	/** Get the select node interface.
	 */
	private function processSelectedNodesUI()
	{
		$name = "statistics_selected_nodes";
		//pretty_print_r($_REQUEST[$name]);
		$this->report_selected_nodes = array ();
		if (!empty ($_REQUEST[$name]))
		{
			foreach ($_REQUEST[$name] as $value)
			{
				if (!empty ($value))
					$this->report_selected_nodes[$value] = Node :: getObject($value);
			}
		}
	}

	/** Get the selected nodes for the reports.
	@return An array of Node objects, with the node_id as the key, or an empty array */
	public function getSelectedNodes()
	{
		return $this->report_selected_nodes;
	}

	/** UI for selecting how the database determines if a user is unique 
	 * @return html markup */
	private function getDistinguishUsersByUI()
	{
		$html = '';

		/*      $html .= " < input type = \ "radio\" name=\"group_connections\" value=\"\"";
		$html .= empty ($_REQUEST['group_connections']) ? 'CHECKED' : '';
		$html .= ">"._("No")."<br>";
		*/
		$html .= "    <select name=\"distinguish_users_by\">";

		foreach ($this->user_distinguish_by_options as $value => $label)
		{
			//echo "<p>$value, $this->report_date_min </p>";
			$value == $this->report_distinguish_users_by ? $selected = 'SELECTED' : $selected = '';
			$html .= "<option value=\"{$value}\" $selected>{$label}";
		}

		$html .= "    </select>\n";

		return $html;
	}

	/** Process the date range selection UI */
	private function processDistinguishUsersByUI()
	{
		if (!isset ($this->user_distinguish_by_options[$_REQUEST['distinguish_users_by']]))
			throw new exception(_("Invalid parameter"));
		$this->report_distinguish_users_by = $_REQUEST['distinguish_users_by'];
	}

	/** Get how are users to be ddistinguished
	 * @return Either 'user_id' our 'user_mac' */
	public function getDistinguishUsersBy()
	{
		return $this->report_distinguish_users_by;
	}

	/** UI for selecting to which users to restrict the reports
	 * @todo:  Allow to select more than one user
	 * @return html markup */
	private function getSelectedUsersUI()
	{
		$html = '';
		$value = '';
		foreach ($this->report_selected_users as $id => $user)
		{
			if ($this->report_distinguish_users_by == 'user_id')
			{
				$value .= $user->getUsername();
			}
			else
			{
				$value .= $id;
			}
		}
		$html .= "    <input type='text' name=\"stats_selected_users\" value='$value'>";

		$type_caption = $this->user_distinguish_by_options[$this->report_distinguish_users_by];
		$html .= " $type_caption\n";

		return $html;
	}

	/** Process the users selection UI 
	 * 	@todo:  Allow to select more than one user*/
	private function processSelectedUsersUI()
	{
		$this->report_selected_users = array ();
		$user_obj = null;
		if ($this->report_distinguish_users_by == 'user_id')
		{
			global $db;
			$username = $db->EscapeString($_REQUEST['stats_selected_users']);
			$row = null;
			$db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE username='$username'", $row);
			if ($row)
			{
				$user_id = $row['user_id'];
				$user_obj = User :: getObject($user_id);
				$this->report_selected_users[$user_id] = $user_obj;
			}
		}
		else
		{
			//We have a MAC address
			if (!empty ($_REQUEST['stats_selected_users']))
				$this->report_selected_users[$_REQUEST['stats_selected_users']] = null;
		}

	}

	/** Get the selected users for the reports.
	@return An empty array or an array of user_id or MAC addresses as the key and a User object as the value, unless it's a MAC in which case the value is null
	*/
	public function getSelectedUsers()
	{
		return $this->report_selected_users;
	}

	/** Get the selected nodes for the reports.
	@return An array of Network objects, with the network_id as the key, or an empty array */
	public function getSelectedNetworks()
	{
		return $this->report_selected_networks;
	}

	/** UI for selecting how the database determines if a user is unique 
	 * @return html markup */
	private function getSelectedReportsUI()
	{
		$html = '';
		$html .= "<ul>\n";
		foreach (self :: getAvailableReports() as $key => $name)
		{
			array_key_exists($key, $this->report_selected_reports) ? $checked = ' CHECKED ' : $checked = '';
			$html .= "<li><input type='checkbox' name='$key' $checked /> $name</li>\n";
		}
		$html .= "</ul>\n";

		return $html;
	}

	/** Process the date range selection UI */
	private function processSelectedReportsUI()
	{
		$this->report_selected_reports = array ();
		foreach (self :: getAvailableReports() as $key => $name)
		{
			if (array_key_exists($key, $_REQUEST))
			{
				$this->report_selected_reports[$key] = call_user_func(array ($key, 'getObject'), $key, $this);
			}
		}
		//pretty_print_r($this->report_selected_reports);
	}

	public function getAdminUI()
	{
		$html = '';
		$html .= "<h3>"._("Report configuration")."</h3>\n";
		$html .= "<div class='admin_container'>\n";

		// Network
		$html .= "<div class='admin_section_container'>\n";
		//$html .= "<div class='admin_section_title'>".." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= Network :: getSelectNetworkUI('Statistics', reset($this->report_selected_networks));
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Date range
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Restrict the time range for which statistics will be computed")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= $this->getDateRangeUI();
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Selected nodes
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Restrict stats to the following nodes")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= $this->getSelectedNodesUI();
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Unique user criteria
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Distinguish users by")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= $this->getDistinguishUsersByUI();
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Selected users
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Restrict stats to the selected users")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= $this->getSelectedUsersUI();
		$html .= "</div>\n";
		$html .= "</div>\n";

		// Reports
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Selected reports")." : </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= $this->getSelectedReportsUI();
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		return $html;
	}

	public function processAdminUI()
	{
		$network = Network :: processSelectNetworkUI('Statistics');
		$this->report_selected_networks = array ();
		$this->report_selected_networks[$network->getId()] = $network;
		$this->processDateRangeUI();
		$this->processSelectedNodesUI();
		$this->processDistinguishUsersByUI();
		$this->processSelectedUsersUI();
		$this->processSelectedReportsUI();
	}

	/** Get the output of all the selected reports
	 * @return html markup */
	public function getReportUI()
	{
		$html = '';
		foreach ($this->report_selected_reports as $classname => $report)
		{
			$html .= $report->getReportUI();
			//$html.='<hr />';
		}

		return $html;
	}

	/************* ALL methods from this point on are deprecated  ********************/

	/**
	 * Find out the date of the most recent successfull (meaning with data transferred) connection to a HotSpot.
	 * @param $node_id Optionnal.  The id of the node used for which you want the last successfull connection date
	 * @return Textual date
	 */
	/*	function getLastConnDate($node_id = null)
		{
			global $db;
	
			if ($node_id != null)
			{
				$node_id = $db->EscapeString($node_id);
				$sql_node = " AND connections.node_id='$node_id'";
			}
			else
			{
				$sql_node = "";
			}
	
			$db->ExecSqlUniqueRes("SELECT timestamp_in FROM connections WHERE incoming!=0 $sql_node ORDER BY timestamp_in DESC LIMIT 1", $row, false);
			return $row['timestamp_in'];
		}
	*/

	/*	public static function getRegistrationsPerNode($from = '', $to = '')
		{
			global $db;
	
			if ($from != '' && $to != '')
				$date_constraint = "AND timestamp_in BETWEEN '$from' AND '$to'";
			else
				$date_constraint = '';
	
			$db->ExecSql("SELECT nodes.name,connections.node_id,COUNT(user_id) as registrations FROM connections,nodes WHERE timestamp_in IN (SELECT MIN(timestamp_in) as first_connection FROM connections GROUP BY user_id) ${date_constraint} AND nodes.node_id=connections.node_id GROUP BY connections.node_id,nodes.name ORDER BY registrations DESC", $results, false);
			return $results;
		}*/

} //End class
?>