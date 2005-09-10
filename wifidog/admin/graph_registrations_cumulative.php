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
  /**@file graph_registrations_cumulative.php
   * @author Copyright (C) 2005 Philippe April
   */

define('BASEPATH',"../");
require_once BASEPATH.'admin/graph_common.inc.php';

$Graph =& Image_Graph::factory("Image_Graph", array(600, 200));
$Plotarea =& $Graph->add(Image_Graph::factory("Image_Graph_Plotarea"));
$Dataset =& Image_Graph::factory("Image_Graph_Dataset_Trivial");
$Area =& Image_Graph::factory("Image_Graph_Plot_Area", &$Dataset);
$Area->setFillColor("#9db8d2");
$Plot =& $Plotarea->add(&$Area);

$total = 0;
$registration_stats = Statistics::getRegistrationsPerMonth($_REQUEST['date_from'], $_REQUEST['date_to'], "ASC");
foreach ($registration_stats as $row) {
    $total += $row['num_users'];
    $Dataset->addPoint(substr($row['month'],0,7), $total);
}

$Graph->done();
?>
