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
  /**@file node_list.php
   * Network status page
   * @author Copyright (C) 2004 Benoit Grégoire
   */

define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';
require_once BASEPATH.'classes/Statistics.php';
require_once BASEPATH.'classes/SmartyWifidog.php';

$smarty = new SmartyWifidog;
$smarty->SetTemplateDir('templates/');

$style = new Style();
$stats = new Statistics();

$db->ExecSql("SELECT node_id, name, last_heartbeat_user_agent, (NOW()-last_heartbeat_timestamp) AS since_last_heartbeat, last_heartbeat_ip, CASE WHEN ((NOW()-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up, creation_date FROM nodes ORDER BY node_id", $node_results, false);

foreach($node_results as $node_row) {
    $node_row['duration'] = $db->GetDurationArrayFromIntervalStr($node_row['since_last_heartbeat']);
    $node_row['num_online_users'] = $stats->getNumOnlineUsers($node_row['node_id']);
    $smarty->append("nodes", $node_row);
}

echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' node list');
$smarty->display("node_list.html");
echo $style->GetFooter();
?>
