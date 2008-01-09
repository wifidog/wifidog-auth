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

/** This file contains the code for the MainUI class, as well as GUI exception handling.
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * @internal We put a call to validate_schema() here so it systematically called
 * from any UI page, but not from any machine readable pages
 */
require_once ('include/schema_validate.php');
validate_schema();
require_once ('include/process_login_out_form.php');

/** Protects against secondary exceptions during exception display */
function exception_output_helper($html) {
    try{
        // Load MainUI class
        $ui = MainUI::getObject();
        $ui->addContent('main_area_middle', $html);
        $ui->display();
    }
    catch (Exception $e)
    {
        echo "Notice:  A secondary Exception was thrown trying to display the Exception graphically using MainUI.  Here is the text-only output:<br/>";
        echo $html;
    }
}
/**
 * This custom exception handler is only called if the MainUI file is included
 */
function wifidog_exception_handler($e) {
    $exceptionClass = get_class($e);
    switch ($exceptionClass) {
        case 'SecurityException':
            $html = null;
            $html .= "<div class = 'errormsg'>\n";
            $user = User::getCurrentUser();
            if($user) {
                $html .= sprintf(_("Your current user (%s) does not have the required level of access.  Please login with with a user with the required permission(s) to try this operation again."),$user->getUserName());
            }
            else {
                $html .= sprintf(_("You didn't log-in or your session timed-out.  Please login to try this operation again."));
            }
            $html .= "</div>\n";
            $html .= "<form name='login_form' id='login_form' action='' method='post'>\n";
            require_once ('classes/Authenticator.php');
            $html .= Authenticator::getLoginUI();
            $html .= "</form>\n";
            $html .= "<div class = 'warningmsg'>\n";
            $html .= sprintf(_("%s"), $e->getMessage());
            $html .= "<pre>\n";
            $html .= sprintf(_("%s was thrown in %s, line %d\n"), get_class($e), $e->getFile(), $e->getLine());
            $html .= $e->getTraceAsString();
            $html .= "</pre>\n";
            $html .= "</div>\n";
            exception_output_helper($html);

            break;
        default:
            //@ob_clean();
            $html = null;
            $html .= "<div class = 'errormsg'>\n";
            $html .= sprintf(_("Detailed error was:  Uncaught %s %s (%s) thrown in file %s, line %d"),get_class($e), $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
            $html .= "<pre>\n";
            $html .= $e->getTraceAsString();
            $html .= "</pre>\n";
            $html .= "</div>\n";
            exception_output_helper($html);
    }
}

set_exception_handler('wifidog_exception_handler');
/**
 * If the database doesn't get cleaned up by a cron job, we'll do now
 */
if (CONF_USE_CRON_FOR_DB_CLEANUP == false) {
    garbage_collect();
}
// Clear the buffer
@ob_clean();
/**
 * Load required file
 */
require_once('include/init_php.php');

/**
 * Load required files
 */
require_once('classes/Session.php');
require_once('classes/SmartyWifidog.php');
require_once('classes/User.php');
require_once('include/language.php');

/**
 * Singleton class for managing headers, footers, stylesheet, etc.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class MainUI {
    /** holder for the singleton */
    private static $object;

    /**
     * Content to be displayed the page
     *
     * @var array
     * @access private
     */
    private $_contentDisplayArray;

    /**
     * Content to be displayed on the page, before ordering
     *
     * @var array
     * @access private
     */
    private $_contentArray;

    /**
     * Object for Smarty class
     *
     * @var object
     * @access private
     */
    private $smarty;

    /**
     * Title of HTML page
     *
     * @var string
     * @access private
     */
    private $title;
    /**
     * Additional class of the <body> of the HTML page
     */
    private $_pageName;

    /** list of URLs to stylesheet to be included */
    private $stylesheetUrlArray = array ();

    /**
     * Headers of HTML page
     *
     * @var private
     * @access private
     */
    private $_htmlHeaders;

    /**
     * Defines if tool section of HTML page is enabled or not
     *
     * @var bool
     * @access private
     */
    private $_toolSectionEnabled = true;

    /**
     * Scripts for the footer
     *
     * @var array
     * @access private
     */
    private $_footerScripts = array ();

    private $_shrinkLeftArea = false;
    /**
     * Get the MainUI object
     * @return object The MainUI object
     */
    public static function &getObject() {
        if (self :: $object == null) {
            self :: $object = new self();
        }
        return self :: $object;
    }
    /**
     * Constructor
     *
     * @return void
     *
     * @access public
     */
    private function __construct() {
        $db = AbstractDb :: getObject();
        // Init Smarty
        $this->smarty = SmartyWifidog :: getObject();

        // Set default title
        $this->title = Network :: getCurrentNetwork()->getName() . ' ' . _("authentication server");
        // Init the content array
        $current_content_sql = "SELECT display_area FROM content_available_display_areas\n";
        $rows = array ();
        $db->execSql($current_content_sql, $rows, false);
        foreach ($rows as $row) {
            $this->_contentDisplayArray[$row['display_area']] = '';
        }
    }

    /**
     * Add content to a structural area of the page
     *
     * @param string $display_area Structural area where content is to be
     * placed.  Must be one of the display aread defined in the
     * content_available_display_areas table
     *
     * @param string $content Either a Content object (recommended) or raw HTML content to be added to the area
     *
     * @param integer $display_order_index The order in which the content should
     * be displayed
     *
     * @return void
     */
    public function addContent($displayArea, $content, $displayOrderIndex = 1) {
        //echo "MainUI::addContent(): Debug: displayArea: $displayArea, displayOrderIndex: $displayOrderIndex, content: $content<br/>";
        if (!isset ($this->_contentDisplayArray[$displayArea])) {
            throw new exception(sprintf(_('%s is not a valid structural display area'), $displayArea));
        }
        $this->_contentArray[] = array (
        'display_area' => $displayArea,
        'display_order' => $displayOrderIndex,
        'content' => $content
        );
    }

    /** Private compare function for sorting the _contentArray() */
    private static function _contentArrayCmp($a, $b) {
        if ($a['display_order'] == $b['display_order']) {
            return 0;
        }
        return ($a['display_order'] < $b['display_order']) ? -1 : 1;
    }

    /** Main processing function do generate the final content.
     * It will successively call prepareGetUserUI() on all content objects,
     * and then getUserUI() on all objects.  Note that the point of calling
     * prepareGetUserUI is to allow that function to call methods of MainUI
     * (such ans changing headers, etc.).  However, please note that you should not
     * call MainUI::addContent() from prepareGetUserUI, as prepareGetUserUI() wouldn't
     * in turn get called on objects added this way.
     * Orders the content and put it in the _contentDisplayArray array
     *
     * @return void
     */
    private function generateDisplayContent() {
        //pretty_print_r($this->_contentArray);
        usort($this->_contentArray, array (
        $this,
        "_contentArrayCmp"
        ));

        //Fist pass (preparation pass)
        foreach ($this->_contentArray as $content_fragment) {
            $content = $content_fragment['content'];

            if (method_exists($content, 'prepareGetUserUI')) {
                //echo "<h1>prepareGetUserUI on ".$content->getId()."</h1>";
                $content->prepareGetUserUI();
            }
        }
        foreach ($this->_contentArray as $content_fragment) {
            $content = $content_fragment['content'];
            if (is_object($content)) {
                if (method_exists($content, 'getUserUI')) {
                    $this->_contentDisplayArray[$content_fragment['display_area']] .= $content->getUserUI();
                } else {
                    throw new exception("Object must implement getUserUI");
                }
            } else {
                $this->_contentDisplayArray[$content_fragment['display_area']] .= $content;
            }
        }

    }

    /**
     * Add the content marked "everywhere" from both the current node and the
     * current network.
     *
     * @return void
     */
    private function addEverywhereContent() {
        $db = AbstractDb :: getObject();
        // Get all network content and node "everywhere" content
        $content_rows = null;
        $network_id = $db->escapeString(Network :: getCurrentNetwork()->getId());
        $sql_network = "(SELECT content_id, display_area, display_order, subscribe_timestamp FROM network_has_content WHERE network_id='$network_id'  AND display_page='everywhere') ";
        $node = Node :: getCurrentNode();
        $sql_node = null;
        if ($node) {
            // Get all node content
            $node_id = $db->escapeString($node->getId());
            $sql_node = "UNION (SELECT content_id, display_area, display_order, subscribe_timestamp FROM node_has_content WHERE node_id='$node_id'  AND display_page='everywhere')";
        }
        $sql = "SELECT * FROM ($sql_network $sql_node) AS content_everywhere ORDER BY display_area, display_order, subscribe_timestamp DESC";

        $db->execSql($sql, $content_rows, false);
        if ($content_rows) {
            foreach ($content_rows as $content_row) {
                $content = Content :: getObject($content_row['content_id']);
                if ($content->isDisplayableAt($node)) {
                    $this->addContent($content_row['display_area'], $content, $content_row['display_order']);
                }
            }
        }

    }

    /**
     * Check if the tool section is enabled
     *
     * @return bool True or false
     *
     * @access public
     */
    public function isToolSectionEnabled() {
        return $this->_toolSectionEnabled;
    }

    /**
     * Check if the tool section is enabled
     *
     * @return bool True or false
     *
     * @access public
     */
    public function setToolSectionEnabled($status) {
        $this->_toolSectionEnabled = $status;
    }

    /**
     * Set the title of the HTML page
     *
     * @param string $title_string Title of the HTML page
     *
     * @return void
     *
     * @access public
     */
    public function setTitle($title_string) {
        $this->title = $title_string;
    }

    public function shrinkLeftArea() {
        $this->_shrinkLeftArea = true;
    }

    /**
     * Set the class name of the <body> of the resulting page.
     *
     * @param string $page_name_string The page name of the resulting page.  Must have no spaces.  ex:  portal, login, userprofile, etc.)
     *
     * @return void
     *
     * @access public
     */
    public function setPageName($page_name_string) {
        $this->_pageName = $page_name_string;
    }

    /**
     * Add content at the very end of the <body>.
     *
     * This is NOT meant to add footers or other display content, it is meant
     * to add <script></script> tag pairs that have to be executed only once
     * the page is loaded.
     *
     * @param string $script A piece of script surrounded by
     *                       <script></script> tags.
     *
     * @return void
     *
     * @access public
     */
    public function addFooterScript($script) {
        $this->_footerScripts[] = $script;
    }

    /**
     * Append HTML markup (normally <script> elements) to the <head> element to
     * the final page.
     *
     * @param string $headers_string HTML markup suitable for the HEAD element
     *
     * @return void
     *
     * @access public
     */
    public function appendHtmlHeadContent($headers_string) {
        $this->_htmlHeaders .= $headers_string . "\n";
    }

    /**
     * Add a stylesheet URL to the main page
     *
     * @param string Stylesheet URL
     *@param media The target media of the selected strylesheet (print, screen,etc.)
     * @return void
     *
     * @access public
     */
    public function appendStylesheetURL($stylesheet_url, $media=null) {
        //Note:  using the URL as value AND key will remove duplicate while keeping the stylesheet inclusion order, because of the way foreach is implemented in PHP
        $this->stylesheetUrlArray[$stylesheet_url]['href'] = $stylesheet_url;
                $this->stylesheetUrlArray[$stylesheet_url]['media'] = $media;
    }

    /**
     * Get the content to be displayed in the tool pane
     *
     * @return string HTML markup
     *
     * @access private
     */
    private function getToolContent() {

        $session = Session :: getObject();
        $AVAIL_LOCALE_ARRAY = LocaleList::getAvailableLanguageArray();

        // Init values
        $html = "";
        $_gwId = null;
        $_gwAddress = null;
        $_gwPort = null;
        $_selected = "";
        $_languageChooser = array ();

        // Init ALL smarty SWITCH values
        $this->smarty->assign('sectionSTART', false);
        $this->smarty->assign('sectionLOGIN', false);

        // Set section of Smarty template
        $this->smarty->assign('sectionSTART', true);

        // Get information about user
        $_currentUser = User :: getCurrentUser();
        $_currentUser?$this->smarty->assign('userListUI', $_currentUser->getListUI()):$this->smarty->assign('userListUI', "");
        $this->smarty->assign('logoutParameters', "");
        $this->smarty->assign('loginParameters', "");
        $this->smarty->assign('formAction', "");
        $this->smarty->assign('toolContent', "");
        $this->smarty->assign('accountInformation', "");
        $this->smarty->assign('techSupportInformation', "");
        $this->smarty->assign('shrinkLeftArea', $this->_shrinkLeftArea);

        /*
         * Provide Smarty information about the user's login/logout status
         */

        if ($_currentUser != null) {
            // User is logged in

            // Detect gateway information
            $_gwId = $session->get(SESS_GW_ID_VAR);
            $_gwAddress = $session->get(SESS_GW_ADDRESS_VAR);
            $_gwPort = $session->get(SESS_GW_PORT_VAR);

            // If gateway information could be detected tell them to Smarty
            if ($_gwId && $_gwAddress && $_gwPort) {
                $this->smarty->assign('logoutParameters', "&amp;gw_id=" . $_gwId . "&amp;gw_address=" . $_gwAddress . "&amp;gw_port=" . $_gwPort);
            }
        } else {
        }

        /*
         * Provide Smarty information for the language chooser
         */

        // Assign the action URL for the form
        $this->smarty->assign('formAction', htmlspecialchars($_SERVER['REQUEST_URI']));

        foreach ($AVAIL_LOCALE_ARRAY as $_langIds => $_langNames) {
            if (Locale :: getCurrentLocale()->getId() == $_langIds) {
                $_selected = ' selected="selected"';
            } else {
                $_selected = "";
            }
            $langName = "{$_langNames[0]}";
            $_languageChooser[] = '<option value="' . $_langIds . '"' . $_selected . '>' . $langName . '</option>';
        }

        // Provide Smarty all available languages
        $this->smarty->assign('languageChooser', $_languageChooser);

        // Compile HTML code
        $html = $this->smarty->fetch("templates/classes/MainUI_ToolContent.tpl");

        return $html;
    }

    /**
     * Display the main page
     *
     * @return void
     *
     * @access public
     * @internal Uses a few request parameters to display debug information.
     * If $_REQUEST['debug_request'] is present, it will print out the
     * $_REQUEST array at the top of the page.
     */
    public function display() {
        $db = AbstractDb :: getObject();
        // Init values
        // Asign base CSS and theme pack CSS stylesheet
        $this->appendStylesheetURL(BASE_THEME_URL . STYLESHEET_NAME);
                $this->appendStylesheetURL(BASE_THEME_URL . PRINT_STYLESHEET_NAME, 'print');
        $networkThemePack = Network :: getCurrentNetwork()->getThemePack();
        if ($networkThemePack) {
            $this->appendStylesheetURL($networkThemePack->getStylesheetUrl());
        }

        //Handle content (must be done before headers and anything else is handled)
        /*
        * Build tool pane if it has been enabled
        */
        if ($this->isToolSectionEnabled()) {
            $this->addContent('left_area_top', $this->getToolContent());
            //Display main menu
            require_once('classes/Menu.php');
            $this->smarty->assign('siteMenu', Menu::getObject()->getUserUI());
        }

        $this->addEverywhereContent();
        $this->generateDisplayContent();

        // Init ALL smarty values
        $this->smarty->assign('htmlHeaders', "");
        // $this->smarty->assign('isSuperAdmin', false);
        // $this->smarty->assign('isOwner', false);
        $this->smarty->assign('debugRequested', false);
        $this->smarty->assign('debugOutput', "");
        $this->smarty->assign('footerScripts', array ());

        // Add HTML headers
        $this->smarty->assign('htmlHeaders', $this->_htmlHeaders);

        // Asign title
        $this->smarty->assign('title', $this->title);

        // Asign CSS class for body
        $this->smarty->assign('page_name', $this->_pageName);

        $this->smarty->assign('stylesheetUrlArray', $this->stylesheetUrlArray);

        /*
         * Allow super admin to display debug output if requested by using
         * $_REQUEST['debug_request']
         */

        // Provide footer scripts to Smarty
        $this->smarty->assign('footerScripts', $this->_footerScripts);

        // Add SQL queries log (must be done manually here at the very end to catch everything)
        if (defined("LOG_SQL_QUERIES") && LOG_SQL_QUERIES == true)
        $this->_contentDisplayArray['page_footer'] .= $db->getSqlQueriesLog();

        // Provide the content array to Smarty
        $this->smarty->assign('contentDisplayArray', $this->_contentDisplayArray);

        // Compile HTML code and output it
        $this->smarty->display("templates/classes/MainUI_Display.tpl");
    }

    /**
     * Display a generic error message
     *
     * @param string $errmsg                  The error message to be displayed
     * @param bool   $show_tech_support_email Defines wether to show the link of
     *                                        the tech-support
     *
     * @return void
     *
     * @access public
     */
    function displayError($errmsg, $show_tech_support_email = true) {
        // Init ALL smarty values
        $this->smarty->assign("error", "");
        $this->smarty->assign("show_tech_support_email", false);
        $this->smarty->assign("tech_support_email", "");

        // Define needed error content
        $this->smarty->assign("error", $errmsg);

        if ($show_tech_support_email) {
            $this->smarty->assign("show_tech_support_email", true);
            $this->smarty->assign("tech_support_email", Network :: getCurrentNetwork()->getTechSupportEmail());
        }

        /*
         * Output the error message
         */
        $html = $this->smarty->fetch("templates/sites/error.tpl");

        $this->addContent('page_header', $html);
        $this->display();
    }

    static public function redirect($redirect_url, $redirect_to_title = null, $timeout = 60) {
        if (!$redirect_to_title) {
            $network = Network :: getCurrentNetwork();
            $redirect_to_title = $network ? sprintf(_("%s Login"), $network->getName()) : _("Login");
        }

        header("Location: $redirect_url");
        echo "<html>\n" . "<head><meta http-equiv='Refresh' content='$timeout; URL=$redirect_url'/></head>\n" . "<body>\n" . "<noscript>\n" . "<span style='display:none;'>\n" . "<h1>" . $redirect_to_title . "</h1>\n" . sprintf(_("Click <a href='%s'>here</a> to continue"), $redirect_url) . "<br/>\n" . _("The transfer from secure login back to regular http may cause a warning.") . "\n" . "</span>\n" . "</noscript>\n" . "</body>\n" . "</html>\n";
        exit;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */