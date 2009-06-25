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
 * Network status page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Define default sort parameter
 */
define('DEFAULT_SORT_BY_PARAM', "name");

/**
 * Load required files
 */
require_once (dirname(__FILE__) . '/include/common.php');
require_once ('classes/SmartyWifidog.php');
require_once ('classes/Node.php');
require_once ('classes/Utils.php');
$smarty = SmartyWifidog :: getObject();
$db = AbstractDb :: getObject();

// Set the sort parameter, defaults to name
if (empty ($_REQUEST["sort_by"])) {
    $sort_by_param = DEFAULT_SORT_BY_PARAM;
    $sort_by_using_sql = true;
}
else {
    // Validate sort parameters
    switch ($_REQUEST["sort_by"]) {
        // SQL sort parameters
        case "last_heartbeat_user_agent" :
        case "name" :
        case "creation_date" :
        case "gw_id" :
            // Fall-through valid parameters
            $sort_by_param = $_REQUEST["sort_by"];
            $sort_by_using_sql = true;
            break;
            // Abstraction-driven sort parameters
        case "node_id" :
        case "num_online_users" :
            $sort_by_param = $_REQUEST["sort_by"];
            $sort_by_using_sql = false;
            break;
        default :
            $sort_by_param = DEFAULT_SORT_BY_PARAM;
            $sort_by_using_sql = true;
    }
}

// Sort according to above instructions
if ($sort_by_using_sql === true) {
    // Check if ordering should ignore uppper and lower case
    if ($sort_by_param == "name" || $sort_by_param == "node_id") {
        $sort_by_param_sql = "lower(" . $sort_by_param . ")";
    }
    else {
        $sort_by_param_sql = $sort_by_param;
    }
} else {
	$sort_by_param_sql = DEFAULT_SORT_BY_PARAM;
}

if (isset ($_REQUEST["network_id"])) {
	$network_id = $_REQUEST["network_id"];
    if($network_id == ""){
        $network = null;
    } else {
        try {
            $network = Network::getObject($network_id);
        } catch (Exception $e) {
            $network = Network::getDefaultNetwork();
        }
    }
} else {
	$network = Network::getDefaultNetwork();
}
$network_id_sql = $network===null?"":"AND network_id = '{$network->getId()}'";

$sql = "SELECT node_id, gw_id, name, last_heartbeat_user_agent, (CURRENT_TIMESTAMP-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS online, creation_date, node_deployment_status, last_heartbeat_wifidog_uptime, last_heartbeat_sys_uptime FROM nodes WHERE node_deployment_status != 'PERMANENTLY_CLOSED' $network_id_sql ORDER BY {$sort_by_param_sql}";
$nodes_results = null;
$db->execSql($sql, $nodes_results, false);

//Possible to select empty networks, not an error
if ($nodes_results === null)
    $nodes_results = array();
//throw new Exception(_("No nodes could not be found in the database"));


$deploymentStatuses = array (
"DEPLOYED" => _("Deployed"
), "IN_PLANNING" => _("In planning"), "IN_TESTING" => _("In testing"), "NON_WIFIDOG_NODE" => _("Non-Wifidog node"), "PERMANENTLY_CLOSED" => _("Permanently closed"), "TEMPORARILY_CLOSED" => _("Temporarily closed"));

$nodes_list = array ();
foreach ($nodes_results as $node_row) {
    $node = Node :: getObject($node_row['node_id']);
    $node_row['duration'] = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
    if($node_row['last_heartbeat_sys_uptime']) {
        $previousRemainder = $node_row['last_heartbeat_sys_uptime'];
        $remainder = $previousRemainder % (3600*24);
        $node_row['wifidog_uptime']['days'] = ($previousRemainder - $remainder)  / (3600*24);
        $previousRemainder = $remainder;
        $remainder = $previousRemainder % (3600);
        $node_row['wifidog_uptime']['hours'] = ($previousRemainder - $remainder)  / (3600);
        $previousRemainder = $remainder;
        $remainder = $previousRemainder % (60);
        $node_row['wifidog_uptime']['minutes'] = ($previousRemainder - $remainder)  / (60);
    }
    $node_row['num_online_users'] = $node->getNumOnlineUsers();
    $nodeDeploymentStatus = $node_row['node_deployment_status'];
    $node_row['node_deployment_status'] = $deploymentStatuses["$nodeDeploymentStatus"];

    if ($node->getAllowsPublicStats() && $node->hasPublicStats())
        $node_row['public_stats_file'] = $node->getPublicStatsFile();

    $nodes_list[] = $node_row;
}

// Sort using PHP
if ($sort_by_using_sql === false) {
    // Using natural-sort algorithm .
    switch ($sort_by_param) {
        case "node_id" :
            Utils :: natsort2d($nodes_list, "node_id");
            break;
        case "num_online_users" :
            Utils :: natsort2d($nodes_list, "num_online_users");
            break;
    }
}

// Pass values to Smarty
$smarty->assign('selectNetworkUI', Network::getSelectUI('network_id', array('preSelectedObject' => $network, 'allowEmpty' => true, 'nullCaptionString' => _("All"), 'onChange' => "submit.click();") ) . (count(Network::getAllNetworks()) > 1 ? '<input class="submit" type="submit" name="submit" value="' . _("Change network") . '">' : ""));
if ($network !== null ) {
    $smarty->assign('selectedNetwork', $network->getName());
} else {
    $smarty->assign('allNetworks', _("All networks"));
}
$smarty->assign("nodes", $nodes_list);
$smarty->assign("sort_by_param", $sort_by_param);

require_once ('classes/MainUI.php');

$ui = MainUI :: getObject();
$ui->addContent('main_area_middle', $smarty->fetch("templates/node_list.html"));
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
