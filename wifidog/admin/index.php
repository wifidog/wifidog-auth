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
  /**@file index.php
   * @author Copyright (C) 2004 Benoit Grégoire
   */

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';

$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' administration');
    echo "<div id='head'><h1>Administration page for ". HOTSPOT_NETWORK_NAME ."</h1></div>\n";
  echo "<div id='navLeft'>\n";
//echo get_user_management_menu();
echo "</div>\n";
    echo "<div id='content'>\n";
echo _("For now, you need to be logged in at a hotspot or to fake a login from one of the HotSpot login pages to have your administrative clearance recognised.");
    echo "<ul>\n";
    echo "<li><a href='user_log.php'>"._('User logs')."</a></li>\n";
    echo "<li><a href='user_stats.php'>"._('Cumulative user statistics')."</a></li>\n";
    echo "<li><a href='hotspot_log.php'>"._('Hotspot logs')."</a></li>\n";
    echo "<li><a href='import_user_database.php'>"._('Import NoCat user database')."</a></li>\n";
    echo "</ul>\n";

echo $style->GetFooter();
?>
