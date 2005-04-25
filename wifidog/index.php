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
   * Authserver home page
   * @author Copyright (C) 2004 Benoit Gr�goire
   */

define('BASEPATH', './');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/MainUI.php';

$smarty->assign("num_valid_users", $stats->getNumValidUsers());
$smarty->assign("num_online_users", $stats->getNumOnlineUsers($node_id = null));

$smarty->assign("title", _("authentication server"));
$ui=new MainUI();
$ui->setMainContent($smarty->fetch("templates/main.html"));
$ui->display();
?>
