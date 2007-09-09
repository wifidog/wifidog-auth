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
 * Displays a user's profile
 *
 * @package    WiFiDogAuthServer
 * @author     François Proulx <francois.proulx@gmail.com>
 * @copyright  2007 François Proulx
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once ('../include/common.php');

require_once ('classes/Node.php');
require_once ('classes/MainUI.php');
require_once ('classes/Session.php');
$smarty = SmartyWifidog::getObject();
$db = AbstractDb::getObject(); 

// Init vars
$profile_user = null;
$profile = null;

// Init session
$session = Session::getObject();

// Get the current user
$current_user = User :: getCurrentUser();

/*
 * Start general request parameter processing section
 */
if (!empty ($_REQUEST['user_id'])) {
    try {
        $profile_user = User :: getObject($_REQUEST['user_id']);
        if(!empty($profile_user)) {
        	$profiles = $profile_user->getAllProfiles();
        	if(!empty($profiles)) {
        		$profile = $profiles[0];        		
        	}
        }
    } catch (Exception $e) {
        $ui = MainUI::getObject();
        $ui->displayError($e->getMessage());
        exit;
    }
} else {
    $ui = MainUI::getObject();
    $ui->displayError(_("No user id specified!"));
    exit;
}

// Init ALL smarty SWITCH values
$smarty->assign('sectionMAINCONTENT', false);

/*
 * Render output
 */

$ui = MainUI::getObject();
$ui->setTitle(_("User profile"));
if(Dependency::check('php-openid')){
    require_once('classes/OpenIdServerWifidog.php');
$ui->appendHtmlHeadContent("<link rel='openid.server' href='".OpenIdServerWifidog::getOpenIdServerUrl()."' />");
}
$ui->setPageName('profile');
//$ui->addContent('left_area_middle', $tool_html);

/*
 * Main content
 */
$welcome_msg = sprintf("<span>%s</span> <em>%s</em>",_("User profile for"), $profile_user->getUsername());
$ui->addContent('page_header', "<h1>$welcome_msg</h1>");


if (!empty($profile)) {
	$main_area_middle_html = "";
	$main_area_middle_html .= $profile->getUserUI();
	
    $ui->addContent('main_area_middle', $main_area_middle_html);
}
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>