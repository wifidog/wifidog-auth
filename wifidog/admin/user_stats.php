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
   * @author Copyright (C) 2004 Benoit Gr�goire
   */
define('BASEPATH','../');
require_once 'admin_common.php';

$security = new Security();
$security->requireAdmin();

require_once BASEPATH.'classes/Statistics.php';

$smarty->assign("total_users",  $stats->getNumUsers());
$smarty->assign("total_valid",  $stats->getNumValidUsers());

$smarty->assign("registrations", Statistics::getRegistrationsPerMonth());
$smarty->assign("most_mobile_users", Statistics::getMostMobileUsers(10));
$smarty->assign("most_frequent_users", Statistics::getMostFrequentUsers(10));
$smarty->assign("most_greedy_users", Statistics::getMostGreedyUsers(10));
$smarty->display("admin/templates/user_stats.html");
?>
