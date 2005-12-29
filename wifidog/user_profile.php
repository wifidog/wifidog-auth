<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * Displays the user profile
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx <francois.proulx@gmail.com> - Technologies
 * Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 * @todo       Write user profiles
 */

require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/User.php');
require_once('classes/MainUI.php');

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
//TODO: Write User profiles
$ui->setMainContent($body_html);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
