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
/**@file hotspot_status.php
 * Network status page
 * @author Copyright (C) 2005 François Proulx
 */

define('BASEPATH', './');
//TODO: Move these constants in Network class, once in database (I guess)
define('GOOGLE_MAPS_API_KEY', 'ENTER_YOUR_KEY_HERE');
// Enter center coords ( ie. Montréal )
define('CENTER_LATITUDE', '45.494511');
define('CENTER_LONGITUDE', '-73.560285');

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/MainUI.php';

$ui = new MainUI();
$ui->setTitle(_("Venues map"));

// Add Google Maps JavaScript
$html_headers = "<script src=\"http://maps.google.com/maps?file=api&v=1&key=".GOOGLE_MAPS_API_KEY."\" type=\"text/javascript\"></script>";
$html_headers .= "<script src=\"js/venues_status_map.js\" type=\"text/javascript\"></script>";
$ui->setHtmlHeader($html_headers);

// Create HTML body
$html = "<div id=\"map_venues_list\"></div>\n";
$html .= "<div id=\"map_frame\"></div>\n";
// The onLoad code should only be called once all DIV are created.
$html .= "<script type=\"text/javascript\">onLoad('hotspot_status.php?format=XML', ".CENTER_LATITUDE.", ".CENTER_LONGITUDE.");</script>\n";
$ui->setMainContent($html);

$ui->display();

?>