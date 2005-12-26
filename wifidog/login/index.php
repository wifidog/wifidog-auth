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
 * Login page
 *
 * The login page will both display the login page, and process login and logout
 * request.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire <bock@step.polymtl.ca> - Technologies Coeus
 * inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

/**
 * @ignore
 */
define('BASEPATH', '../');

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/Node.php';
require_once BASEPATH.'classes/User.php';
require_once BASEPATH.'classes/Network.php';

/* Start general request parameter processing section */
$node = null;
if (!empty ($_REQUEST['gw_id']))
{
    $gw_id = $_REQUEST['gw_id'];

    try
    {
        $node = Node :: getObject($_REQUEST['gw_id']);
        $hotspot_name = $node->getName();
        $network = $node->getNetwork();
    }
    catch (Exception $e)
    {
        $smarty->assign("error", $e->getMessage());
        $smarty->display("templates/generic_error.html");
        exit;
    }
}
else
{
    /* Gateway ID is not set... Virtual login */
    $network = Network :: getCurrentNetwork();
}

isset ($_REQUEST["username"]) && $username = $_REQUEST["username"];
isset ($_REQUEST["gw_address"]) && $gw_address = $_REQUEST['gw_address'];
isset ($_REQUEST["gw_port"]) && $gw_port = $_REQUEST['gw_port'];
isset ($_REQUEST["gw_id"]) && $gw_id = $_REQUEST['gw_id'];

isset ($_REQUEST["gw_address"]) && $session->set(SESS_GW_ADDRESS_VAR, $_REQUEST['gw_address']);
isset ($_REQUEST["gw_port"]) && $session->set(SESS_GW_PORT_VAR, $_REQUEST['gw_port']);
isset ($_REQUEST["gw_id"]) && $session->set(SESS_GW_ID_VAR, $_REQUEST['gw_id']);

// Store original URL typed by user.
//TODO: manage this...
if (!empty ($_REQUEST['url']))
{
    $session->set(SESS_ORIGINAL_URL_VAR, $_REQUEST['url']);
}
/* End general request parameter processing section */

/* Start login process section.
 * If  successfull, the browser is redirected to another page */

/*  If this is a splash-only node, skip the login interface and log-in using the splash_only user */
if ($node && $node->isSplashOnly())
{
    $user = $network->getSplashOnlyUser();
    $token = $user->generateConnectionToken();
    User :: setCurrentUser($user);
    header("Location: http://".$_REQUEST['gw_address'].":".$_REQUEST['gw_port']."/wifidog/auth?token=$token");
}

/* Normal login process */
if (!empty ($_REQUEST['username']) && !empty ($_REQUEST['password']) && !empty ($_REQUEST['auth_source']))
{

    $errmsg = '';
    $username = $db->EscapeString($_REQUEST['username']);

    // Authenticating the user through the selected auth source.
    $network = Network :: processSelectNetworkUI('auth_source');

    $user = $network->getAuthenticator()->login($_REQUEST['username'], $_REQUEST['password'], $errmsg);
    if ($user != null)
    {
        if (isset ($_REQUEST['gw_address']) && isset ($_REQUEST['gw_port']))
        {
            /* Login from a gateway, redirect to the gateway to activate the token */
            $token = $user->generateConnectionToken();
            header("Location: http://".$_REQUEST['gw_address'].":".$_REQUEST['gw_port']."/wifidog/auth?token=$token");
        }
        else
        {
            /* Virtual login, redirect to the auth server homepage */
            header("Location: ".BASE_SSL_PATH);
        }
        exit;
    }
    else
    {
        $error = $errmsg;
    }
}
else
{
    //Note that this is executed even when we have just arrived at the login page, so the user is reminded to supply a username and password
    $error = _('Your must specify your username and password');
}
/* End login process section.*/

/* Start logout process section.
 * Once logged out, we display the login page */
if ((!empty ($_REQUEST['logout']) && $_REQUEST['logout'] == true) && ($user = User :: getCurrentUser()) != null)
{
    $network->getAuthenticator()->logout();
}
/* End logout process section. */

/* Start login interface section */
$html = '';
$html .= '<div id="login_form">'."\n";
/*if (empty ($_REQUEST['gw_id']))
{
    $html .= '<h1>'._("Virtual login").'</h1>'."\n";
}
else
{
    $html .= '<h1>'._("Welcome! Hotspot:")." $hotspot_name</h1>\n";
}
*/
$html .= '<h1><a href="'.BASE_SSL_PATH.'signup.php">'._("Create a free account").'</a></h1>';

$html .= '<h1>'._("I already have an account:").'</h1>';
$html .= '<p class="indent">'."\n";
$html .= '<form name="login_form" method="post">'."\n";
if ($node != null)
{
    $html .= '<input type="hidden" name="gw_address" value="'.$gw_address.'">'."\n";
    $html .= '<input type="hidden" name="gw_port" value="'.$gw_port.'">'."\n";
    $html .= '<input type="hidden" name="gw_id" value="'.$gw_id.'">'."\n";
}
$html .= Network :: getSelectNetworkUI('auth_source')."<br>\n";
$html .= _("Username (or email)").'<br>'."\n";
$html .= '<input type="text" name="username" value="'.$username.'" size="20" id="form_username"><br>'."\n";
$html .= _("Password").':<br>'."\n";
$html .= '<input type="password" name="password" size="20"><br>'."\n";
if ($error)
{
    $html .= '<div class="errormsg">'."\n";
    $html .= "$error\n";
    $html .= '</div>'."\n";
}

$html .= '<input class="submit" type="submit" name="submit" value="'._("Login").'">'."\n";
;
$html .= '</form>'."\n";
$html .= '</p>';

$html .= '<h1>'._("I'm having difficulties").':</h1>'."\n";
$html .= '<ul>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'lost_username.php">'._("I Forgot my username").'</a><br>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'lost_password.php">'._("I Forgot my password").'</a>'."\n";
$html .= '<li><a href="'.BASE_SSL_PATH.'resend_validation.php">'._("Re-send the validation email").'</a><br>'."\n";
if ($error)
{
    $class = 'class="help"';
}
else
{
    $class = '';
}
$html .= '<li><a href="'.BASE_SSL_PATH.'faq.php">'._("Frequently asked questions").'</a><br>'."\n";
$html .= '</ul>'."\n";
$html .= '</div>'."\n";
/* End login interface section */
$html .= '<script language="javascript">'."\n";
$html .= 'document.getElementById("form_username").focus()'."\n";
$html .= '</script>'."\n";

// HTML body
$hotspot_network_name = $network->getName();
$hotspot_network_url = $network->getHomepageURL();
$network_logo_banner_url = COMMON_CONTENT_URL.NETWORK_LOGO_BANNER_NAME;

$html_body = "<div class='login_body'><center>";

/* Node section */
// Get all node content
if (!empty($node))
{
    $hotspot_homepage_url = $node->getHomePageURL();
    if (!empty ($hotspot_homepage_url))
        $html_body .= "<a href=\"{$hotspot_homepage_url}\"><h1>{$node->getName()}</h1></a>";
    else
        $html_body .= "<h1>{$node->getName()}</h1>";

    $contents = $node->getAllContent(true, null, 'login_page');
    if ($contents)
    {
        $html_body .= "<table><tr><td>";
        $html_body .= "<div class='portal_node_section'>\n";

        foreach ($contents as $content)
        {
            if ($content->isDisplayableAt($node))
            {
                $html_body .= "<div class='portal_content'>\n";
                $html_body .= $content->getUserUI();
                $html_body .= "</div>\n";
            }
        }
        $html_body .= "</div>\n";
        $html_body .= "</td></tr></table>";
    }
}

$html_body .= "</center></div>";

require_once BASEPATH.'classes/MainUI.php';
$ui = new MainUI();
$ui->setToolContent($html);
$ui->setMainContent($html_body);
$ui->display();

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
