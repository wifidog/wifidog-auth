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

/** Représente un Langstring en particulier, ne créez pas un objet langstrings si vous n'en avez pas spécifiquement besoin 
 */
class Picture extends File
{
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
	}

	/**Affiche l'interface d'administration de l'objet */
	function getAdminUI($subclass_admin_interface = null)
	{
        $html = '';
        $html .= "<div class='admin_class'>Picture (".get_class($this)." instance)</div>\n";
        
        // Show File admin UI + display the picture
        $html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_data'>\n";
        $html .= "<div class='admin_section_title'>"._("Picture preview")." : </div>\n";
		$html .= "<img src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
		$html .= "</div>\n";
        $html .= "</div>\n";

        $html .= $subclass_admin_interface;
        return parent :: getAdminUI($html);
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI()
	{
        $html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>Picture (".get_class($this)." instance)</div>\n";
		$html .= "<img class='user_ui_picture' src='".htmlentities($this->getFileUrl())."' alt='".$this->getFileName()."''>";
		$html .= "</div>\n";
        return $html;
	}

} /* end class File */
?>