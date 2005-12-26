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
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx <francois.proulx@gmail.com> - Technologies
 * Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

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
        global $db;

        $content_id = $db->EscapeString($content_id);
        $sql = "SELECT * FROM files WHERE files_id='$content_id'";
        $db->ExecSqlUniqueRes($sql, $row, false);
        if ($row == null)
        {
            /*Since the parent Content exists, the necessary data in content_group had not yet been created */
            $sql = "INSERT INTO files (files_id) VALUES ('$content_id')";
            $db->ExecSqlUpdate($sql, false);

            $sql = "SELECT * FROM files WHERE files_id='$content_id'";
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
        if (!empty ($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] == UPLOAD_ERR_OK)
        {
            // Unlink BLOB if any exists
            $blob_oid = $this->getBinaryDataOid();
            if ($blob_oid)
                $this->mBd->UnlinkLargeObject($blob_oid);

            // Updating database
            // Create a new BLOB
            $new_oid = $this->mBd->ImportLargeObject($_FILES[$upload_field]['tmp_name']);
            $this->setBinaryDataOid($new_oid);
            $this->setLocalFileSize($_FILES[$upload_field]['size']);
            $this->setMimeType($_FILES[$upload_field]['type']);
            $this->setFilename($_FILES[$upload_field]['name']);
            $this->refresh();
            return true;
        }
        else
        {
            switch ($_FILES[$upload_field]['error'])
            {
                case 'UPLOAD_ERR_INI_SIZE' :
                    echo _("File size exceeds limit specified in PHP.ini");
                    break;
                case 'UPLOAD_ERR_FORM_SIZE' :
                    echo _("File size exceeds limit specified HTML form");
                    break;
                case 'UPLOAD_ERR_PARTIAL' :
                    echo _("File upload was interrupted");
                    break;
                    /*
                    case UPLOAD_ERR_NO_FILE:
                        echo _("No file was uploaded");
                        break;*/
                case 'UPLOAD_ERR_NO_TMP_DIR' :
                    echo _("Missing temp folder");
                    break;
            }
            return false;
        }
    }

    /** Returns the DateTime object representing the date of the contribution */
    function getBinaryDataOid()
    {
        return $this->mBd->UnescapeBinaryString($this->files_row['data_blob']);
    }

    function setBinaryDataOid($oid)
    {
        if(is_null($oid))
            $oid = "NULL";
        $this->mBd->ExecSqlUpdate("UPDATE files SET data_blob = $oid WHERE files_id='".$this->getId()."'", false);
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

    function getFileSize($unit = self :: UNIT_BYTES)
    {
        if ($this->isLocalFile())
            $size = $this->files_row['local_binary_size'];
        else
            $size = $this->files_row['remote_size'];

        switch ($unit)
        {
            case self :: UNIT_KILOBYTES;
            case self :: UNIT_MEGABYTES :
            case self :: UNIT_GIGABYTES :
            case self :: UNIT_BYTES :
                return round($size / $unit, 2);
            default :
                return $size;
        }
    }

    function setLocalFileSize($size, $unit = self :: UNIT_BYTES)
    {
        if (is_numeric($size))
        {
            $octet_size = $size * $unit;
            $this->mBd->execSqlUpdate("UPDATE files SET local_binary_size = $octet_size WHERE files_id='".$this->getId()."'", false);
            $this->refresh();
        }
    }

    function setRemoteFileSize($size, $unit = self :: UNIT_KILOBYTES)
    {
        if (is_numeric($size))
        {
            $octet_size = $size * $unit;
            $this->mBd->execSqlUpdate("UPDATE files SET remote_size = $octet_size WHERE files_id='".$this->getId()."'", false);
            $this->refresh();
        }
    }

    function getFileUrl()
    {
        if (!$this->isLocalFile())
            return $this->files_row['url'];
        else
            return BASE_SSL_PATH."file_download.php?file_id=".$this->getId();
    }

    function setURL($url)
    {
        if ($url == null)
            $url = "NULL";
        else
            $url = "'".$this->mBd->EscapeString($url)."'";
        $this->mBd->execSqlUpdate("UPDATE files SET url = $url WHERE files_id='".$this->getId()."'", false);
        $this->refresh();
    }

    function isLocalFile()
    {
        return is_null($this->files_row['url']);
    }

    /**Affiche l'interface d'administration de l'objet */
    function getAdminUI($subclass_admin_interface = null)
    {
        $html = '';
        $html .= "<div class='admin_class'>File (".get_class($this)." instance)</div>\n";

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='by_upload' ". ($this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Upload a new file (Uploading a new one will replace any existing file)")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />';
        $html .= '<input name="file_file_upload'.$this->getId().'" type="file" />';
        $html .= "</div>\n";
        $html .= "</div>\n";

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='remote' ". (!$this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Remote file via URL")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        if ($this->isLocalFile())
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50'/>";
        else
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50' value='".$this->getFileUrl()."'/>";
        $html .= "</div>\n";
        $html .= "</div>\n";

        if (!$this->isLocalFile())
        {
            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("File URL")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= $this->getFileUrl();
            $html .= "</div>\n";
            $html .= "</div>\n";
        }

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_title'>"._("Filename to display")." : </div>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= '<input type="text" name="file_file_name'.$this->getId().'" value="'.$this->getFilename().'" />';
        $html .= "</div>\n";
        $html .= "</div>\n";

        if ($this->isLocalFile())
        {
            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("MIME type")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= '<input type="text" name="file_mime_type'.$this->getId().'" value="'.$this->getMimeType().'" />';
            $html .= "</div>\n";
            $html .= "</div>\n";

            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Locally stored file size")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            $html .= $this->getFileSize(self :: UNIT_KILOBYTES)." "._("KB");
            $html .= "</div>\n";
            $html .= "</div>\n";
        }
        else
        {
            $html .= "<div class='admin_section_container'>\n";
            $html .= "<div class='admin_section_title'>"._("Remote file size (Automatically converted from KB to Bytes)")." : </div>\n";
            $html .= "<div class='admin_section_data'>\n";
            // The hidden field contains old value to determine if we have to update ( this prevents unwanted successive floating point evaluation )
            $html .= '<input type="hidden" name="file_old_remote_size'.$this->getId().'" value="'.$this->getFileSize().'" />';
            $html .= '<input type="text" name="file_remote_size'.$this->getId().'" value="'.$this->getFileSize().'" />';
            $html .= "</div>\n";
            $html .= "</div>\n";
        }

        $html .= "<div class='admin_section_container'>\n";
        $html .= "<div class='admin_section_data'>\n";
        $html .= "<a href='".$this->getFileUrl()."'>"._("Download")." ".$this->getFilename()." (".$this->getFileSize(self :: UNIT_KILOBYTES)." "._("KB").")</a>";
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

            // If no file was uploaded, update filename and mime type
            if (!empty ($_REQUEST["file_mode".$this->getId()]))
            {
        if (!empty($_REQUEST["file_file_name".$this->getId()])) {
                  $this->setFilename($_REQUEST["file_file_name".$this->getId()]);
        }

                $file_mode = $_REQUEST["file_mode".$this->getId()];
                if ($file_mode == "by_upload")
                {
                    if(isset($_REQUEST["file_mime_type".$this->getId()]))
                        $this->setMimeType($_REQUEST["file_mime_type".$this->getId()]);
                    $this->setBinaryDataFromPostVar("file_file_upload".$this->getId());
                    $this->setURL(null);
                    // Reset the remote file size ( not used )
                    $this->setRemoteFileSize(0);
                }
                else
                {
                    if ($file_mode == "remote")
                    {
                        $this->setURL($_REQUEST["file_url".$this->getId()]);
                        $this->setBinaryDataOid(null);
                        // When switching from local to remote, this field does not exist yet
                        if (isset($_REQUEST["file_old_remote_size".$this->getId()]))
                        {
                            if ($_REQUEST["file_remote_size".$this->getId()] != $_REQUEST["file_old_remote_size".$this->getId()])
                                $this->setRemoteFileSize($_REQUEST["file_remote_size".$this->getId()]);
                        }
                        else
                            $this->setRemoteFileSize(0);
                    }
                }
            }
        }
    }

    /** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
     * @param $subclass_admin_interface Html content of the interface element of a children
     * @return The HTML fragment for this interface */
    public function getUserUI()
    {
        $html = '';
        $html .= "<div class='user_ui_container'>\n";
        $html .= "<div class='user_ui_object_class'>File (".get_class($this)." instance)</div>\n";
        if($this->getFileSize() > 0)
            $append_size = " (".$this->getFileSize(self :: UNIT_KILOBYTES)." "._("KB").")";
        else
            $append_size = "";
        $html .= "<div class='download_button'><a href='".htmlentities($this->getFileUrl())."'>"._("Download")." ".$this->getFilename()."$append_size</a></div>";
        $html .= "</div>\n";
        return parent :: getUserUI($html);
    }

    /** Delete this Content from the database */
    public function delete(& $errmsg)
    {
        if ($this->isPersistent() == false)
        {
            // Unlink BLOB if any exists
            $blob_oid = $this->getBinaryDataOid();
            if($blob_oid)
            {
                $errmsg = "Deleting BLOB OID : $blob_oid";
                if($this->mBd->UnlinkLargeObject($blob_oid) == false)
                {
                    $errmsg = _("Unable to successfully unlink BLOB OID : $blob_oid !");
                    return false;
                }
            }
            $this->mBd->ExecSqlUpdate("DELETE FROM files WHERE files_id = '".$this->getId()."'", false);
        }
        else
            $errmsg = _("Could not delete this file, since it is persistent");
        return parent :: delete($errmsg);
    }
    /** Reloads the object from the database.  Should normally be called after a set operation.
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope */
    private function refresh()
    {
        $this->__construct($this->id);
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
