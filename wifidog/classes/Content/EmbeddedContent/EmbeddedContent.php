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
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * A generic embedded content container
 *
 * This object supports backward compatiblity fallback
 *
 * Inspired by W3C WCGAG 2.0 recommendations
 * http://www.w3.org/TR/2004/WD-WCAG20-HTML-TECHS-20041119/#embed
 *
 * And Macromedia recommendations for backward compatibility
 * http://www.macromedia.com/cfusion/knowledgebase/index.cfm?id=tn_12701
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 */
class EmbeddedContent extends Content
{
    /**
     * Constructor
     *
     * @param string $content_id Content Id
     *
     * @return void
     *
     * @access protected
     */
    protected function __construct($content_id)
    {
        // Define globals
        global $db;

        // Init values
        $row = null;

        parent::__construct($content_id);

        $content_id = $db->escapeString($content_id);

        $sql = "SELECT * FROM content_embedded_content WHERE embedded_content_id='$content_id'";
        $db->execSqlUniqueRes($sql, $row, false);
        if ($row == null)
        {
            /*Since the parent Content exists, the necessary data in content_group had not yet been created */
            $sql = "INSERT INTO content_embedded_content (embedded_content_id) VALUES ('$content_id')";
            $db->execSqlUpdate($sql, false);
            $sql = "SELECT * FROM content_embedded_content WHERE embedded_content_id='$content_id'";
            $db->execSqlUniqueRes($sql, $row, false);
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

    /**
     * Returns the attributes of embedded content
     *
     * @return string Attributes of the embedded content
     *
     * @access private
     */
    private function getAttributes()
    {
        return $this->embedded_content_row['attributes'];
    }

    /**
     * Sets the attributes of embedded content
     *
     * @param string $attributes_str Attributes of the embedded content
     *
     * @return void
     *
     * @access private
     */
    private function setAttributes($attributes_str)
    {
        $attributes_str = $this->mBd->escapeString($attributes_str);
        $this->mBd->execSqlUpdate("UPDATE content_embedded_content SET attributes ='".$attributes_str."' WHERE embedded_content_id='".$this->getId()."'", false);
        $this->refresh();
    }

    /**
     * Returns parameters of embedded content
     *
     * @return string Parameters of the embedded content
     *
     * @access private
     */
    private function getParameters()
    {
        return $this->embedded_content_row['parameters'];
    }

    /**
     * Sets the parameters of embedded content
     *
     * @param string $paramters_str Parameters of the embedded content
     *
     * @return void
     *
     * @access private
     */
    private function setParameters($paramters_str)
    {
        $paramters_str = $this->mBd->escapeString($paramters_str);
        $this->mBd->execSqlUpdate("UPDATE content_embedded_content SET parameters ='".$paramters_str."' WHERE embedded_content_id='".$this->getId()."'", false);
        $this->refresh();
    }

    /**
     * Shows the administration interface for embedded content.
     *
     * @param string $subclass_admin_interface HTML code to be added after the
     *                                         administration interface
     *
     * @return string HTML code for the administration interface
     *
     * @access public
     */
    public function getAdminUI($subclass_admin_interface = null, $title=null)
    {
        $html = '';
 		$html .= "<ul class='admin_element_list'>\n";
        /* Embedded content Content */
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>"._("Embedded content")." : <br></div>\n";
        $html .= "<div class='admin_element_data'>\n";

        if (empty ($this->embedded_content_row['embedded_file_id'])) {
            // Mandate File
            $html .= self :: getNewContentUI("embedded_file_{$this->id}_new", "File");
            $html .= "</div>\n";
        } else {
            $embedded_content_file = self::getObject($this->embedded_content_row['embedded_file_id']);

            $html .= $embedded_content_file->getAdminUI();

            $html .= "</div>\n";
            $html .= "<div class='admin_element_tools'>\n";
            $name = "embeddedcontent_".$this->id."_embedded_file_erase";

            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("Attributes")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= "<br><i>"._("It is recommended to specify at least <b>width='x' height='y'</b> as attributes")."</i><br>\n";
            $html .= '<textarea name="embedded_content_attributes'.$this->getId().'" cols="60" rows="3">'.htmlspecialchars($this->getAttributes(), ENT_QUOTES, 'UTF-8').'</textarea>';
            $html .= "</div>\n";
            $html .= "</li>\n";

            $html .= "<li class='admin_element_item_container'>\n";
            $html .= "<div class='admin_element_label'>"._("Parameters")." : </div>\n";
            $html .= "<div class='admin_element_data'>\n";
            $html .= '<br><textarea name="embedded_content_parameters'.$this->getId().'" cols="60" rows="3">'.htmlspecialchars($this->getParameters(), ENT_QUOTES, 'UTF-8').'</textarea>';
            $html .= "</div>\n";
            $html .= "</li>\n";

            $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
            $html .= "</div>\n";
        }

        $html .= "</li>\n";

        /* Fallback content */
        $html .= "<div class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>"._("Fallback content (Can be another embedded content to create a fallback hierarchy)")." : <br></div>\n";
        $html .= "<div class='admin_element_data'>\n";

        if (empty ($this->embedded_content_row['fallback_content_id'])) {
            $html .= self::getNewContentUI("fallback_content_{$this->id}_new");
            $html .= "</div>\n";
        } else {
            $fallback_content = self::getObject($this->embedded_content_row['fallback_content_id']);

            $html .= $fallback_content->getAdminUI();

            $html .= "</div>\n";
            $html .= "<div class='admin_element_tools'>\n";
            $name = "fallback_content_".$this->id."_fallback_content_erase";
            $html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
            $html .= "</div>\n";
        }
        $html .= "</li>\n";
        $html .= "</ul>\n";
        $html .= $subclass_admin_interface;

        return parent::getAdminUI($html, $title);
    }

    /**
     * Processes the input of the administration interface for embedded content
     *
     * @return void
     *
     * @access public
     */
    public function processAdminUI()
    {
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
            // Define globals
            global $db;

            // Init values
            $errmsg = null;

            parent::processAdminUI();

            if (empty ($this->embedded_content_row['embedded_file_id'])) {
                $embedded_content_file = self :: processNewContentUI("embedded_file_{$this->id}_new");

                if ($embedded_content_file != null) {
                    $embedded_content_file_id = $embedded_content_file->GetId();
                    $db->execSqlUpdate("UPDATE content_embedded_content SET embedded_file_id = '$embedded_content_file_id' WHERE embedded_content_id = '$this->id'", FALSE);
                } else {
                    echo _("You MUST choose a File object or any of its siblings.");
                    $embedded_content_file->delete($errmsg);
                }
            } else {
                $embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
                $name = "embeddedcontent_".$this->id."_embedded_file_erase";

                if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                    $db->execSqlUpdate("UPDATE content_embedded_content SET embedded_file_id = NULL WHERE embedded_content_id = '$this->id'", FALSE);
                    $embedded_content_file->delete($errmsg);
                } else {
                    $embedded_content_file->processAdminUI();

                    $name = "embedded_content_attributes".$this->getId();
                    $this->setAttributes($_REQUEST[$name]);

                    $name = "embedded_content_parameters".$this->getId();
                    $this->setParameters($_REQUEST[$name]);
                }
            }

            if (empty ($this->embedded_content_row['fallback_content_id'])) {
                $fallback_content = self :: processNewContentUI("fallback_content_{$this->id}_new");

                if ($fallback_content != null) {
                    $fallback_content_id = $fallback_content->GetId();
                    $db->execSqlUpdate("UPDATE content_embedded_content SET fallback_content_id = '$fallback_content_id' WHERE embedded_content_id = '$this->id'", FALSE);
                }
            } else {
                $fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
                $name = "fallback_content_".$this->id."_fallback_content_erase";

                if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
                    $db->execSqlUpdate("UPDATE content_embedded_content SET fallback_content_id = NULL WHERE embedded_content_id = '$this->id'", FALSE);
                    $fallback_content->delete($errmsg);
                } else {
                    $fallback_content->processAdminUI();
                }
            }

            $this->refresh();
        }
    }

    /**
     * Retreives the user interface of this object.
     *
     * @return string The HTML fragment for this interface
     *
     * @access public
     */
    public function getUserUI()
    {
        // Init values
        $html = '';
        $embedded_content_file = null;
        $fallback_content = null;

        $html .= "<div class='user_ui_container ".get_class($this)."'>\n";

        /* Get both objects if they exist */
        if (!empty ($this->embedded_content_row['embedded_file_id'])) {
            $embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
        }

        if (!empty ($this->embedded_content_row['fallback_content_id'])) {
            $fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
        }

        /**
         * @internal Example:
         *
         * <samp>
         *     <object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="400" height="316" codebase="http://www.apple.com/qtactivex/qtplugin.cab">
         *         <param name="SRC" value="/sponsors/airborne/400.mov">
         *         <param name="QTNEXT1" value="<http://stream.qtv.apple.com/qtv/endorphin/http/hydrogen3_ref.mov> T<myself>">
         *         <param name="AUTOPLAY" value="true">
         *         <param name="CONTROLLER" value="true">
         *         <embed src="/sponsors/airborne/400.mov" qtnext1="<http://stream.qtv.apple.com/qtv/endorphin/http/hydrogen3_ref.mov> T<myself>" width="400" height="316" align="left" autoplay="true" controller="true" pluginspage="http://www.apple.com/quicktime/download/">
         *         </embed>
         *     </object>
         * </samp>
         */
        if ($embedded_content_file != null) {
            $url = htmlentities($embedded_content_file->getFileUrl());
            $mime_type = $embedded_content_file->getMimeType();
            $html .= "<object type='$mime_type' data='$url' {$this->getAttributes()}>\n";
            $html .= "<param name='AUTOPLAY' value='false'>\n";
            $html .= "<param name='AUTOPLAY' value='0'>\n";
            $html .= "{$this->getParameters()}\n";

            // Spit fallback content between inside the <object> tag
            if ($fallback_content != null) {
                $html .= $fallback_content->getUserUI();
            }

            $html .= "<embed autoplay=FALSE src='$url'>\n";
            $html .= "<nobembed>\n";
            $html .= "<p><a href='$url'>"._("Download")." ".$embedded_content_file->getFilename()." (".$embedded_content_file->getFileSize(File :: UNIT_KILOBYTES)." "._("KB").")</a></p>";
            $html .= "</nobembed>\n";
            $html .= "</object>\n";
        }

        $html .= "</div>\n";

        return parent::getUserUI($html);
    }

    /**
     * Reloads the object from the database. Should normally be called after
     * a set operation. This function is private because calling it from a
     * subclass will call the constructor from the wrong scope.
     *
     * @return void
     *
     * @access private
     */
    private function refresh()
    {
        $this->__construct($this->id);
    }

    /**
     * Deletes a embedded content object
     *
     * @param string $errmsg Reference to error message
     *
     * @return bool True if deletion was successful
     *
     * @access public
     * @internal Persistent content will not be deleted
     */
    public function delete(&$errmsg)
    {
        if ($this->isPersistent() == false) {
            if (!empty ($this->embedded_content_row['embedded_file_id'])) {
                $embedded_content_file = self :: getObject($this->embedded_content_row['embedded_file_id']);
                $embedded_content_file->delete($errmsg);
            }

            if (!empty ($this->embedded_content_row['fallback_content_id'])) {
                $fallback_content = self :: getObject($this->embedded_content_row['fallback_content_id']);
                $fallback_content->delete($errmsg);
            }
        } else {
            $errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
        }

        return parent :: delete($errmsg);
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


