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
   * @author Copyright (C) 2005 Philippe April
   */

   /* The purpose of this file is to have common objects and things
    * declared but NOT to have them in common.php because common.php
    * is also used for functions called by the gateway and we do not
    * (for example) want to create a session for every call and pings
    * from the gateways.
    */
require_once BASEPATH.'classes/Session.php';
require_once BASEPATH.'classes/Statistics.php';
require_once BASEPATH.'classes/SmartyWifidog.php';
require_once BASEPATH.'classes/User.php';

$smarty = new SmartyWifidog;
$session = new Session();
$stats = new Statistics();

require_once BASEPATH.'include/language.php';

try
{
  $current_user = new User($session->get(SESS_USER_ID_VAR));
  $smarty->assign("auth_user", $current_user->getUsername());
}
catch (Exception $e) 
{
  ;
}

?>