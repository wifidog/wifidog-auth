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
/**@file MainUI.php
 * @author Copyright (C) 2005 Technologies Coeus inc.
 */
require_once BASEPATH.'include/common.php';
	/** @note We put a call to validate_schema() here so it systematically called
 * from any UI page, but not from any machine readable pages 
 */ 
 		require_once BASEPATH.'include/schema_validate.php';
 		validate_schema();
		
if (CONF_USE_CRON_FOR_DB_CLEANUP == false)
{
	garbage_collect();
}

require_once BASEPATH.'include/common_interface.php';

/** Style contains functions managing headers, footers, stylesheet, etc.
 */
class MainUI
{
	private $main_content; /**<Content to be displayed in the main pane */
	private $tool_content; /**<Content to be displayed in the tool pane */
	private $smarty;
	private $title;
	private $html_headers;
	private $tool_section_enabled = true;
	private $footer_scripts = array ();

	function __construct()
	{
		$this->smarty = new SmartyWifidog();
		$this->title = Network :: getCurrentNetwork()->getName().' '._("authentication server"); //Default title
	}
	
	/** Check if the tool section is enabled
	 * 
	 */
	public function isToolSectionEnabled()
	{
		return $this->tool_section_enabled;
	}
	
	public function setToolSectionEnabled($status)
	{
		$this->tool_section_enabled = $status;
	}

	/** Set the content to be displayed in the main pane */
	public function setMainContent($html)
	{
		$this->main_content = $html;
	}

	/** Set the title of the page */
	public function setTitle($title_string)
	{
		$this->title = $title_string;
	}

	/** Add content at the very end of the <body>.  This is NOT meant to add footers or other display content, it is meant to add <script></script> tag pairs that have to be executed only once the page is loaded.
	 * @param $script A piece of script surrounded by <script></script> tags. */
	public function addFooterScript($script)
	{
		$this->footer_scripts[] = $script;
	}

	/** Set the HTML page headers */
	public function setHtmlHeader($headers_string)
	{
		$this->html_headers = $headers_string;
	}

	/** Set the section to be displayed in the tool pane */
	public function setToolSection($section)
	{
		switch ($section)
		{
			case "ADMIN" :
				$current_user = User :: getCurrentUser();
				$html = '';
				$html .= "<ul class='admin_menu_list'>\n";

				if ($current_user && $current_user->isSuperAdmin())
				{
					$html .= "<li><a href='user_log.php'>"._("User logs")."</a></li>\n";
					$html .= "<li><a href='online_users.php'>"._("Online Users")."</a></li>\n";
					$html .= "<li><a href='user_stats.php'>"._("Cumulative user statistics")."</a></li>\n";
					$html .= "<li><a href='hotspot_log.php'>"._("Hotspot logs")."</a></li>\n";
					$html .= "<li><a href='import_user_database.php'>"._("Import NoCat user database")."</a></li>\n";
					$html .= "<li><a href='content_admin.php'>"._("Content manager")."</a></li>\n";
				}

				$html .= "</ul>\n";

				// If the user is super admin OR owner of at least one hotspot show the menu
				if ($current_user && ($current_user->isSuperAdmin() || $current_user->isOwner()))
				{
					/* Node admin */
					$html .= "<div class='admin_section_container'>\n";
					$html .= '<form action="'.GENERIC_OBJECT_ADMIN_ABS_HREF.'" method="post">';
					$html .= "<div class='admin_section_title'>"._("Node administration:")." </div>\n";

					$html .= "<div class='admin_section_data'>\n";

					if ($current_user->isSuperAdmin())
						$sql_additional_where = '';
					else
						$sql_additional_where = "AND node_id IN (SELECT node_id from node_stakeholders WHERE is_owner = true AND user_id='".$current_user->getId()."')";
					$html .= "<div id='NodeSelector'>\n";
					$html .= Node :: getSelectNodeUI('object_id', $sql_additional_where);
					$html .= "</div>\n";
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";

					$html .= "<input type='hidden' name='object_class' value='Node'>\n";
					$html .= "<input type='hidden' name='action' value='edit'>\n";
					$html .= "<input type='submit' name='edit_submit' value='"._("Edit")."'>\n";

					$html .= "</div>\n";
					$html .= '</form>';
					$html .= "</div>\n";
				}

				/* Network admin */
				if ($current_user && $current_user->isSuperAdmin())
				{
					$html .= "<div class='admin_section_container'>\n";
					$html .= '<form action="'.GENERIC_OBJECT_ADMIN_ABS_HREF.'" method="post">';
					$html .= "<div class='admin_section_title'>"._("Network administration:")." </div>\n";

					$html .= "<div class='admin_section_data'>\n";
					$html .= "<input type='hidden' name='action' value='edit'>\n";
					$html .= "<input type='hidden' name='object_class' value='Network'><br>\n";
					$html .= Network :: getSelectNetworkUI('object_id');
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";

					$html .= "<input type=submit name='edit_submit' value='"._("Edit")."'>\n";
					$html .= "</div>\n";
					$html .= '</form>';
					$html .= "</div>\n";
				}
				break;
			default :
				$html .= "<p class='errormsg'>"._("Unknown section:")." $section</p>\n";

		}
		$this->tool_content = $html;
	}

	/** Set the content to be displayed in the tool pane */
	public function setToolContent($html)
	{
		$this->tool_content = $html;
	}

	/** Get the content to be displayed in the tool pane
	 * @param section, one of:  START, LOGIN, 
	 * @return HTML markup */
	private function getToolContent($section = 'START')
	{
		global $session;
		$html = '';
		switch ($section)
		{
			case "NONE" :
				break;
			case "LOGIN" :
				break;
			case "START" :
				$html .= '<div id="tool_section">'."\n";
				$html .= '<div class="tool_user_info">'."\n";
				$html .= '<span class="tool_user_info">'."\n";
				$user = User :: getCurrentUser();
				if ($user != null)
				{
					$html .= '<p>'._("Logged in as:").' '.$user->getUsername().'</p>'."\n";
					$html .= '<a class="administration" HREF="'.BASE_SSL_PATH.'user_profile.php"><img class="administration" src="'.BASE_SSL_PATH.'images/profile.gif" border="0"> '._("My Profile").'</a>'."\n";

					$gw_id = $session->get(SESS_GW_ID_VAR);
					$gw_address = $session->get(SESS_GW_ADDRESS_VAR);
					$gw_port = $session->get(SESS_GW_PORT_VAR);

					if ($gw_id && $gw_address && $gw_port)
						$html .= '<a class="administration" HREF="'.BASE_SSL_PATH.'login/?logout=true&gw_id='.$gw_id.'&gw_address='.$gw_address.'&gw_port='.$gw_port.'"><img class="administration" src="'.BASE_SSL_PATH.'images/logout.gif" border="0"> '._("Logout").'</a>'."\n";
					else
						$html .= '<a class="administration" HREF="'.BASE_SSL_PATH.'login/?logout=true"><img class="administration" src="'.BASE_SSL_PATH.'images/logout.gif" border="0"> '._("Logout").'</a>'."\n";

				}
				else
				{
					$gw_id = !empty ($_REQUEST['gw_id']) ? $_REQUEST['gw_id'] : $session->get(SESS_GW_ID_VAR);
					$gw_address = !empty ($_REQUEST['gw_address']) ? $_REQUEST['gw_address'] : $session->get(SESS_GW_ADDRESS_VAR);
					$gw_port = !empty ($_REQUEST['gw_port']) ? $_REQUEST['gw_port'] : $session->get(SESS_GW_PORT_VAR);

					// If the user connects physically ( through a gateway don't show the confusing login message ) 
					if (empty ($gw_id) || empty ($gw_address) || empty ($gw_port))
						$html .= '<p>'._("I'm NOT at a hotspot.").'<br><a href="'.BASE_SSL_PATH.'login/">'._("I would like to login virtually.").'</a></p>'."\n";
					else
						$html .= '<p>'._("NOT logged in.").'<br><a href="'.BASE_SSL_PATH.'login/?gw_id='.$gw_id.'&gw_address='.$gw_address.'&gw_port='.$gw_port.'">'._("Login to this hotspot.").'</a></p>'."\n";
					$html .= '<a class="administration" HREF="'.Network :: getCurrentNetwork()->getHomepageURL().'"><img class="administration" src="'.BASE_SSL_PATH.'images/lien_ext.gif"> '.Network :: getCurrentNetwork()->getName().'</a>'."\n";
					$html .= '<a class="administration" HREF="'.BASE_SSL_PATH.'faq.php"><img class="administration" src="'.BASE_SSL_PATH.'images/where.gif"> '._("Where am I?").'</a>'."\n";
				}

				$html .= "</span>"."\n"; //End tool_user_info
				$html .= "</div>"."\n"; //End tool_user_info

				$html .= '<div class="navigation">'."\n";
				/*
				$html .= '<a href="index.php" class="navigation">'._("Start").'</a>'."\n";
				$html .= '<img class="separator" src="'.BASE_NON_SSL_PATH.'/images/separator.gif">'."\n";
				$html .= '<a href="users.php" class="navigation">'._("Users Online").'</a>'."\n";
				$html .= '<img class="separator" src="'.BASE_NON_SSL_PATH.'/images/separator.gif">'."\n";
				$html .= '<a href="news.php" class="navigation">'._("News").'</a>'."\n";
				$html .= '<img class="separator" src="'.BASE_NON_SSL_PATH.'/images/separator.gif">'."\n";
				$html .= '<a href="hotspots.php" class="navigation">'._("Hotspots").'</a>'."\n";
				*/
				$html .= '<span class="navigation">';
				$html .= Network :: getCurrentNetwork()->getName()." "._("Building your wireless community");
				$html .= '</span>';
				$html .= "</div>"."\n"; //End navigation

				$html .= '<div class="language">'."\n";
				$html .= '<form class="language" name="lang_form" method="post" action="'.$_SERVER['REQUEST_URI'].'">'."\n";
				$html .= _("Language:")."\n";
				$html .= "<select name='wifidog_language' onChange='javascript: document.lang_form.submit();'>"."\n";
				global $AVAIL_LOCALE_ARRAY; //From config file
				foreach ($AVAIL_LOCALE_ARRAY as $lang_ids => $lang_names)
				{
					if (Locale :: getCurrentLocale()->getId() == $lang_ids)
					{
						$selected = "SELECTED";
					}
					else
					{
						$selected = '';
					}
					$html .= '<option label="'.$lang_names.'" value="'.$lang_ids.'" '.$selected.'>'.$lang_names.'</option>'."\n";
				}
				$html .= "</select>"."\n";
				$html .= "</form>"."\n";

				$html .= "</div>"."\n"; //End language

				$html .= "<div class='tool_content'>"."\n";
				/******************************/
				$html .= $this->tool_content;
				/******************************/
				$html .= "</div>"."\n"; //End tool_content
				$html .= '<div class="avis">'."\n";
				$html .= '<span class="avis">'."\n";
				$html .= sprintf(_("Accounts on %s are and will stay completely free."), Network :: getCurrentNetwork()->getName());
				$html .= _("Please inform us of any problem or service interruption at:");
				$tech_support_email = Network :: getCurrentNetwork()->getTechSupportEmail();
				$html .= '<a href="mailto:'.$tech_support_email.'">'.$tech_support_email.'</a>'."\n";
				$html .= "</span>"."\n"; //End avis
				$html .= "</div>"."\n"; //End avis
				$html .= "</div>"."\n"; //End tool_section
				break;
			default :
				$html .= '<p class="errmsg">MainUI::getToolContent(): Unknown section!</p>'."\n";
		}
		return $html;
	}

	/** Display the page
	 * @note:  Uses a few request parameters to displaty debug information
	 * if $_REQUEST['debug_request'] is present, it will print out the $_REQUEST array at the top of the page */
	public function display()
	{
		$html = '';
		//$this->smarty->display(DEFAULT_CONTENT_SMARTY_PATH."header.html");

		/**** Headers ****/
		$html .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n";
		$html .= '<html>'."\n";
		$html .= '<head>'."\n";
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n";
		$html .= '<meta http-equiv="Pragma" CONTENT="no-cache">'."\n";
		$html .= '<meta http-equiv="Expires" CONTENT="-1">'."\n";
		// Add HTML headers
		$html .= "{$this->html_headers}";
		$html .= "<html>\n";
		$html .= "<head>\n";
		$html .= "<title>{$this->title}</title>\n";
		$html .= "<style type='text/css'>\n";
		if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.STYLESHEET_NAME))
		{
			$stylesheet_file = NODE_CONTENT_SMARTY_PATH.STYLESHEET_NAME;
		}
		else
		{
			$stylesheet_file = DEFAULT_CONTENT_SMARTY_PATH.STYLESHEET_NAME;
		}
		$html .= $this->smarty->fetch($stylesheet_file);
		$html .= "</style>\n";
		$html .= "</head>\n";

		$html .= "<body>"."\n";
		if(isset($_REQUEST['debug_request']))
		{
			$html .= '<pre>';
			$html .= print_r($_REQUEST,true);
			$html .= '</pre>';
		}
		$html .= '<div class="outer_container">'."\n";

		
		if($this->isToolSectionEnabled())
		{
			/**** Tools ******/
			$html .= $this->getToolContent();
			
			/**** Main section ****/
			$html .= "<div id='main_section'>"."\n";
			$html .= $this->main_content;
			$html .= "</div>"."\n"; //End main_section	
		}
		else
		{
			/**** Main section ****/
			$html .= $this->main_content;
		}

		$html .= '</div>'."\n"; //End outer_container

		foreach ($this->footer_scripts as $script)
		{
			$html .= "{$script}\n";
		}
		$html .= "</body>"."\n";
		$html .= "</html>"."\n";
		echo $html;

	}

	function displayError($errmsg)
	{
		$html = "<p>$errmsg</p>\n";
		$email = Network::getCurrentNetwork()->getTechSupportEmail();
		if(!empty($email))
		{
		$html .= "<p>"._("Please get in touch with ")."<a href='{$email}'>{$email}</a></p>";
		}
		$this->setMainContent($html);
		$this->display();
	}

} //End class
?>