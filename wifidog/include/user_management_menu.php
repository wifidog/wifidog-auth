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
  /**@file AbstractDb.php
   * @author Copyright (C) 2004 Benoit Grégoire
   */

function get_user_management_menu()
{
  $retval = '';

  //$retval .= "<div class='menu'>\n";

  $retval .= "<p>&nbsp;User management</p>\n";
  $retval .= "<ul>\n";
  if($_SERVER['SERVER_PORT']==80) { 
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?action=validation_email_form'>"._('Send validation email again')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?action=lost_username_form'>"._('Lost my username')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?action=lost_password_form'>"._('Lost my password')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?action=change_password_form'>"._('Change my password')."</a></li>\n"; 
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]?action=register_new_account_form'>"._('Register a new user')."</a></li>\n"; 
  }
  else {
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]?action=validation_email_form'>"._('Send validation email again')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]?action=lost_username_form'>"._('Lost my username')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]?action=lost_password_form'>"._('Lost my password')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]?action=change_password_form'>"._('Change my password')."</a></li>\n";
    $retval .= "<li><a href='http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]?action=register_new_account_form'>"._('Register a new user')."</a></li>\n";
  }
  $retval .= "</ul>\n";
  //$retval .= "</div>\n";
  return $retval;
}
?>
