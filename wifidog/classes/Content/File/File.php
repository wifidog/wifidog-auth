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
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Binary or ASCII file
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @copyright  2005-2006 Max Horvath, maxspot GmbH
 */
class File extends Content
{

    /**
     * File size unit: Byte

     */
    const UNIT_BYTES = 1;

    /**
     * File size unit: KB

     */
    const UNIT_KILOBYTES = 1024;

    /**
     * File size unit: MB

     */
    const UNIT_MEGABYTES = 1048576;

    /**
     * File size unit: GB

     */
    const UNIT_GIGABYTES = 1073741824;

        /** Can the user edit the filename */
		private $configEnableEditFilename = true;
		
		        /** Can the user edit the mime type */
		private $configEnableEditMimeType = true;
    /**
     * Constructor
     *
     * @param string $content_id Content id
     */
    protected function __construct($content_id)
    {
        
        $db = AbstractDb::getObject();

        // Init values
        $row = null;
        parent :: __construct($content_id);

        $content_id = $db->escapeString($content_id);
        $sql = "SELECT * FROM content_file WHERE files_id='$content_id'";
        $db->execSqlUniqueRes($sql, $row, false);

        if ($row == null) {
            /*
             * Since the parent Content exists, the necessary data in
             * content_group had not yet been created
             */
            $sql = "INSERT INTO content_file (files_id) VALUES ('$content_id')";
            $db->execSqlUpdate($sql, false);

            $sql = "SELECT * FROM content_file WHERE files_id='$content_id'";
            $db->execSqlUniqueRes($sql, $row, false);

            if ($row == null) {
                throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
            }
        }

        $this->mBd = &$db;
        $this->files_row = $row;
    }

    /**
     * Set Binary data from a POST form data field
     *
     * @param string $upload_field The form field that contains the data
     *
     * @return bool True if successful

     */
    private function setBinaryDataFromPostVar($upload_field)
    {
        // Init values
        $_retval = false;

        if (!empty ($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] == UPLOAD_ERR_OK) {
            // Unlink BLOB if any exists
            $blob_oid = $this->getBinaryDataOid();

            if ($blob_oid) {
                $this->mBd->unlinkLargeObject($blob_oid);
            }

            // Updating database
            // Create a new BLOB
            $new_oid = $this->mBd->importLargeObject($_FILES[$upload_field]['tmp_name']);
            // Switch to new OID and touch file
            $this->setBinaryDataOid($new_oid);
            $this->setLocalFileSize($_FILES[$upload_field]['size']);
            $this->setMimeType($_FILES[$upload_field]['type']);
            $this->setFilename($_FILES[$upload_field]['name']);

            $_retval = true;
        } else {
            switch ($_FILES[$upload_field]['error'])
            {
            case 'UPLOAD_ERR_INI_SIZE':
                echo _("File size exceeds limit specified in PHP.ini");
                break;

            case 'UPLOAD_ERR_FORM_SIZE':
                echo _("File size exceeds limit specified HTML form");
                break;

            case 'UPLOAD_ERR_PARTIAL':
                echo _("File upload was interrupted");
                break;

            case 'UPLOAD_ERR_NO_TMP_DIR':
                echo _("Missing temp folder");
                break;

            }
        }

        return $_retval;
    }

    /**
     * Returns the binary data from the database
     *
     * @return string Binary data from the database

     */
    private function getBinaryDataOid()
    {
        return $this->mBd->unescapeBinaryString($this->files_row['data_blob']);
    }

    /**
     * Saves the binary data to the database
     *
     * @param string $oid Binary data
     *
     * @return void

     */
    private function setBinaryDataOid($oid)
    {
        if (is_null($oid)) {
            $oid_sql = "NULL";
        }
        else
        {
        	$oid_sql = $this->mBd->escapeString($oid);
        }
    	if($this->files_row['data_blob']!=$oid){
        $this->mBd->execSqlUpdate("UPDATE content_file SET data_blob = $oid WHERE files_id='".$this->getId()."'", false);
        // Touch and refresh this object
        $this->touch();
    	}
    }

    /**
     * Is the MimeType editable
     * @param $enabled Default is true
     */
    protected function configEnableEditMimeType($enabled=true)
    {
        return $this->configEnableEditMimeType=$enabled;
    }
    
    /**
     * Returns the MIME type of the file
     *
     * @return string MIME type of file
     */
    public function getMimeType()
    {
        return $this->files_row['mime_type'];
    }

    /**
     * Saves the MIME type of the file
     *
     * @param string $mime_type
     *
     * @return void
     */
    private function setMimeType($mime_type)
    {
        $mime_type = $this->mBd->escapeString($mime_type);
        $this->mBd->execSqlUpdate("UPDATE content_file SET mime_type ='".$mime_type."' WHERE files_id='".$this->getId()."'", false);
        // Touch and refresh this object
        $this->touch();
    }

    /**
     * Is the filename editable
     * @param $enabled Default is true
     */
    protected function configEnableEditFilename($enabled=true)
    {
        return $this->configEnableEditFilename=$enabled;
    }
    
    /**
     * Returns the filename of the file
     *
     * @return string Filename of file

     */
    protected function getFilename()
    {
        return $this->files_row['filename'];
    }

    /**
     * Stores the filename of the file
     *
     * @param string $file_name Filename of the file
     *
     * @return void
     */
    private function setFilename($file_name)
    {
        $file_name = $this->mBd->escapeString($file_name);
        $this->mBd->execSqlUpdate("UPDATE content_file SET filename ='".$file_name."' WHERE files_id='".$this->getId()."'", false);
        // Touch and refresh this object
        $this->touch();
    }

    /**
     * Returns the size of the file
     *
     * @param string $unit Name of constant of which kind of filesize unit
     *                     to use
     *                     Possibilities:
     *                       + self::UNIT_BYTES
     *                       + self::UNIT_KILOBYTES
     *                       + self::MEGABYTES
     *                       + self::GIGABYTES
     *
     * @return float Size of file
     */
    protected function getFileSize($unit = self::UNIT_BYTES)
    {
        if ($this->isLocalFile()) {
            $size = $this->files_row['local_binary_size'];
        } else {
            $size = $this->files_row['remote_size'];
        }

        switch ($unit) {
        case self::UNIT_KILOBYTES:
        case self::UNIT_MEGABYTES:
        case self::UNIT_GIGABYTES:
        case self::UNIT_BYTES:
            $size = round($size / $unit, 2);
            break;

        }

        return $size;
    }

    /**
     * Sets the size of a local file
     *
     * @param int    $size Size of file
     * @param string $unit Name of constant of which kind of filesize unit
     *                     to use
     *                     Possibilities:
     *                       + self::UNIT_BYTES
     *                       + self::UNIT_KILOBYTES
     *                       + self::MEGABYTES
     *                       + self::GIGABYTES
     *
     * @return void
     */
    private function setLocalFileSize($size, $unit = self::UNIT_BYTES)
    {
        if (is_numeric($size)) {
            $octet_size = $size * $unit;

            $this->mBd->execSqlUpdate("UPDATE content_file SET local_binary_size = $octet_size WHERE files_id='" . $this->getId() . "'", false);
            $this->refresh();
        }
    }

    /**
     * Sets the size of a remote file
     *
     * @param int    $size Size of file
     * @param string $unit Name of constant of which kind of filesize unit
     *                     to use
     *                     Possibilities:
     *                       + self::UNIT_BYTES
     *                       + self::UNIT_KILOBYTES
     *                       + self::MEGABYTES
     *                       + self::GIGABYTES
     *
     * @return void

     */
    private function setRemoteFileSize($size, $unit = self::UNIT_KILOBYTES)
    {
        if (is_numeric($size)) {
            $octet_size = $size * $unit;

            $this->mBd->execSqlUpdate("UPDATE content_file SET remote_size = $octet_size WHERE files_id='".$this->getId()."'", false);
            $this->refresh();
        }
    }

    /**
     * Get URL of file
     *
     * @return string URL of file
     */
    public function getFileUrl()
    {
        // Init values
        $_retval = null;

        if (!$this->isLocalFile()) {
            $_retval = $this->files_row['url'];
        } else {
            $_retval = BASE_SSL_PATH . "file_download.php?file_id=" . $this->getId();
        }

        return $_retval;
    }

    /**
     * Sets URL of a file
     *
     * @param string $url
     *
     * @return void

     */
    private function setURL($url)
    {
        if ($url == null) {
            $url_sql = "NULL";
        } else {
            $url_sql = "'".$this->mBd->escapeString($url)."'";
        }
            	if($this->files_row['url']!=$url){
        
        $this->mBd->execSqlUpdate("UPDATE content_file SET url = $url_sql WHERE files_id='".$this->getId()."'", false);
        $this->refresh();
            	}
    }

    /**
     * Returns if file is a local file
     *
     * @return bool True if file is local
     */
    protected function isLocalFile()
    {
        return is_null($this->files_row['url']);
    }

    /**
     * Shows the administration interface for RssAggregator.
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     */
    public function getAdminUI($subclass_admin_interface = null, $title=null)
    {
        // Init values
        $html = '';

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='by_upload' ". ($this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Upload a new file (Uploading a new one will replace any existing file)")." : </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="1073741824" />';
        $html .= '<input name="file_file_upload'.$this->getId().'" type="file" />';
        $html .= "</div>\n";
        $html .= "</li>\n";

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>";
        $html .= "<input type='radio' name='file_mode".$this->getId()."' value='remote' ". (!$this->isLocalFile() ? "CHECKED" : "").">";
        $html .= _("Remote file via URL")." : </div>\n";
        $html .= "<div class='admin_element_data'>\n";

        if ($this->isLocalFile()) {
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50'/>";
        } else {
            $html .= "<input name='file_url".$this->getId()."' type='text' size='50' value='".$this->getFileUrl()."'/>";
        }

        $html .= "</div>\n";
        $html .= "</li>\n";

        if (!$this->isLocalFile()) {
            $html .= "<div class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("File URL")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= $this->getFileUrl();
            $html .= "</div>\n";
            $html .= "</li>\n";
        }
        
if($this->configEnableEditFilename) {
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>"._("Filename to display")." : </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $html .= '<input type="text" name="file_file_name'.$this->getId().'" value="'.$this->getFilename().'" />';
        $html .= "</div>\n";
        $html .= "</li>\n";
}

        if ($this->isLocalFile()) {
        	if($this->configEnableEditMimeType){
            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("MIME type")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= '<input type="text" name="file_mime_type'.$this->getId().'" value="'.$this->getMimeType().'" />';
            $html .= "</div>\n";
            $html .= "</li>\n";
        	}

            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("Locally stored file size")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= $this->getFileSize(self :: UNIT_KILOBYTES)." "._("KB");
        } else {
            $html .= "<div class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("Remote file size (Automatically converted from KB to Bytes)")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            // The hidden field contains old value to determine if we have to update ( this prevents unwanted successive floating point evaluation )
            $html .= '<input type="hidden" name="file_old_remote_size'.$this->getId().'" value="'.$this->getFileSize().'" />';
            $html .= '<input type="text" name="file_remote_size'.$this->getId().'" value="'.$this->getFileSize().'" />';

        }
                $html .= " <a href='".$this->getFileUrl()."'>"._("Download")."</a>\n";
        $html .= " "._("Last update")." : \n";
                    $html .= $this->getLastUpdateTimestamp();
                $html .= "</div>\n";
            $html .= "</li>\n";

        $html .= $subclass_admin_interface;

        return parent::getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for RssAggregator
     *
     * @return void
     */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            parent :: processAdminUI();

            // If no file was uploaded, update filename and mime type
            if (!empty ($_REQUEST["file_mode".$this->getId()])) {
                if ($this->configEnableEditFilename && !empty($_REQUEST["file_file_name".$this->getId()])) {
                    $this->setFilename($_REQUEST["file_file_name".$this->getId()]);
                }

                $file_mode = $_REQUEST["file_mode".$this->getId()];

                if ($file_mode == "by_upload") {
                    if($this->configEnableEditMimeType &&isset($_REQUEST["file_mime_type".$this->getId()])) {
                        $this->setMimeType($_REQUEST["file_mime_type".$this->getId()]);
                    }

                    $this->setBinaryDataFromPostVar("file_file_upload".$this->getId());
                    $this->setURL(null);

                    // Reset the remote file size ( not used )
                    $this->setRemoteFileSize(0);
                } else {
                    if ($file_mode == "remote") {
                        $this->setURL($_REQUEST["file_url".$this->getId()]);
                        $this->setBinaryDataOid(null);

                        // When switching from local to remote, this field does not exist yet
                        if (isset($_REQUEST["file_old_remote_size".$this->getId()])) {
                            if ($_REQUEST["file_remote_size".$this->getId()] != $_REQUEST["file_old_remote_size".$this->getId()]) {
                                $this->setRemoteFileSize($_REQUEST["file_remote_size".$this->getId()]);
                            }
                        } else {
                            $this->setRemoteFileSize(0);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * @return string The HTML fragment for this interface
     */
    public function getUserUI()
    {
        // Init values
        $html = '';

        if($this->getFileSize() > 0) {
            $append_size = " (".$this->getFileSize(self :: UNIT_KILOBYTES)." "._("KB").")";
        } else {
            $append_size = "";
        }

        $html .= "<div class='download_button'><a href='".htmlentities($this->getFileUrl())."'>"._("Download")." ".$this->getFilename()."$append_size</a></div>";
        $html = $this->replaceHyperLinks($html);
        $this->setUserUIMainDisplayContent($html);
        return parent::getUserUI();
    }

    /**
     * Reloads the object from the database. Should normally be called after
     * a set operation. This function is private because calling it from a
     * subclass will call the constructor from the wrong scope.
     *
     * @return void
     */
    private function refresh()
    {
        $this->__construct($this->id);
    }

    /**
     * Deletes a File object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     * @internal Persistent content will not be deleted
     */
    public function delete(&$errmsg)
    {
        if ($this->isPersistent() == false) {
            // Unlink BLOB if any exists
            $blob_oid = $this->getBinaryDataOid();

            if($blob_oid) {
                $errmsg = "Deleting BLOB OID : $blob_oid";

                if($this->mBd->UnlinkLargeObject($blob_oid) == false) {
                    $errmsg = _("Unable to successfully unlink BLOB OID : $blob_oid !");
                    return false;
                }
            }

            $this->mBd->execSqlUpdate("DELETE FROM content_file WHERE files_id = '".$this->getId()."'", false);
        } else {
            $errmsg = _("Could not delete this file, since it is persistent");
        }

        return parent::delete($errmsg);
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


