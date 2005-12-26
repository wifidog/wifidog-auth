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
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005 Max Horvath <max.horvath@maxspot.de> - maxspot GmbH
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

function showHideView(show, hide) {
    if (show != null && show != "") {
        var _show = document.getElementById(show);
        _show.style.display = "";
    }

    if (hide != null && hide != "") {
        var _hide = document.getElementById(hide);
        _hide.style.display = "none";
    }
}

function showChangeView(show, change, changeIdentifier, changeCommand) {
    if (show != null && show != "") {
        var _show = document.getElementById(show);
        _show.style.display = "";
    }

    if (change != null && change != "" &&
        changeIdentifier != null && changeIdentifier != "" &&
        changeCommand != null && changeCommand != "") {
        var _change = document.getElementById(change);
        _change.changeIdentifier = changeCommand;
    }
}

function toggleView(name) {
    if (name != null) {
        changeView(name);
    } else {
        changeView("", name);
    }
}