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
  /**@file index.php
   * Pattern Language Home page
   * @author Copyright (C) 2005 François Proulx
   */

define('BASEPATH', '../../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/MainUI.php';

// The Pattern Language toolbar
$tool_html = "<h1>"._("Pattern Language")."</h1>";
$tool_html .= '<ul class="pattern_language_menu">'."\n";
$gw_id = $session->get(SESS_GW_ID_VAR);
if(!empty($gw_id))
    $tool_html .= "<li><a href='/portal/?gw_id=$gw_id'>"._("Go back to this hotspot portal page")."</a></li>";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/index.php">'._("About Pattern Language").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/narrative.php">'._("Read narrative").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/archives.php">'._("Archives").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/hotspots.php">'._("Participating hotspots").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/subscription.php">'._("Subscription").'</a><br>'."\n";
$tool_html .= '</ul>'."\n";

$tool_html .= "<div class='pattern_language_credits'>";
$tool_html .= _("Programming by Benoît Grégoire and François Proulx")."<p>";
$tool_html .= _("French translation by TBD")."<p>";
$tool_html .= _("With thanks to tobias v. van Veen & Michael Longford")."<p>";
$tool_html .= _("Pattern Language is a commission by the Locative Media Lab and the ")."<a href='http://www.mdcn.ca/'>Mobile Digital Commons Network</a>"._(" with funding from the New Media Research Networks Fund at the Department of Canadian Heritage.")."<p>";
$tool_html .= "<a href='http://www.katearmstrong.com/'>"._("Contact Kate Armstrong")."</a><p>";
$tool_html .= "</div>";

// Body
$body_html = "<h1>"._("About Pattern Language")."</h1>";
$body_html .= "<div class='pattern_language_body'>";
$body_html .= _("Pattern Language is a location-aware fiction project by Kate Armstrong that attaches patterns of narrative to individuals as they move through the city of Montreal. Each person's path is logged in the system and compiled into a document that can be read online. The work is meant to engage with the rhythms of the city: by evolving according to the patterns of an individual, each story forms both a map or trace of movement and a fabric of sound.")."<p>";
$body_html .= _("Pattern Language is activated through the login system of the Île Sans Fil network. Once a user subscribes to Pattern Language , a piece of the story is delivered whenever s/he logs into the ISF wireless network using a WiFi-enabled laptop or mobile device.")."<p>";
$body_html .= _("Narrative fragments associated with each hotspot correspond to the point of view of one character, so that repeatedly logging into the system from a single hotspot will produce a narrative from a single point of view, while moving between hotspots will insert new characters and perspectives into the text.")."<p>";
$body_html .= _("Users may choose to actively engage Pattern Language by deliberately travelling between points in the city in order to generate narrative activity, or they may decide to have the project running in the background as they go about their regular activities, only stopping to read their document now and then over a period of time.")."<p>";
$body_html .= _("Once the fragments have been delivered they are compiled into a document that is unique to each participant. Each individual narrative is archived and viewable online. Users may read the documents they have generated or those that have been generated by others. Participation in Pattern Language  is limited to those using the system in Montreal, although narratives generated by participants may be read by anyone visiting the website.");
$body_html .= "</div>";


$ui=new MainUI();
$ui->setToolContent($tool_html);
$ui->setTitle(_("Pattern Language - About"));
$ui->setMainContent($body_html);
$ui->display();

?>