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

  $retval .= "<p>Find more HotSpots</p>\n";
  $retval .= "<ul>\n";
   $retval .= "<li><a href='".BASE_URL_PATH."".HOTSPOT_STATUS_PAGE."'>"._('List of all HotSpots')."</a></li>\n"; 
  $retval .= "</ul>\n";
  $retval .= "<p>User management</p>\n";
  $retval .= "<ul>\n";

    $retval .= "<li><a href='".BASE_SSL_PATH."".USER_MANAGEMENT_PAGE."?action=register_new_account_form'>"._('Create new account')."</a></li>\n"; 
    $retval .= "<li><a href='".BASE_SSL_PATH."".USER_MANAGEMENT_PAGE."?action=validation_email_form'>"._('Re-send validation email')."</a></li>\n";
    $retval .= "<li><a href='".BASE_SSL_PATH."".USER_MANAGEMENT_PAGE."?action=lost_username_form'>"._('Lost username')."</a></li>\n";
    $retval .= "<li><a href='".BASE_SSL_PATH."".USER_MANAGEMENT_PAGE."?action=lost_password_form'>"._('Lost password')."</a></li>\n";
    $retval .= "<li><a href='".BASE_SSL_PATH."".USER_MANAGEMENT_PAGE."?action=change_password_form'>"._('Change password')."</a></li>\n"; 
 
  $retval .= "</ul>\n";
  $retval .= "<p class='sidenote'>"._("Accounts on ".HOTSPOT_NETWORK_NAME." are and will remain <emp>totally free</emp>, use the left menu to create a new one or recover a lost username or password.")."</p>\n";
  $retval .= "<p class='sidenote'>"._("Please report any problem or interruption in our service to:")." <a href='".TECH_SUPPORT_EMAIL."'>".TECH_SUPPORT_EMAIL."</a></p>\n";
  //$retval .= "</div>\n";
  return $retval;
}
?>
