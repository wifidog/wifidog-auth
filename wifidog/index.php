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

define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';

$style = new Style();
echo $style->GetHeader(HOTSPOT_NETWORK_NAME.' authentication server');

    echo "<h1>Wifidog authentication server for ". HOTSPOT_NETWORK_NAME ."</h1>\n";
    echo "<ul>\n";
    echo "<li><a href='".BASE_SSL_PATH."login/index.php?gw_id=default&gw_address=127.0.0.1&gw_port=80'>Login (demo)</a></li>\n";
    echo "<li><a href='./portal/index.php?gw_id=default'>Portal (demo)</a></li>\n";
    echo "<li><a href='./user_management/index.php'>Personal user management</a></li>\n";
    echo "<li><a href='".BASE_SSL_PATH."admin/index.php'>Administration</a></li>\n";
    echo "</ul>\n";

echo $style->GetFooter();
?>
