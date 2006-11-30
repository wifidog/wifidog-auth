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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once ('include/common.php');

/**
 * Gives various statistics about the status of the network or of a specific node
 *
 * @package    WiFiDogAuthServer
 * @subpackage Statistics
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class Statistics {
    /** An array of the of the selected networks.
     * The key is the network_id, the value is the Network object. */
    private $report_selected_networks = array ();
    private $report_date_min; /**< Minimum timestamp */
    /** Maximum timestamp */
    private $report_date_max;
    /** How to distinguish unique users.  Either 'user_mac' (MAC addresses) or 'user_id' */
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
    function __construct() {
        $this->user_distinguish_by_options = array (
            'user_id' => _("Usernames"
        ), 'user_mac' => _("MAC addresses"));

    }

    /**
     * Get the list of available report types
     *
     * @return array An array of class names
     */
    public function getAvailableReports() {
        $dir = WIFIDOG_ABS_FILE_PATH . 'classes/StatisticReport';
        if ($handle = opendir($dir)) {
            $tab = Array ();
            /* This is the correct way to loop over the directory. */
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $matches = null;
                    if (preg_match("/^(.*)\.php$/", $file, $matches) > 0) {
                        //pretty_print_r($matches);
                        $filename = $dir . '/' . $matches[0];
                        require_once ($filename);
                        $classname = $matches[1];
                        if (call_user_func(array (
                                $classname,
                                'isAvailable'
                            ), $this)) {
                            $name = call_user_func(array (
                                $classname,
                                'getReportName'
                            ), $this);

                            $tab[$classname] = $name;
                        }
                    }
                }
            }
            closedir($handle);
        } else {
            throw new Exception(_('Unable to open directory ') . $dir);
        }
        $tab = str_ireplace('.php', '', $tab);
        asort($tab);
        return $tab;
    }

    /**
     * UI for selecting the date ragnge for the report
     *
     * @return string HTML markup
     */
    private function getDateRangeUI() {
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
        $from_presets = array (
            _("No restriction..."
        ) => "", _("yesterday") => strftime("%Y-%m-%d 00:00", strtotime("-1 day")), _("today") => strftime("%Y-%m-%d 00:00", time()), _("2 days ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 days")), _("3 days ago") => strftime("%Y-%m-%d 00:00", strtotime("-3 days")), _("1 week ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 week")), _("2 weeks ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 weeks")), _("3 weeks ago") => strftime("%Y-%m-%d 00:00", strtotime("-3 weeks")), _("1 month ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 month")), _("2 months ago") => strftime("%Y-%m-%d 00:00", strtotime("-2 months")), _("6 months ago") => strftime("%Y-%m-%d 00:00", strtotime("-6 months")), _("1 year ago") => strftime("%Y-%m-%d 00:00", strtotime("-1 year")), _("-") => "", _("Select from and to...") => "", _("yesterday (whole day)") => strftime("%Y-%m-%d 00:00", strtotime("-1 day")) . "," . strftime("%Y-%m-%d 11:59", strtotime("-1 day")), _("today (whole day)") => strftime("%Y-%m-%d 00:00", time()) . "," . strftime("%Y-%m-%d %H:%M", time()), _("this month") => strftime("%Y-%m-01 00:00", time()) . "," . strftime("%Y-%m-%d %H:%M", time()), _("last month") => strftime("%Y-%m-01 00:00", strtotime("-1 month")) . "," . strftime("%Y-%m-01 00:00", time()), _("this year") => strftime("%Y-01-01 00:00", time()) . "," . strftime("%Y-%m-%d %H:%M", time()), _("forever") => "1970-01-01 00:00," . strftime("%Y-%m-%d %H:%M", time()));

        $to_presets = array (
            _("No restriction..."
        ) => "", _("yesterday") => strftime("%Y-%m-%d 11:59", strtotime("-1 day")), _("today") => strftime("%Y-%m-%d 11:59", time()), _("2 days ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 days")), _("3 days ago") => strftime("%Y-%m-%d 11:59", strtotime("-3 days")), _("1 week ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 week")), _("2 weeks ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 weeks")), _("3 weeks ago") => strftime("%Y-%m-%d 11:59", strtotime("-3 weeks")), _("1 month ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 month")), _("2 months ago") => strftime("%Y-%m-%d 11:59", strtotime("-2 months")), _("6 months ago") => strftime("%Y-%m-%d 11:59", strtotime("-6 months")), _("1 year ago") => strftime("%Y-%m-%d 11:59", strtotime("-1 year")), _("-") => "", _("Select from and to...") => "", _("yesterday (whole day)") => strftime("%Y-%m-%d 11:59", strtotime("-1 day")) . "," . strftime("%Y-%m-%d 00:00", strtotime("-1 day")), _("today (whole day)") => strftime("%Y-%m-%d %H:%M", time()) . "," . strftime("%Y-%m-%d 00:00", time()), _("this month") => strftime("%Y-%m-%d %H:%M", time()) . "," . strftime("%Y-%m-01 00:00", time()), _("last month") => strftime("%Y-%m-01 00:00", time()) . "," . strftime("%Y-%m-01 00:00", strtotime("-1 month")), _("this year") => strftime("%Y-%m-%d %H:%M", time()) . "," . strftime("%Y-01-01 00:00", time()), _("forever") => strftime("%Y-%m-%d %H:%M", time()) . ",1970-01-01 00:00");

        $html .= "<table class='admin_element_list'>";
        $html .= "<tr class='admin_element_item_container'>";
        $html .= "    <th class='admin_element_label'>" . _("From") . ":</th>";
        $html .= "    <td class='admin_element_data'><input type='text' name='date_from' value='{$this->report_date_min}'></td>";
        $html .= "    <td class='admin_element_tools'>";
        $html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_from,this.form.date_to);\">";

        foreach ($from_presets as $label => $value) {
            //echo "<p>$value, $this->report_date_min </p>";
            $value == $this->report_date_min ? $selected = 'SELECTED' : $selected = '';
            $html .= "<option value=\"{$value}\" $selected>{$label}";
        }

        $html .= "    </select>\n";
        $html .= "    </td>\n";
        $html .= "</tr>\n";
        $html .= "<tr class='admin_element_item_container'>\n";
        $html .= "    <th class='admin_element_label'>" . _("To") . ":</th>\n";
        $html .= "    <td class='admin_element_data'><input type=\"text\" name=\"date_to\" value=\"{$this->report_date_max}\"></td>\n";
        $html .= "    <td class='admin_element_data'>\n";
        $html .= "    <select onChange=\"javascript:change_value(this.value,this.form.date_to,this.form.date_from);\">\n";

        foreach ($to_presets as $label => $value) {
            $value == $this->report_date_max ? $selected = 'SELECTED' : $selected = '';
            $html .= "<option value=\"{$value}\" $selected>{$label}";
        }

        $html .= "    </select>\n";
        $html .= "    </td>\n";
        $html .= "</tr>\n";
        $html .= "</table>\n";
        return $html;

    }
    /**
     * Process the date range selection UI
     *
     * @return void
     */
    private function processDateRangeUI() {
        if (isset ($_REQUEST['date_from'])) {
            $this->report_date_min = $_REQUEST['date_from'];
        }

        if (isset ($_REQUEST['date_to'])) {
            $this->report_date_max = $_REQUEST['date_to'];
        }
    }

    /**
     * Get the actual SQL fragment to restrict a report to a specific date
     *
     * @param string $column The column in the database that must be higher or equal to
     * the min date and lower or equal to the max date
     * @return string SQL AND clauses
     */
    public function getSqlDateConstraint($column = 'timestamp_in') {
        $db = AbstractDb :: getObject();
        $column = $db->escapeString($column);
        $sql = '';
        if ($date_min = $db->escapeString($this->report_date_min)) {
            $sql .= " AND $column >= '$date_min' ";
        }
        if ($date_max = $db->escapeString($this->report_date_max)) {
            $sql .= " AND $column <= '$date_max' ";
        }
        return $sql;
    }

    /**
     * Get the actual SQL fragment to restrict a report to the specific
     * node(s) selected
     *
     * @param string $column The column in the database that must match the node_id
     * @return string SQL AND clauses
     */
    public function getSqlNodeConstraint($column) {
        $db = AbstractDb :: getObject();
        $column = $db->escapeString($column);
        $sql = '';
        if (count($this->report_selected_nodes) > 0) {
            $sql .= " AND (";
            $first = true;
            foreach ($this->report_selected_nodes as $node_id => $node) {
                $node_id = $db->escapeString($node_id);
                $first ? $sql .= "" : $sql .= " OR ";
                $sql .= "$column = '$node_id'";
                $first = false;
            }
            $sql .= ") \n";
        }
        return $sql;
    }
    /**
     * Get the actual SQL fragment to restrict a report to a specific network(s)
     * selected
     * @param string $column The column in the database that must match the network_id
     * @return string SQL AND clauses
     */
    public function getSqlNetworkConstraint($column) {
        $db = AbstractDb :: getObject();
        $column = $db->escapeString($column);
        $sql = '';
        if (count($this->report_selected_networks) > 0) {
            $sql .= " AND (";
            $first = true;
            foreach ($this->report_selected_networks as $network_id => $network) {
                $network_id = $db->escapeString($network_id);
                $first ? $sql .= "" : $sql .= " OR ";
                $sql .= "$column = '$network_id'";
                $first = false;
            }
            $sql .= ") \n";
        }
        return $sql;
    }
    /**
     * Get the actual SQL fragment to restrict a report to the specific
     * user(s) selected
     *
     * @return string SQL AND clauses
     */
    public function getSqlUserConstraint() {
        $db = AbstractDb :: getObject();
        $column = $db->escapeString($this->report_distinguish_users_by);
        $sql = '';
        if (count($this->report_selected_users) > 0) {
            $sql .= " AND (";
            $first = true;
            foreach ($this->report_selected_users as $id => $user) {
                $id = $db->escapeString($id);
                $first ? $sql .= "" : $sql .= " OR ";
                $sql .= "connections.$column = '$id'";
                $first = false;
            }
            $sql .= ") \n";
        }
        return $sql;
    }

    /**
     * Get the actual SQL fragment to get the candidates rows from the connections table,
     * once obeying all the report configuration constraints.  Only connections
     * with actuall data transferred is considered.  Connections is always
     * joined to the nodes table, but not to network or users.
     *
     * @param string $select_columns The selected columns, will be inserted between
     * between SELECT and FROM
     * @param string $join_users true or false, Should we join with the users table?
     * @return string SQL select statemnt.  You can append additional AND and GROUP BY
     * clauses
     */
    public function getSqlCandidateConnectionsQuery($select_columns = '*', $join_users = false) {
        $sql = '';
        $date_constraint = $this->getSqlDateConstraint('timestamp_in');
        $node_constraint = $this->getSqlNodeConstraint('connections.node_id');
        $network_constraint = $this->getSqlNetworkConstraint('nodes.network_id');
        $user_constraint = $this->getSqlUserConstraint();
        $join_users_sql = '';
        if ($join_users || !empty ($user_constraint)) {
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

    /**
     * Get the actual SQL fragment to get all the conn_id of the all users first successfull connections from the connections table.  Only connections
     * with actuall data transferred is considered. It will ignore all report
     * configuration except getDistinguishUsersBy() and selected users, because
     * doing otherwise would not give the real first connection.
     *
     * @return string SQL query
     */
    public function getSqlRealFirstConnectionsQuery($select_columns = '*', $join_users = false) {
        $sql = '';
        $distinguish_users_by = $this->getDistinguishUsersBy();
        $user_constraint = $this->getSqlUserConstraint();
        $join_users_sql = '';
        if ($join_users || !empty ($user_constraint)) {
            $join_users_sql = "JOIN users ON (connections.user_id = users.user_id)";
        }
        $sql .= "SELECT DISTINCT ON(connections.$distinguish_users_by) $select_columns  \n";
        $sql .= "FROM connections  \n";
        $sql .= "$join_users_sql \n";
        $sql .= "WHERE (incoming!=0 OR outgoing!=0) \n";
        $sql .= " {$user_constraint}";
        $sql .= "  ORDER BY connections.$distinguish_users_by, timestamp_in";

        return $sql;
    }

    /**
     * Get an interface to pick to which nodes the statistics apply.
     *
     * @return string HTML markup
     *
     * @access private
     */
    private function getSelectedNodesUI() {

        $db = AbstractDb :: getObject();

        // Init values
        $html = '';

        $name = "statistics_selected_nodes[]";
        $user = User :: getCurrentUser();

        if (!isset ($user)) {
            throw new Exception(_('Access denied!'));
        } else
            if ((!$user->isSuperAdmin() && !$user->isOwner()) || $user->isNobody()) {
                throw new Exception(_('Access denied!'));
            }

        if ($user->isSuperAdmin()) {
            $sql_join = '';
        } else {
            $user_id = $db->escapeString($user->getId());
            $sql_join = " JOIN node_stakeholders ON (nodes.node_id=node_stakeholders.node_id AND user_id='$user_id') ";
        }
        $selectedNodes = $this->getSelectedNodes();
        $sql = "SELECT nodes.node_id, nodes.name from nodes $sql_join WHERE 1=1 ORDER BY lower(nodes.node_id)";
        $html .= Node :: getSelectNodeUI($name, $sql_join, null, $selectedNodes, "select_multiple");
        return $html;
    }

    /**
     * Get the select node interface.
     */
    private function processSelectedNodesUI() {
        $name = "statistics_selected_nodes";
        //pretty_print_r($_REQUEST[$name]);
        $this->report_selected_nodes = array ();
        if (!empty ($_REQUEST[$name])) {
            foreach ($_REQUEST[$name] as $value) {
                if (!empty ($value))
                    $this->report_selected_nodes[$value] = Node :: getObject($value);
            }
        }
    }

    /**
     * Get the selected nodes for the reports.
     * @return array An array of Node objects, with the node_id as the key, or
     * an empty array
     */
    public function getSelectedNodes() {
        return $this->report_selected_nodes;
    }

    /**
     * UI for selecting how the database determines if a user is unique
     *
     * @return string HTML markup
     */
    private function getDistinguishUsersByUI() {
        $html = '';

        /*      $html .= " < input type = \ "radio\" name=\"group_connections\" value=\"\"";
        $html .= empty ($_REQUEST['group_connections']) ? 'CHECKED' : '';
        $html .= ">"._("No")."<br>";
        */
        $html .= "    <select name=\"distinguish_users_by\">";

        foreach ($this->user_distinguish_by_options as $value => $label) {
            //echo "<p>$value, $this->report_date_min </p>";
            $value == $this->report_distinguish_users_by ? $selected = 'SELECTED' : $selected = '';
            $html .= "<option value=\"{$value}\" $selected>{$label}";
        }

        $html .= "    </select>\n";

        return $html;
    }

    /**
     * Process the date range selection UI
     */
    private function processDistinguishUsersByUI() {
        if (!isset ($this->user_distinguish_by_options[$_REQUEST['distinguish_users_by']]))
            throw new exception(_("Invalid parameter"));
        $this->report_distinguish_users_by = $_REQUEST['distinguish_users_by'];
    }

    /**
     * Get how are users to be ddistinguished
     *
     * @return string Either 'user_id' our 'user_mac'
     */
    public function getDistinguishUsersBy() {
        return $this->report_distinguish_users_by;
    }

    /**
     * UI for selecting to which users to restrict the reports
     *
     * @return string HTML markup
     *
     * @todo Allow to select more than one user
     */
    private function getSelectedUsersUI() {
        $html = '';
        $value = '';
        foreach ($this->report_selected_users as $id => $user) {
            if ($this->report_distinguish_users_by == 'user_id') {
                $value .= $user->getUsername();
            } else {
                $value .= $id;
            }
        }
        $html .= "    <input type='text' name=\"stats_selected_users\" value='$value'>";

        $type_caption = _("Username or MAC address, depending on selection above");
        $html .= " $type_caption\n";

        return $html;
    }

    /**
     * Process the users selection UI
     *
     * @todo Allow to select more than one user
     */
    private function processSelectedUsersUI() {
        $this->report_selected_users = array ();
        $user_obj = null;
        if (!empty ($_REQUEST['stats_selected_users'])) {
            if ($this->report_distinguish_users_by == 'user_id') {
                $db = AbstractDb :: getObject();
                $username = $db->escapeString($_REQUEST['stats_selected_users']);
                $row = null;
                $db->execSqlUniqueRes("SELECT user_id FROM users WHERE username='$username'", $row, false);
                if ($row) {
                    $user_id = $row['user_id'];
                    $user_obj = User :: getObject($user_id);
                    $this->report_selected_users[$user_id] = $user_obj;
                }
            } else {
                //We have a MAC address
                if (!empty ($_REQUEST['stats_selected_users']))
                    $this->report_selected_users[$_REQUEST['stats_selected_users']] = null;
            }
        }
    }

    /**
     * Get the selected users for the reports.
     *
     * @return array An empty array or an array of user_id or MAC addresses as the
     * key and a User object as the value, unless it's a MAC in which case the
     * value is null
     */
    public function getSelectedUsers() {
        return $this->report_selected_users;
    }

    /**
     * Get the selected nodes for the reports.
     *
     * @return array An array of Network objects, with the network_id as the
     * key, or an empty array
     */
    public function getSelectedNetworks() {
        return $this->report_selected_networks;
    }

    /**
     * UI for selecting how the database determines if a user is unique
     *
     * @return string HTML markup
     */
    private function getSelectedReportsUI() {
        $html = '';
        $html .= "<ul class='admin_element_list'>\n";

        foreach (self :: getAvailableReports() as $key => $name) {
            array_key_exists($key, $this->report_selected_reports) ? $checked = ' CHECKED ' : $checked = '';
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_tools'><input type='checkbox' name='$key' $checked /></div>\n";
            $html .= "<div class='admin_element_label'>$name</div>\n";

            $html .= "</li>\n";
        }
        $html .= "</ul>\n";

        return $html;
    }

    /**
     * Process the date range selection UI
     */
    private function processSelectedReportsUI() {
        $this->report_selected_reports = array ();
        foreach (self :: getAvailableReports() as $key => $name) {
            if (array_key_exists($key, $_REQUEST)) {
                $this->report_selected_reports[$key] = call_user_func(array (
                    $key,
                    'getObject'
                ), $key, $this);
            }
        }
        //pretty_print_r($this->report_selected_reports);
    }

    public function getAdminUI() {
        $html = '';
        $html .= "<fieldset class='admin_container'>\n";
        $html .= "<legend>" . _("Report configuration") . "</legend>\n";
        $html .= "<ul class='admin_element_list'>\n";
        // Network
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= Network :: getSelectNetworkUI('Statistics', reset($this->report_selected_networks), null, User :: getCurrentUser()->isSuperAdmin());
        $html .= "</div>\n";
        $html .= "</li>\n";

        // Date range
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . _("Restrict the time range for which statistics will be computed") . " : </legend>\n";
        $html .= $this->getDateRangeUI();
        $html .= "</fieldset>\n";
        $html .= "</li>\n";

        // Selected nodes
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . _("Restrict stats to the following nodes") . " : </legend>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $this->getSelectedNodesUI();
        $html .= "</div>\n";
        $html .= "</fieldset>\n";
        $html .= "</li>\n";

        // Unique user criteria
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . _("Distinguish users by") . " : </legend>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $this->getDistinguishUsersByUI();
        $html .= "</div>\n";
        $html .= "</fieldset>\n";
        $html .= "</li>\n";
        // Selected users
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset class='admin_element_group'>\n";
        $html .= "<legend>" . _("Restrict stats to the selected users") . " : </legend>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $this->getSelectedUsersUI();
        $html .= "</div>\n";
        $html .= "</fieldset class='admin_element_group'>\n";
        $html .= "</li>\n";
        // Reports
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<fieldset>\n";
        $html .= "<legend>" . _("Selected reports") . " : </legend>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= $this->getSelectedReportsUI();
        $html .= "</div>\n";
        $html .= "</fieldset>\n";
        $html .= "</ul>\n";
        $html .= "</fieldset>\n";
        $html .= "</li>\n";
        return $html;
    }

    public function processAdminUI() {
        $network = Network :: processSelectNetworkUI('Statistics');
        if ($network) {
            $this->report_selected_networks[$network->getId()] = $network;
        } else {
            Security :: requireAdmin();
        }
        $this->processDateRangeUI();
        $this->processSelectedNodesUI();
        $this->processDistinguishUsersByUI();
        $this->processSelectedUsersUI();
        $this->processSelectedReportsUI();
    }

    /**
     * Get the output of all the selected reports
     *
     * @return string HTML markup
     */
    public function getReportUI() {
        $html = '';
        foreach ($this->report_selected_reports as $classname => $report) {
            $html .= $report->getReportUI();
            //$html.='<hr />';
        }

        return $html;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */