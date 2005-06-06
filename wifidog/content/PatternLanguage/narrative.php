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
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/Content/PatternLanguage.php';
require_once BASEPATH.'classes/MainUI.php';

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
// User's Narrative
try
{
	if (empty ($_REQUEST['user_id']))
		$user = User :: getCurrentUser();
	else
		$user = User :: getObject($_REQUEST['user_id']);

	if ($user)
	{
		$body_html = "<img src='header.gif'>\n";
        $body_html .= "<h1>"._("Narrative for ")."{$user->getUsername()}</h1>\n";
        $body_html .= "<div class='pattern_language_body'>\n";
		$body_html .= $pattern_language->displayNarrative($user);
        $body_html .= "</div>\n";
	}
    else
    {
    	$body_html = "<img src='header.gif'><p>\n";
        $body_html .= _("You must be logged in to read your narrative");
    }
}
catch (Exception $e)
{
	header("Location: archives.php");
}

$ui = new MainUI();
$ui->setToolContent($tool_html);
$ui->setTitle(_("Pattern Language - Narrative"));
$ui->setMainContent($body_html);
$ui->display();
?>