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
 * WiFiDog Authentication Server home page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire <bock@step.polymtl.ca> - Technologies Coeus
 * inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

/**
 * Don't change the first require_once() as we didn't add WiFiDogs installation
 * path to the global include_path variable of PHP, yet!
 */
require_once(dirname(__FILE__) . '/include/common.php');
EventLogging::SetupErrorHandling("strict~/var:\sDeprecated/(off)",
								 new PrintChannel(new HTMLFormatter(), 'warning,notice', null, true),
								 new PrintChannel(new HTMLCommentsFormatter(), '=debug', null, true));

require_once('include/common_interface.php');
require_once('classes/MainUI.php');
require_once('classes/Node.php');

$network = Network::getCurrentNetwork();

$tool_html  = '<ul>';
$tool_html .= '<li><a href="' . BASE_SSL_PATH . 'change_password.php">' . _("Change password") . '</a></li>';
$tool_html .= '<li><a href="' . BASE_SSL_PATH . 'faq.php">' . _("I have trouble connecting and I would like some help") . '</a></li>';
$tool_html .= '</ul>';

$body_html  = '<p>' . sprintf(_("The %s network currently has %s valid users, %s user(s) are currently online"), $network->getName(), $network->getNumValidUsers(), $network->getNumOnlineUsers()) . '</p>';
$body_html .= '<ul>';

if (defined('GMAPS_HOTSPOTS_MAP_ENABLED') && GMAPS_HOTSPOTS_MAP_ENABLED == true) {
    $body_html .= '<li><a href="' . BASE_NON_SSL_PATH . 'hotspots_map.php">' . _("Deployed HotSpots map") . '</a></li>';
}

$body_html .= '<li><a href="hotspot_status.php">' . _("Deployed HotSpots status with coordinates") . '</a></li>';
$body_html .= '<li><a href="node_list.php">' . _("Full node technical status (includes non-deployed nodes)") . '</a></li>';
$body_html .= '<li><a href="' . BASE_SSL_PATH . 'admin/index.php">' . _("Administration") . '</a></li>';
$body_html .= '</ul>';

$smarty->assign("title", _("authentication server"));

$ui = new MainUI();
$ui->setToolContent($tool_html);
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
