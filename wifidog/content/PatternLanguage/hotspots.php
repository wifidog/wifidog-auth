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
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../../include/common.php');

require_once('include/common_interface.php');
require_once('classes/MainUI.php');
require_once('classes/Content/PatternLanguage.php');

// This trick is done to allow displaying of Pattern Language right away if there is only one available.
if(!empty($_REQUEST['content_id']))
{
    $content_id = $_REQUEST['content_id'];
    $pattern_language = PatternLanguage::getObject($content_id);
}
else
{
    $content_id = "";
    $pattern_languages = PatternLanguage :: getAllContent();
    if(count($pattern_languages) >= 1)
        $pattern_language = $pattern_languages[0];
    else
        exit;
}

// The Pattern Language toolbar
$tool_html = "<h1>{$pattern_language->getTitle()->__toString()}</h1>";
$tool_html .= '<ul class="pattern_language_menu">'."\n";
$gw_id = $session->get(SESS_GW_ID_VAR);
if(!empty($gw_id))
    $tool_html .= "<li><a href='/portal/?gw_id=$gw_id'>"._("Go back to this hotspot portal page")."</a></li>";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/index.php?content_id='.$content_id.'">'._("About Pattern Language").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/narrative.php?content_id='.$content_id.'">'._("Read narrative").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/archives.php?content_id='.$content_id.'">'._("Archives").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/hotspots.php?content_id='.$content_id.'">'._("Participating hotspots").'</a><br>'."\n";
$tool_html .= '<li><a href="'.BASE_SSL_PATH.'content/PatternLanguage/subscription.php?content_id='.$content_id.'">'._("Subscription").'</a><br>'."\n";
$tool_html .= '</ul>'."\n";

$tool_html .= "<div class='pattern_language_credits'>";
$tool_html .=  $pattern_language->getSponsorInfo()->__toString();
$tool_html .= "</div>";

// Body
$body_html = "<img src='".BASE_SSL_PATH."images/PatternLanguage/header.gif'>\n";
$body_html .= "<h1>"._("Participating hotspots")."</h1>\n";
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

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
