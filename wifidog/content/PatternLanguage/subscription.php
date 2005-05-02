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
$tool_html .= _("Pattern Language is a commission by the Locative Media Lab and the ")."<a href='http://www.mdcn.ca/'>Mobile Digital Commons Network</a>". (" with funding from the New Media Research Networks Fund at the Department of Canadian Heritage.")."<p>";
$tool_html .= "<a href='http://www.katearmstrong.com/'>"._("Contact Kate Armstrong")."</a><p>";
$tool_html .= "</div>";

// Body
$body_html = "<h1>"._("Pattern Language Subscription")."</h1>\n";
$body_html .= "<div class='pattern_language_body'>\n";

$current_user = User::getCurrentUser();
if($current_user)
{
    if(!empty($_REQUEST['content_id']))
    {
        if(!empty($_REQUEST['subscribe']))
        {
            $pattern_language = PatternLanguage::getObject($_REQUEST['content_id']);
            if(!$pattern_language->isUserSubscribed($current_user))
            {
                $pattern_language->subscribe($current_user);
                $body_html .= _("Thank you for subscribing");
                $gw_id = $session->get(SESS_GW_ID_VAR);
                if(!empty($gw_id))
                    $body_html .= "<p><a href='/portal/?gw_id=$gw_id'>"._("Go back to this hotspot portal page")."</a>";
            }
        }
        else if(!empty($_REQUEST['unsubscribe']))
        {
            $pattern_language = PatternLanguage::getObject($_REQUEST['content_id']);
            if($pattern_language->isUserSubscribed($current_user))
                $pattern_language->unsubscribe($current_user);
            $body_html .= _("You are now unsubscribed");
            $gw_id = $session->get(SESS_GW_ID_VAR);
            if(!empty($gw_id))
                $body_html .= "<p><a href='/portal/?gw_id=$gw_id'>"._("Go back to this hotspot portal page")."</a>";
        }
    }
    else
    {
        $pattern_languages = PatternLanguage :: getAllContent();
        foreach ($pattern_languages as $pattern_language)
        {
            if(!$pattern_language->isUserSubscribed($current_user))
            {
                // Subscription
                $body_html .= _("By clicking the link below you will subscribe to Pattern Language. Pattern Language will appear in the user subscribed content section at the bottom of portal pages. As you visit participating hotspots thoughout Montréal you will receive patterns that will create your unique narrative. You can always read other people's narrative using the archives.");
                $body_html .= "<br><a href='subscription.php?subscribe=true&content_id={$pattern_language->getId()}'>"._("Subscribe now")."</a>";
            } 
            else
            {
                // Unsubscription
                $body_html .= _("You are already subscribed to Pattern Language, you can terminate your participation by clicking below.");
                $body_html .= "<br><a href='subscription.php?unsubscribe=true&content_id={$pattern_language->getId()}'>"._("Unsubscribe now")."</a>";
            }
        }
    }
}
else
{
    $body_html .= _("You must be logged in to subscribe !");
}

$body_html .= "</div>\n";

$ui = new MainUI();
$ui->setToolContent($tool_html);
$ui->setTitle(_("Pattern Language - Subscription"));
$ui->setMainContent($body_html);
$ui->display();
?>