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

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Statistics.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/SmartyWifidog.php';

$security = new Security();
$security->requireAdmin();

$stats = new Statistics();
$smarty = new SmartyWifidog;
$session = new Session;

include BASEPATH.'include/language.php';


$smarty->assign("total_users",  $stats->getNumUsers());
$smarty->assign("total_valid",  $stats->getNumValidUsers());

$results = null;
$db->ExecSql("SELECT COUNT(users) AS num_users, date_trunc('month', reg_date) AS month FROM users  WHERE account_status = ".ACCOUNT_STATUS_ALLOWED." GROUP BY date_trunc('month', reg_date) ORDER BY month DESC",$results, false);
if ($results != null) {
    $smarty->assign("registrations", $results);
}


$results = null;
$db->ExecSql("SELECT COUNT(DISTINCT node_id) AS num_hotspots_visited, user_id FROM users NATURAL JOIN connections WHERE (incoming!=0 OR outgoing!=0) GROUP BY user_id ORDER BY num_hotspots_visited DESC LIMIT 10", $results, false);
if ($results != null) {
    $smarty->assign("most_mobile_users", $results);
}

$results = null;
$db->ExecSql("SELECT COUNT(user_id) AS active_days, user_id FROM (SELECT DISTINCT user_id, date_trunc('day', timestamp_in) AS date FROM connections WHERE (incoming!=0 OR outgoing!=0) GROUP BY date,user_id) as user_active_days GROUP BY user_id ORDER BY active_days DESC LIMIT 10",$results, false);
if ($results != null) {
    $smarty->assign("most_frequent_users", $results);
}

$results = null;
$db->ExecSql("SELECT DISTINCT user_id, SUM((incoming+outgoing)/1048576) AS total, SUM((incoming/1048576)) AS total_incoming, SUM((outgoing/1048576)) AS total_outgoing FROM connections GROUP BY user_id ORDER BY total DESC limit 10",$results, false);
if ($results!=null) {
    $smarty->assign("most_apetite_users", $results);
}

$smarty->display("admin/templates/user_stats.html");
?>
