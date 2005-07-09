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

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/MainUI.php';

$ui = new MainUI();
$ui->setTitle(_("Venues map"));

// Add Google Maps JavaScript ( must set config values )
$html_headers = "<script src=\"http://maps.google.com/maps?file=api&v=1&key=".GMAPS_API_KEY."\" type=\"text/javascript\"></script>";
$html_headers .= "<script src=\"js/gmaps_venues_status_map.js\" type=\"text/javascript\"></script>";
$ui->setHtmlHeader($html_headers);

// Create HTML body
$html = "<h1>"._("Venues status map")."</h1>\n";
$html .= "<div id=\"map_venues_list\"></div>\n";
$html .= "<div id=\"map_frame\"></div>\n";
// The onLoad code should only be called once all DIV are created.
$html .= "<script type=\"text/javascript\">onLoad('".GMAPS_XML_SOURCE_URL."', ".GMAPS_INITIAL_LATITUDE.", ".GMAPS_INITIAL_LONGITUDE.", ".GMAPS_INITIAL_ZOOM_LEVEL.");</script>\n";

$tool_html = '<p class="indent">'."\n";
$tool_html .= "<ul class='users_list'>\n";
$tool_html .= "<li><a href='hotspot_status.php'>"._('Deployed HotSpots status with coordinates')."</a></li>";
$tool_html .= "</ul>\n";
$tool_html .= '</p>'."\n";

$ui->setToolContent($tool_html);		
$ui->setMainContent($html);

$ui->display();

?>