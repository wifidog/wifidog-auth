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
 * Displays the portal page
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Philippe April
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required files
 */
require_once('../include/common.php');

require_once('include/common_interface.php');
require_once('classes/Node.php');
require_once('classes/MainUI.php');
require_once('classes/Session.php');

/**
 * Define width of toolbar
 *
 * Must match the stylesheet for the tool section width
 */
define('TOOLBAR_WIDTH', '250');

// Init values
$node = null;
$show_more_link = false;

// Init session
$session = new Session();

// Get the current user
$current_user = User::getCurrentUser();

if (!empty ($_REQUEST['gw_id'])) {
    $node = Node :: getObject($_REQUEST['gw_id']);
}

if ($node == null) {
    $smarty->display("templates/message_unknown_hotspot.html");
    exit;
}

// Get information about current network
$network = $node->getNetwork();

/*
 * If this node has a custom portal defined, and the network config allows it,
 * redirect to the custom portal
 */
$custom_portal_url = $node->getCustomPortalRedirectUrl();
if (!empty($custom_portal_url) && $network->getCustomPortalRedirectAllowed()) {
    header("Location: {$custom_portal_url}");
}

$node_id = $node->getId();
$portal_template = $node_id.".html";
Node :: setCurrentNode($node);

$ui = new MainUI();
if (isset ($session))
{
    if(!empty($_REQUEST['gw_id']))
        $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);

}

$tool_html = '';

$tool_html .= "<h1>"._("Online users")."</h1>"."\n";
$tool_html .= '<p class="indent">'."\n";
$current_node = Node :: getCurrentNode();
if ($current_node != null)
{
    $current_node_id = $current_node->getId();
    $online_users = $current_node->getOnlineUsers();
    $num_online_users = count($online_users);
    if ($num_online_users > 0)
    {
        //$tool_html .= $num_online_users.' '._("other users online at this hotspot...");
        $tool_html .= "<ul class='users_list'>\n";
        foreach($online_users as $online_user) {
            $tool_html .= "<li>";
            $tool_html .= $online_user->getUsername();
            $roles = array();
            if ($current_node->isOwner($online_user))
                $roles[] = _("owner");
            if ($current_node->isTechnicalOfficer($online_user))
                $roles[] = _("technical officer");
            if ($roles)
                $tool_html .= " <span class='roles'>(" . join($roles, ",") . ")</span>";
            $tool_html .= "</li>\n";
        }
        $tool_html .= "</ul>\n";
    }
    else
    {
        $tool_html .= _("Nobody is online at this hotspot...");
    }
}
else
{
    $network = Network::getCurrentNetwork();
    $current_node_id = null;
    $tool_html .= _("You are not currently at a hotspot...");
}
$tool_html .= "</p>"."\n";

$tool_html .= '<script type="text/javascript">
function getElementById(id) {
    if (document.all) {
    return document.getElementById(id);
    }
    for (i=0;i<document.forms.length;i++) {
    if (document.forms[i].elements[id]) {return document.forms[i].elements[id]; }
    }
}

function getWindowSize(window) {
var size_array = new Array(2);
  var myWidth = 0, myHeight = 0;
  if( typeof( window.innerWidth ) == "number" ) {
    //Non-IE
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if( document.documentElement &&
      ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in "standards compliant mode"
    myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  size_array[0] = myWidth;
  size_array[1] = myHeight;
//  window.alert( "Width = " + myWidth );
//  window.alert( "Height = " + myHeight );
return size_array;
}


</script>';


$tool_html .= '<p class="indent">'."\n";
$tool_html .= "<a  id='wifidog_portal_expand' onclick=\"
var wifidog_portal_expand = getElementById('wifidog_portal_expand');
var wifidog_portal_collapse = getElementById('wifidog_portal_collapse');

wifidog_portal_expand.style.display = 'none';
wifidog_portal_collapse.style.display = 'inline';
var size_array = getWindowSize(window.opener);
window.resizeTo(size_array[0],size_array[1]);
\">"._("Expand portal")."</a>"."\n";

$tool_html .= "<a id='wifidog_portal_collapse' onclick=\"
var wifidog_portal_expand = getElementById('wifidog_portal_expand');
var wifidog_portal_collapse = getElementById('wifidog_portal_collapse');

wifidog_portal_expand.style.display = 'inline';
wifidog_portal_collapse.style.display = 'none';
var size_array = getWindowSize(window.opener);
window.resizeTo('".TOOLBAR_WIDTH."',size_array[1]);

\">"._("Collapse portal")."</a>"."\n";
$tool_html .= "</p>"."\n";




    $original_url_requested=$session->get(SESS_ORIGINAL_URL_VAR);
    if(empty($original_url_requested))
    {
        $url="missing_original_url.php";
    }
    else
    {
        $url=$original_url_requested;
    }
$tool_html .= '<p class="indent">'."\n";
$tool_html .= "<a id='wifidog_use_internet' href='$url' onclick=\"
var size_array = getWindowSize(window);
var original_location=window.location.href;
//this.target='_blank';
var old_window = window;
var new_window = window.open('".CURRENT_REQUEST_URL."','wifidog_portal');
new_window.blur();
old_window.focus();
new_window.resizeTo('".TOOLBAR_WIDTH."',size_array[1]);

//old_window.location.href='test';
//window.moveBy(300, 300);
/*if(window.open){alert('window.open enabled');}
else{alert('window.open DISABLED');}*/

\"><img src='" . BASE_SSL_PATH . "images/start.gif'></a>\n";
$tool_html .= "</p>"."\n";

$tool_html .= '<script type="text/javascript">
//Set up if expand/collapse functionnality is to be enabled by checking if we were called from another portal window.

window.is_wifidog_portal=true; //This assignement may be read by another window

var wifidog_portal_expand = document.getElementById("wifidog_portal_expand");
var wifidog_portal_collapse = document.getElementById("wifidog_portal_collapse");
var wifidog_use_internet = document.getElementById("wifidog_use_internet");
if(window.opener && window.opener.is_wifidog_portal==true)
{
wifidog_portal_expand.style.display = "inline";
wifidog_portal_collapse.style.display = "none";
wifidog_use_internet.style.display = "none";
}
else
{
wifidog_portal_expand.style.display = "none";
wifidog_portal_collapse.style.display = "none";
}
</script>';

$ui->setToolContent($tool_html);

$hotspot_network_name = $network->getName();
$hotspot_network_url = $network->getHomepageURL();
$network_logo_url = COMMON_CONTENT_URL.NETWORK_LOGO_NAME;
$network_logo_banner_url = COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

$html = '';

// While in validation period, alert user that he should validate his account ASAP
if($current_user && $current_user->getAccountStatus() == ACCOUNT_STATUS_VALIDATION)
    $html .= "<div id='warning_message_area'>"._('An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retrieve your email and validate your account.')."</div>";

$html .= "<div id='portal_container'>\n";


/* Network section */

$html .= "<div class='portal_network_section'>\n";
$html .= "<a href='{$hotspot_network_url}'><img class='portal_section_logo' alt='{$hotspot_network_name} logo' src='{$network_logo_banner_url}' border='0'></a>\n";
// Get all network content and EXCLUDE user subscribed content
if($current_user)
    $contents = Network :: getCurrentNetwork()->getAllContent(true, $current_user);
else
    $contents = Network :: getCurrentNetwork()->getAllContent();
if ($contents)
{
    foreach ($contents as $content)
    {
        if ($content->isDisplayableAt($node))
        {
            $html .= "<div class='portal_content'>\n";
            $html .= $content->getUserUI();
            $html .= "</div>\n";
        }
    }
}
$html .= "</div>\n";

/* Node section */
// Get all node content and EXCLUDE user subscribed content
if($current_user)
    $contents = $node->getAllContent(true, $current_user);
else
    $contents = $node->getAllContent();

if(!empty($contents))
{
    $html .= "<div class='portal_node_section'>\n";
    $html .= "<span class='portal_section_title'>"._("Content from:")." ";
    $node_homepage = $node->getHomePageURL();
    if (!empty ($node_homepage))
    {
        $html .= "<a href='$node_homepage'>";
    }
    $html .= $node->getName();
    if (!empty ($node_homepage))
    {
        $html .= "</a>\n";
    }
    $html .= "</span>";
    foreach ($contents as $content)
    {
        // Check for content requirements to show the "Show all contents" link
        if (!$show_more_link) {
            if ($content->getObjectType() == "ContentGroup") {
                if (method_exists($content, "isArtisticContent") && method_exists($content, "isLocativeContent")) {
                    if ($content->isArtisticContent() && $content->isLocativeContent()) {
                        $show_more_link = true;
                    }
                }
            }
        }

        if ($content->isDisplayableAt($node)) {
            $html .= "<div class='portal_content'>\n";
            $html .= $content->getUserUI();
            $html .= "</div>\n";
        }
    }
    $html .= "</div>\n";
}

/* User section */
if($current_user)
{
    $contents = User :: getCurrentUser()->getAllContent();
    if($contents)
    {
        $html .= "<div class='portal_user_section'>\n";
        $html .= "<h1>"._("My content")."</h1>\n";
        foreach ($contents as $content)
        {
            $html .= "<div class='portal_content'>\n";
            $html .= $content->getUserUI();
            $html .= "</div>\n";
        }
        $html .= "</div>\n";
    }
}

// Hyperlinks to full content display page
if ($show_more_link) {
    $html .= "<a href='" . BASE_SSL_PATH . "content/?gw_id={$current_node_id}'>"._("Show all available contents for this hotspot")."</a>"."\n";
}

$html .= "<div style='clear:both;'></div>";
$html .= "</div>\n";

$ui->setMainContent($html);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
