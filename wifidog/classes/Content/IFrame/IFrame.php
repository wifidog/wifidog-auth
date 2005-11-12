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
/**@file IFrame.php
 * @author Copyright (C) 2005 François Prouulx <francois.proulx@gmail.com>
*/

require_once BASEPATH.'classes/Content.php';
error_reporting(E_ALL);

/** 
 * An IFrame can integrate external HTML content from a given URL. 
 */
class IFrame extends Content
{
	/**Constructor
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;
		$this->mDb = & $db;

		$content_id = $db->EscapeString($content_id);
		$sql = "SELECT * FROM iframes WHERE iframes_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			/*Since the parent Content exists, the necessary data in content_group had not yet been created */
			$sql = "INSERT INTO iframes (iframes_id) VALUES ('$content_id')";
			$db->ExecSqlUpdate($sql, false);

			$sql = "SELECT * FROM iframes WHERE iframes_id='$content_id'";
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
			}

		}
		$this->iframe_row = $row;
	}

	/**
	 * Return the IFrame URL
	*/
	function getUrl()
	{
		return $this->iframe_row['url'];
	}

	function setUrl($url)
	{
		$url = $this->mDb->EscapeString($url);
		$this->mDb->ExecSqlUpdate("UPDATE iframes SET url = '{$url}' WHERE iframes_id = '{$this->getId()}';");
	}
	/**
This function is there so that displayUserUi will work fine with the IFrameRest object.  Do NOT delete it.
	*/
	function getGeneratedUrl()
	{
		return $this->getUrl();;
	}
	function getWidth()
	{
		return $this->iframe_row['width'];
	}

	function setWidth($width)
	{
		if (empty ($width) || is_numeric($width))
		{
			empty ($width) ? $width = "NULL" : $width = $this->mDb->EscapeString($width);
			$this->mDb->ExecSqlUpdate("UPDATE iframes SET width =".$width." WHERE iframes_id='".$this->getId()."'", false);
			$this->refresh();
		}
	}

	function getHeight()
	{
		return $this->iframe_row['height'];
	}

	function setHeight($height)
	{
		if (empty ($height) || is_numeric($height))
		{
			empty ($height) ? $height = "NULL" : $height = $this->mDb->EscapeString($height);
			$this->mDb->ExecSqlUpdate("UPDATE iframes SET height =".$height." WHERE iframes_id='".$this->getId()."'", false);
			$this->refresh();
		}
	}

	/**
	 * Retrieves Admin UI for IFrame
	*/
	function getAdminUI($subclass_admin_interface=null)
	{
		$html = '';
		$html .= "<div class='admin_class'>IFrame (".get_class($this)." instance)</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= "<div class='admin_section_title'>"._("Width (suggested width is 600 (pixels))")." : </div>\n";
		$name = "iframe_".$this->id."_width";
		$html .= "<input type='text' name='{$name}' value='{$this->getWidth()}'>";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "iframe_".$this->id."_height";
		$html .= "<div class='admin_section_title'>"._("Height (suggested width is 400 (pixels))")." : </div>\n";
		$html .= "<input type='text' name='{$name}' value='{$this->getHeight()}'>";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
		$html .= "<div class='admin_section_title'>"._("HTML content URL")." : </div>\n";
		$name = "iframe_".$this->id."_url";
		$html .= "<input type='text' size=80 name='$name' value='".$this->getUrl()."'\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= $subclass_admin_interface;
		return parent :: getAdminUI($html);
	}

	function processAdminUI()
	{
		if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
		{
			parent :: processAdminUI();

			// If the URL is not empty
			$name = "iframe_".$this->id."_url";
			if (!empty ($_REQUEST[$name]))
			{
				$this->setUrl($_REQUEST[$name]);
			}
			else
				$this->setUrl("");
				
			$name = "iframe_".$this->id."_width";
			$this->setWidth(intval($_REQUEST[$name]));
			$name = "iframe_".$this->id."_height";
	    		$this->setHeight(intval($_REQUEST[$name]));
		}
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI($subclass_user_interface = null)
	{
		$html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>IFrame (".get_class($this)." instance)</div>\n";
		$html .= "<iframe width='{$this->getWidth()}' height='{$this->getHeight()}' frameborder='1' src='{$this->getGeneratedUrl()}'>"._("Your browser does not support IFrames.")."</iframe>\n";
		$html .= $subclass_user_interface;
		$html .= "</div>\n";
		return parent :: getUserUI($html);
	}
	
	/** Reloads the object from the database.  Should normally be called after a set operation.
	 * This function is private because calling it from a subclass will call the
	 * constructor from the wrong scope */
	private function refresh()
	{
		$this->__construct($this->id);
	}
} /* end class Langstring */
?>