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
 * Load required classes
 */
require_once('classes/Content/SimplePicture/SimplePicture.php');
/**
 * Represents an avatar
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author François Proulx <francois.proulx@gmail.com>
 * @copyright Copyright (c) 2007 François Proulx
 */
class Avatar extends SimplePicture
{
    /**
     * Constructor
     *
     * @param string $content_id Content id     */
    protected function __construct($content_id) {
        parent :: __construct($content_id);
        $this -> configEnableEditWidthHeight(false);
        $this -> configEnableHyperlink(false);
    }

    public static function getDefaultUserUI() {
        $html = "<img src='" . COMMON_IMAGES_URL . "default_avatar.png' class='Avatar' />";
        return $html;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */