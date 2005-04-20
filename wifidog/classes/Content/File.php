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
/**@file File.php
 * @author Copyright (C) 2005 François Proulx, Technologies Coeus inc.
*/

require_once BASEPATH.'classes/FormSelectGenerator.php';
require_once BASEPATH.'classes/Content.php';

error_reporting(E_ALL);

/** Représente un Langstring en particulier, ne créez pas un objet langstrings si vous n'en avez pas spécifiquement besoin 
 */
class File extends Content
{
    /* File size units */
    const UNIT_BYTES = 1;
    const UNIT_KILOBYTES = 1024;
    const UNIT_MEGABYTES = 1048576;
    const UNIT_GIGABYTES = 1073741824;
    
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
        $this->setIsPersistent(false);
        $this->setIsTrivialContent(true);
		global $db;

		$content_id = $db->EscapeString($content_id);
		$sql = "SELECT files_id, filename, mime_type, url, octet_length(binary_data) AS size FROM files WHERE files_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			/*Since the parent Content exists, the necessary data in content_group had not yet been created */
			$sql = "INSERT INTO files (files_id) VALUES ('$content_id')";
			$db->ExecSqlUpdate($sql, false);

			$sql = "SELECT files_id, filename, mime_type, url, octet_length(binary_data) AS size FROM files WHERE files_id='$content_id'";
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
			}

		}
		$this->mBd = & $db;
		$this->files_row = $row;
	}

    /**
     * Set Binary data from a POST form data field
     * @param string $upload_field The form field that contains the data
     * 
     */
	function setBinaryDataFromPostVar($upload_field)
	{
		if (!empty ($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] != UPLOAD_ERR_NO_FILE)
		{
			// Getting binary data from file
			$fp = fopen($_FILES[$upload_field]['tmp_name'], "rb");
			$buffer = fread($fp, filesize($_FILES[$upload_field]['tmp_name']));
			fclose($fp);

			// Updating database
			$this->setBinaryData($buffer);
            $this->setMimeType($_FILES[$upload_field]['type']);
            $this->setFilename($_FILES[$upload_field]['name']);
            $this->refresh();
            return true;
		}
		else
		{
			return false;
		}
	}

	/** Returns the DateTime object representing the date of the contribution */
	function getBinaryData()
	{
		$this->mBd->ExecSqlUniqueRes("SELECT binary_data FROM files WHERE files_id ='".$this->getId()."';", $row, false);
		return $this->mBd->UnescapeBinaryString($row['binary_data']);
	}

	function setBinaryData($data)
	{
		$data = $this->mBd->EscapeBinaryString($data);
		$this->mBd->ExecSqlUpdate("UPDATE files SET binary_data ='".$data."' WHERE files_id='".$this->getId()."'", false);
		$this->refresh();
	}

	function getMimeType()
	{
		return $this->files_row['mime_type'];
	}

	function setMimeType($mime_type)
	{
		$mime_type = $this->mBd->EscapeString($mime_type);
		$this->mBd->ExecSqlUpdate("UPDATE files SET mime_type ='".$mime_type."' WHERE files_id='".$this->getId()."'", false);
		$this->refresh();
	}

	function getFilename()
	{
		return $this->files_row['filename'];
	}

	function setFilename($file_name)
	{
		$file_name = $this->mBd->EscapeString($file_name);
		$this->mBd->ExecSqlUpdate("UPDATE files SET filename ='".$file_name."' WHERE files_id='".$this->getId()."'", false);
		$this->refresh();
	}
    
    function getFileSize($unit = self::UNIT_BYTES)
    {
        switch($unit)
        {
            case self::UNIT_KILOBYTES;
            case self::UNIT_MEGABYTES:
            case self::UNIT_GIGABYTES:
            case self::UNIT_BYTES:
                return round($this->files_row['size'] / $unit, 2);
            default:
                return $this->files_row['size'];
                break;
        }
    }
    
    function getFileUrl()
    {
        //TODO: build local url + file generator
        if(!isLocalFile())
            return $this->files_row['url'];
        else
            return "http://";
    }
    
    function setURL($url)
    {
        $url = $this->mBd->EscapeString($url);
        $this->mBd->execSqlUpdate("UPDATE files SET url = '$url' WHERE files_id='".$this->getId()."'", false);
        $this->refresh();
    }
    
    function isLocalFile()
    {
        return !empty($this->files_row['url']);
    }

	/**Affiche l'interface d'administration de l'objet */
	function getAdminUI()
	{
		$html = '';
		$html .= "<div class='admin_class'>File (".get_class($this)." instance)</div>\n";
        
		$html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_by_upload".$this->getId()."' value='true' ".(!$this->isLocalFile()?"CHECKED":"").">";
        $html .= _("Upload a new file (This will replace any existing file)")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />';
        $html .= '<input name="file_file_upload'.$this->getId().'" type="file" />';
		$html .= "</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_by_url".$this->getId()."' value='true' ".($this->isLocalFile()?"CHECKED":"").">";
        $html .= _("Remote file via URL")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= "<input name='file_url".$this->getId()."' type='text' size='50'/>";
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Filename")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="text" name="file_file_name'.$this->getId().'" value="'.$this->getFilename().'" />';
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("MIME type")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="text" name="file_mime_type'.$this->getId().'" value="'.$this->getMimeType().'" />';
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        if($this->isLocalFile())
        {
            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("File size")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= $this->getFileSize(self::UNIT_KILOBYTES)." "._("KB");
            $html .= "</div>\n";
            $html .= "</div>\n";
        }
        else
        {
            //TODO: implement user defined size
        }
        
		return parent :: getAdminUI($html);
	}

	function processAdminUI()
	{
		parent :: processAdminUI();
        
        // If no file was uploaded, update filename and mime type
        if(!empty($_REQUEST["file_by_upload".$this->getId()]))
        {
            $this->setBinaryDataFromPostVar("file_file_upload".$this->getId());
        }
        else
            if(!empty($_REQUEST["file_url".$this->getId()]))
            {
                $this->setURL($_REQUEST["file_url".$this->getId()]);
            }
        $this->setMimeType($_REQUEST["file_mime_type".$this->getId()]);
        $this->setFilename($_REQUEST["file_file_name".$this->getId()]);
	}

	/**Affiche l'interface usager de l'objet
	        */

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI()
	{
		$html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>Langstring (".get_class($this)." instance)</div>\n";
		$html .= "<a href='".$this->getFileUrl()."'>"._("Download this file")." (".$this->getFileSize(UNIT_KILOBYTES)." "._("KB").")</a>";
		$html .= "</div>\n";
		return parent :: getUserUI($html);
	}

	/** Delete this Content from the database */
	public function delete(& $errmsg)
	{
		if ($this->isPersistent() == false)
		{
			$this->mBd->ExecSqlUpdate("DELETE FROM files WHERE files_id = '".$this->getId()."'", false);
		}
		else
			$errmsg = _("Could not delete this file, since it is persistent");
		return parent :: delete($errmsg);
	}

} /* end class File */
?>