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
  /**@file user_profile.php
   * User profile
   * @author 2005 François Proulx
   */

define('BASEPATH', './');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/MainUI.php';

// Prepare tools menu
$tool_html = '<ul>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'change_password.php">'._("Change password").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'faq.php">'._("I have trouble connecting and I would like some help").'</a><br>'."\n";
$tool_html .= '</ul>'."\n";
$body_html = "";

// Prepare User object
if(!empty($_REQUEST['user_id']))
    $user = User::getObject($_REQUEST['user_id']);
else
    $user = User::getCurrentUser();

if($user)
{
    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'save')
        $body_html = $user->processAdminUI();
        
    // If the current user is the same show admin menu otherwise just show the profile
    if($user->getId() == User::getCurrentUser()->getId())
        $body_html .= $user->getAdminUI();
    else
        $body_html .= $user->getUserUI();
}

$ui=new MainUI();
$ui->setToolContent($tool_html);
$smarty->assign("title", _("User Profile"));
$ui->setMainContent($body_html);
$ui->display();

?>