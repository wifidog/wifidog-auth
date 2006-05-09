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
 * Resends the validation e-mail if it has been requested by the user
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('include/common_interface.php');
require_once('classes/User.php');
require_once('classes/MainUI.php');

/**
 * Process recovering username
 */

// Init ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Init ALL smarty values
$smarty->assign('username', "");
$smarty->assign('message', "");
$smarty->assign('error', "");
$smarty->assign('auth_sources', "");
$smarty->assign('selected_auth_source', "");
$smarty->assign('SelectNetworkUI', "");

if (isset($_REQUEST["form_request"])) {
    $account_origin = Network::getObject($_REQUEST['auth_source']);

    try {
        /*
         * Tool content
         */

        // Set section of Smarty template
        $smarty->assign('sectionTOOLCONTENT', true);

        // Compile HTML code
        $html = $smarty->fetch("templates/sites/resend_validation.tpl");

        /*
         * Main content
         */

        // Reset ALL smarty SWITCH values
        $smarty->assign('sectionTOOLCONTENT', false);
        $smarty->assign('sectionMAINCONTENT', false);

        // Set section of Smarty template
        $smarty->assign('sectionMAINCONTENT', true);

        if (empty($account_origin)) {
            throw new Exception(_("Sorry, this network does not exist !"));
        }

        if (!$_REQUEST["username"]) {
            throw new Exception(_("Please specify a username"));
        }

        // Get a list of users with this username
        $user = User::getUserByUsernameAndOrigin($_REQUEST['username'], $account_origin);

        if ($user == null) {
            throw new Exception(_("This username could not be found in our database"));
        }

        $user->sendValidationEmail();

        $smarty->assign('message', _("An email with confirmation instructions was sent to your email address."));

        // Compile HTML code
        $html_body = $smarty->fetch("templates/sites/validate.tpl");

        /*
         * Render output
         */
        $ui = new MainUI();
        $ui->addContent('left_area_middle', $html);
        $ui->addContent('main_area_middle', $html_body);
        $ui->display();

        // We're done ...
        exit;
    } catch (Exception $e) {
        $smarty->assign('error', $e->getMessage());

        // Reset HTML output
        $html = "";
        $html_body = "";

        // Reset ALL smarty SWITCH values
        $smarty->assign('sectionTOOLCONTENT', false);
        $smarty->assign('sectionMAINCONTENT', false);
    }
}

/*
 * Tool content
 */

// Set section of Smarty template
$smarty->assign('sectionTOOLCONTENT', true);

// Compile HTML code
$html = $smarty->fetch("templates/sites/resend_validation.tpl");

/*
 * Main content
 */

// Reset ALL smarty SWITCH values
$smarty->assign('sectionTOOLCONTENT', false);
$smarty->assign('sectionMAINCONTENT', false);

// Set section of Smarty template
$smarty->assign('sectionMAINCONTENT', true);

// Add the auth servers list to smarty variables
$sources = array();

// Preserve keys
$network_array=Network::getAllNetworks();

foreach ($network_array as $network) {
    if ($network->getAuthenticator()->isRegistrationPermitted()) {
        $sources[$network->getId()] = $network->getName();
    }
}

if (isset($sources)) {
    $smarty->assign('auth_sources', $sources);
}

// Pass the account_origin along, if it's set
if (isset($_REQUEST["auth_source"])) {
    $smarty->assign('selected_auth_source', $_REQUEST["auth_source"]);
}

$smarty->assign('SelectNetworkUI', Network::getSelectNetworkUI('auth_source'));

// Compile HTML code
$html_body = $smarty->fetch("templates/sites/resend_validation.tpl");

/*
 * Render output
 */
$ui = new MainUI();
$ui->addContent('left_area_middle', $html);
$ui->addContent('main_area_middle', $html_body);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
