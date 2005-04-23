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
/**@file EmbeddedContent.php
 * @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>
 */
require_once BASEPATH.'classes/Content.php';

/** 
 * A generic embedded content container 
 * This object supports backward compatiblity fallback
 * 
 * Inspired by W3C WCGAG 2.0 recommendations
 * http://www.w3.org/TR/2004/WD-WCAG20-HTML-TECHS-20041119/#embed
 *
 * And Macromedia recommendations for backward compatibility
 * http://www.macromedia.com/cfusion/knowledgebase/index.cfm?id=tn_12701
 * */
class EmbeddedContent extends Content
{
	protected function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;
		$content_id = $db->EscapeString($content_id);

		$sql = "SELECT * FROM embedded_content WHERE embedded_content_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			/*Since the parent Content exists, the necessary data in content_group had not yet been created */
			$sql = "INSERT INTO embedded_content (embedded_content_id) VALUES ('$content_id')";
			$db->ExecSqlUpdate($sql, false);
			$sql = "SELECT * FROM embedded_content WHERE embedded_content_id='$content_id'";
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
			}

		}
		
		$this->mBd = &$db;
		$this->setIsTrivialContent(true);
		$this->setIsPersistent(false);
		$this->embedded_content_row = $row;
	}

	public function getAdminUI($subclass_admin_interface = null)
	{
		$html = '';
		$html .= "<div class='admin_class'>EmbeddedContent (".get_class($this)." instance)</div>\n";

		/* Embedded content Content */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Embedded content")." : <br></div>\n";
		$html .= "<div class='admin_section_data'>\n";
		if (empty ($this->embedded_content_row['embedded_file_id']))
		{
			// Mandate File
			$html .= self :: getNewContentUI("embedded_file_{$this->id}_new", "File");
			$html .= "</div>\n";
		}
		else
		{
			$embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
			$html .= $embedded_content_file->getAdminUI();
			$html .= "</div>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "embeddedcontent_".$this->id."_embedded_file_erase";
			
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Attributes")." : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= "<br><i>"._("It is recommended to specify at least <b>width='x' height='y'</b> as attributes")."</i><br>\n";
			$html .= '<textarea name="embedded_content_attributes'.$this->getId().'" cols="60" rows="3">'.htmlspecialchars($this->getAttributes(), ENT_QUOTES, 'UTF-8').'</textarea>';
			$html .= "</div>\n";
			$html .= "</div>\n";
			
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Parameters")." : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= '<br><textarea name="embedded_content_parameters'.$this->getId().'" cols="60" rows="3">'.htmlspecialchars($this->getParameters(), ENT_QUOTES, 'UTF-8').'</textarea>';
			$html .= "</div>\n";
			$html .= "</div>\n";
			
			$html .= "<input type='submit' name='$name' value='"._("Delete")."'>"; //  onclick='submit();'
			$html .= "</div>\n";
		}
		$html .= "</div>\n";

		/* Fallback content */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Fallback content (Can be another embedded content to create a fallback hierarchy)")." : <br></div>\n";
		$html .= "<div class='admin_section_data'>\n";
		if (empty ($this->embedded_content_row['fallback_content_id']))
		{
			$html .= self :: getNewContentUI("fallback_content_{$this->id}_new");
			$html .= "</div>\n";
		}
		else
		{
			$fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
			$html .= $fallback_content->getAdminUI();
			$html .= "</div>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "fallback_content_".$this->id."_fallback_content_erase";
			$html .= "<input type='submit' name='$name' value='"._("Delete")."'>"; // onclick='submit();'
			$html .= "</div>\n";
		}
		$html .= "</div>\n";

		$html .= $subclass_admin_interface;
		return parent :: getAdminUI($html);
	}

	function processAdminUI()
	{
		parent :: processAdminUI();

		global $db;
		if (empty ($this->embedded_content_row['embedded_file_id']))
		{
			$embedded_content_file = self :: processNewContentUI("embedded_file_{$this->id}_new");
			if ($embedded_content_file != null)
			{
				$embedded_content_file_id = $embedded_content_file->GetId();
				$db->ExecSqlUpdate("UPDATE embedded_content SET embedded_file_id = '$embedded_content_file_id' WHERE embedded_content_id = '$this->id'", FALSE);
			}
			else
			{
				echo _("You MUST choose a File object or any of its siblings.");
				$embedded_content_file->delete($errmsg);
			}
		}
		else
		{
			$embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
			$name = "embeddedcontent_".$this->id."_embedded_file_erase";
			if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
			{
				$db->ExecSqlUpdate("UPDATE embedded_content SET embedded_file_id = NULL WHERE embedded_content_id = '$this->id'", FALSE);
				$embedded_content_file->delete($errmsg);
			}
			else
			{
				$embedded_content_file->processAdminUI();
				
				$name = "embedded_content_attributes".$this->getId();
				$this->setAttributes($_REQUEST[$name]);
				
				$name = "embedded_content_parameters".$this->getId();
				$this->setParameters($_REQUEST[$name]);
			}
		}

		if (empty ($this->embedded_content_row['fallback_content_id']))
		{
			$fallback_content = self :: processNewContentUI("fallback_content_{$this->id}_new");
			if ($fallback_content != null)
			{
				$fallback_content_id = $fallback_content->GetId();
				$db->ExecSqlUpdate("UPDATE embedded_content SET fallback_content_id = '$fallback_content_id' WHERE embedded_content_id = '$this->id'", FALSE);
			}
		}
		else
		{
			$fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
			$name = "fallback_content_".$this->id."_fallback_content_erase";
			if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
			{
				$db->ExecSqlUpdate("UPDATE embedded_content SET fallback_content_id = NULL WHERE embedded_content_id = '$this->id'", FALSE);
				$fallback_content->delete($errmsg);
			}
			else
			{
				$fallback_content->processAdminUI();
			}
		}

		$this->refresh();
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI()
	{
		$html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>EmbeddedContent (".get_class($this)." instance)</div>\n";

		$embedded_content_file = null;
		$fallback_content = null;

		/* Get both objects if they exist */
		if (!empty ($this->embedded_content_row['embedded_file_id']))
			$embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
		if (!empty ($this->embedded_content_row['fallback_content_id']))
			$fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);

		/*
		 * 
		 * <object classid="clsid:A12BCD3F-GH4I-56JK-xyz"
		codebase="http://example.com/content.cab" 
		width="100" height="80">
		<param name="Movie" value="moviename.swf" />
		<embed src="moviename.swf" width="100" height="80"
		pluginspage="http://example.com/shockwave/download/" />
		<noembed>
		<img alt="Still from Movie" src="moviename.gif" 
		width="100" height="80" />
		</noembed>
		</object>
		 * 
		 * 
		 * <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="400" height="316" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
						<param name="SRC" value="/sponsors/airborne/400.mov">
		
						<param name="QTNEXT1" value="<http://stream.qtv.apple.com/qtv/endorphin/http/hydrogen3_ref.mov> T<myself>">
						<param name="AUTOPLAY" value="true">
						<param name="CONTROLLER" value="true">
						<embed src="/sponsors/airborne/400.mov" qtnext1="<http://stream.qtv.apple.com/qtv/endorphin/http/hydrogen3_ref.mov> T<myself>" width="400" height="316" align="left" autoplay="true" controller="true" pluginspage="http://www.apple.com/quicktime/download/">
						</embed>
						</object>
		 * 
		 */
		if ($embedded_content_file != null)
		{
			$url = htmlentities($embedded_content_file->getFileUrl());
			$mime_type = $embedded_content_file->getMimeType();
			$html .= "<object type='$mime_type' data='$url' {$this->getAttributes()}>\n";
			$html .= "<param name='AUTOPLAY' value='false'>\n";
			$html .= "<param name='AUTOPLAY' value='0'>\n";
			$html .= "{$this->getParameters()}\n";
			// Spit fallback content between inside the <object> tag
			if ($fallback_content != null)
				$html .= $fallback_content->getUserUI();
			$html .= "<embed autoplay=FALSE src='$url'>\n";
			$html .= "<nobembed>\n";
			$html .= "<p><a href='$url'>"._("Download")." ".$embedded_content_file->getFilename()." (".$embedded_content_file->getFileSize(File :: UNIT_KILOBYTES)." "._("KB").")</a></p>";
			$html .= "</nobembed>\n";
			$html .= "</object>\n";
		}

		$html .= "</div>\n";
		return parent :: getUserUI($html);
	}

	//TODO: Add support for attributes and parameters
	public function getAttributes()
	{
		return $this->embedded_content_row['attributes'];
	}

	public function setAttributes($attributes_str)
	{
		$attributes_str = $this->mBd->EscapeString($attributes_str);
		$this->mBd->ExecSqlUpdate("UPDATE embedded_content SET attributes ='".$attributes_str."' WHERE embedded_content_id='".$this->getId()."'", false);
		$this->refresh();
	}

	public function getParameters()
	{
		return $this->embedded_content_row['parameters'];
	}

	public function setParameters($paramters_str)
	{
		$paramters_str = $this->mBd->EscapeString($paramters_str);
		$this->mBd->ExecSqlUpdate("UPDATE embedded_content SET parameters ='".$paramters_str."' WHERE embedded_content_id='".$this->getId()."'", false);
		$this->refresh();
	}

	/** Delete this Content from the database 
	*/
	public function delete(& $errmsg)
	{
		if ($this->isPersistent() == false)
		{
			if (!empty ($this->embedded_content_row['embedded_file_id']))
			{
				$embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
				$embedded_content_file->delete($errmsg);
			}
			if (!empty ($this->embedded_content_row['fallback_content_id']))
			{
				$fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
				$fallback_content->delete($errmsg);
			}
		}
		return parent :: delete($errmsg);
	}

} // End class
?>