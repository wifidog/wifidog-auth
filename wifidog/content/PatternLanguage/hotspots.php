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
$tool_html .= _("Pattern Language is a commission by the Locative Media Lab and the ")."<a href='http://www.mdcn.ca/'>Mobile Digital Commons Network</a>".(" with funding from the New Media Research Networks Fund at the Department of Canadian Heritage.")."<p>";
$tool_html .= "<a href='http://www.katearmstrong.com/'>"._("Contact Kate Armstrong")."</a><p>";
$tool_html .= "</div>";

// Body
$body_html = "<h1>"._("Participating hotspots")."</h1>\n";
$body_html .= "<div class='pattern_language_body'>\n";
$body_html .= "<ul class='pattern_language_menu'>\n";
$body_html .= "<li>Laika : 4040 St-Laurent (coin Duluth). Métro Sherbrooke. 514-842-8088.</li>";
$body_html .= "<li>Café Supreme : 3685 St-Laurent. Métro Sherbrooke. 514-844-5975.</li>";
$body_html .= "<li>Les Folies : 701 Mont-Royal Est. Métro Mont-Royal, en face. 514-528-4343.</li>";
$body_html .= "<li>Studio XX : 338 Terasse Saint-Denis . 514-845-7934.</li>";
$body_html .= "<li>Café Tribune : 1567 Saint-Denis. Métro Berri-UQAM, sortir à Saint-Denis et traverser la rue. 514-840-0915.</li>";
$body_html .= "<li>Café l'Utopik : 552 Sainte-Catherine est. Métro Berri-UQAM, sortir à Sainte-Catherine et traverser la rue. 514-844-1139.</li>";
$body_html .= "<li>Zeke's Gallery : 3955 Saint-Laurent coin Duluth. Métro Mont-Royal. 514-288-2233.</li>";
$body_html .= "<li>Cluny's Art Bar : Fonderie Darling - 745 rue Ottawa, Vieux-Montréal . Métro Square-Victoria . 514-392-1554.</li>";
$body_html .= "<li>Café Vienne : 1446 Sainte-Catherine ouest. Métro Guy-Concordia, sortie Guy. 514-397-8779.</li>";
$body_html .= "<li>Atwater Library : 1200 Atwater. 514-935-7344. info@atwaterlibrary.ca.</li>";
$body_html .= "</ul>\n";
$body_html .= "</div>\n";

$ui=new MainUI();
$ui->setToolContent($tool_html);
$ui->setTitle(_("Pattern Language - Hotspots list"));
$ui->setMainContent($body_html);
$ui->display();

?>