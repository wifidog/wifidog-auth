<?php
  // $Id$
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
  /**@file
   * Gateway Messages
   * @author Copyright (C) 2005 Philippe April
   */
define('BASEPATH','./');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';

if (isset($_REQUEST["message"])) {
    switch ($_REQUEST["message"]) {
        case "failed_validation":
            $smarty->display("templates/message_failed_validation.html");
            break;
        case "denied":
            $smarty->display("templates/message_denied.html");
            break;
        case "activate":
            $smarty->display("templates/message_activate.html");
            break;
        default:
            $smarty->display("templates/message_default.html");
            break;
    }
} else {
    $smarty->display("templates/message_default.html");
}
?>
