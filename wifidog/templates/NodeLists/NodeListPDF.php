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
 * Configuration file of PDF NodeList output
 *
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: config.php 986 2006-03-07 19:41:51Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/********************************************************************\
 * PDF OUTPUT CONFIGURATION                                         *
\********************************************************************/

/**
 * Format of generated page?
 * ======================================================
 *
 * Possible values:
 * - letter
 * - a4
 */
define("PDF_FORMAT", "letter");

/**
 * Sort list by?
 * ======================================================
 *
 * Possible values:
 * - name
 * - street_name
 * - postal_code
 * - city
 */
define("PDF_SORT", "name");

/**
 * Format of date
 * ======================================================
 *
 * Use PHP date format
 */
define("PDF_DATE", "m/d/Y");

/**
 * Path to your logo
 * ======================================================
 */
define("PDF_IMAGE", "media/base_theme/images/wifidog_logo.jpg");

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
