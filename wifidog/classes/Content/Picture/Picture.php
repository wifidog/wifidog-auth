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
/**@file Picture.php
 * @author Copyright (C) 2005 François Proulx, Technologies Coeus inc.
*/

require_once BASEPATH.'classes/Content.php';

error_reporting(E_ALL);

/** Represents an Image
 */
class Picture extends File
{
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;

		$content_id = $db->EscapeString($content_id);
		$sql = "SELECT * FROM pictures WHERE pictures_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			/*Since the parent Content exists, the necessary data in content_group had not yet been created */
			$sql = "INSERT INTO pictures (pictures_id) VALUES ('$content_id')";
			$db->ExecSqlUpdate($sql, false);

			$sql = "SELECT * FROM pictures WHERE pictures_id='$content_id'";
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
			}

		}
		$this->mBd = & $db;
		$this->pictures_row = $row;
	}
	
	function getWidth()
	{
		return $this->pictures_row['width'];
	}
	
	function setWidth($width)
	{
		if(empty($width) || is_numeric($width))
		{
			empty($width) ? $width = "NULL" : $width = $this->mBd->EscapeString($width) ;
			$this->mBd->ExecSqlUpdate("UPDATE pictures SET width =".$width." WHERE pictures_id='".$this->getId()."'", false);
			$this->refresh();
		}
	}
	
	function getHeight()
	{
		return $this->pictures_row['height'];
	}
	
	function setHeight($height)
	{
		if(empty($height) || is_numeric($height))
		{
			empty($height) ? $height = "NULL" : $height = $this->mBd->EscapeString($height) ;
			$this->mBd->ExecSqlUpdate("UPDATE pictures SET height =".$height." WHERE pictures_id='".$this->getId()."'", false);
			$this->refresh();
		}
	}

	/**Affiche l'interface d'administration de l'objet */
	function getAdminUI($subclass_admin_interface = null)
	{
        $html = '';
        $html .= "<div class='admin_class'>Picture (".get_class($this)." instance)</div>\n";
        
        $html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
        $html .= "<div class='admin_section_title'>"._("Width (leave empty if you want to keep original width)")." : </div>\n";
		$html .= "<input type='text' name='pictures_{$this->getId()}_width' value='{$this->getWidth()}'>";
		$html .= "</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
        $html .= "<div class='admin_section_title'>"._("Height (leave empty if you want to keep original height)")." : </div>\n";
		$html .= "<input type='text' name='pictures_{$this->getId()}_height' value='{$this->getHeight()}'>";
		$html .= "</div>\n";
        $html .= "</div>\n";
        
        // Show File admin UI + display the picture
        $html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
        $html .= "<div class='admin_section_title'>"._("Picture preview")." : </div>\n";
        
        $width = $this->getWidth();
		$height = $this->getHeight();
		
		if(empty($width))
			$width = "";
		else
			$width = "width='$width'";
		
		if(empty($height))
			$height = "";
		else
			$height = "height='$height'";
			
		$html .= "<img src='".htmlentities($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''>";
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
	    		
	    		$this->setWidth(intval($_REQUEST["pictures_{$this->getId()}_width"]));
	    		$this->setHeight(intval($_REQUEST["pictures_{$this->getId()}_height"]));
        }
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI()
	{
        $html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>Picture (".get_class($this)." instance)</div>\n";
		
		$width = $this->getWidth();
		$height = $this->getHeight();
		
		if(empty($width))
			$width = "";
		else
			$width = "width='$width'";
		
		if(empty($height))
			$height = "";
		else
			$height = "height='$height'";
			
		$html .= "<img src='".htmlentities($this->getFileUrl())."' $width $height alt='".$this->getFileName()."''>";
		$html .= "</div>\n";
        return $html;
	}
	/** Reloads the object from the database.  Should normally be called after a set operation.
	 * This function is private because calling it from a subclass will call the
	 * constructor from the wrong scope */
	private function refresh()
	{
		$this->__construct($this->id);
	}
} /* end class File */
?>