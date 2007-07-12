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
 * Gateway messages
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @copyright  2004-2006 Philippe April
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once(dirname(__FILE__) . '/include/common.php');

require_once('classes/User.php');
require_once('classes/Network.php');
require_once('classes/MainUI.php');

$ui = MainUI::getObject();
$errmsg = "";

if (isset($_REQUEST["message"])) {
    switch ($_REQUEST["message"]) {
    case "failed_validation":
        $errmsg .= "<p>" . sprintf(_("You have failed to validate your account in %d minutes."), (User::getCurrentUser()->getNetwork()->getValidationGraceTime() / 60)) . "</p>";
        $errmsg .= "<p>" . _("Please validate your account from somewhere else or create a new one.") . "</p>";
        $ui->displayError($errmsg);
        break;

    case "denied":
        $errmsg .= _("Access denied!");
        $ui->displayError($errmsg);
        break;

    case "activate":
        $minutes_grace_time = User::getCurrentUser()->getNetwork()->getValidationGraceTime() / 60;
        $errmsg .= "<p>" . sprintf(_("You have now been granted %d minutes of Internet access without being validated to go and activate your account."), $minutes_grace_time) . "</p>";
        $errmsg .= "<p>" . sprintf(_("If you fail to validate your account in %d minutes, you will have to validate it from somewhere else."), $minutes_grace_time) . "</p>";
        $errmsg .= "<p>" . _("If you do not receive an email from our validation server in the next minute, perhaps you have made a typo in your email address, you might have to create another account.") . "</p>";
        $ui->displayError($errmsg);
        break;

    default:
        $errmsg .= _("Unknown message specified! (this is probably an error)");
        $ui->displayError($errmsg);
        break;
    }
} else {
    $errmsg .= _("No message has been specified! (this is probably an error)");
    $ui->displayError($errmsg);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>