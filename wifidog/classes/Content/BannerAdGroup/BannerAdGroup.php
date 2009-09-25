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
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: TrivialLangstring.php 1031 2006-05-10 18:56:02Z benoitg $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('classes/Content/ContentGroup/ContentGroup.php');
/**
 * Represents a list of banner ads
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <benoitg@coeus.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 */
class BannerAdGroup extends ContentGroup {
    private $predefinedSizes;
    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @return void     */
    protected function __construct($content_id) {
        parent :: __construct($content_id);
        $criteria_array = array (
            array (
                'isContentType',
                array (
                    array (
                        'SimplePicture'
                    )
                )
            )
        );
        $allowed_content_types = ContentTypeFilter :: getObject($criteria_array);
        $this->setAllowedContentTypes($allowed_content_types);
        $this->predefinedSizes = array (
            /* Rectangles and Pop-Ups */
			'300px/250px' => _('300x250 - IAB Medium Rectangle'), 
			'250px/250px' => _('250x250 - IAB Square Pop-Up'), 
			'240px/400px' => _('240x400 - IAB Vertical Rectangle'), 
			'336px/280px' => _('336x280 - IAB Large Rectangle'), 
			'180px/150px' => _('180x150 - IAB Rectangle'),
			        /* Banners and Buttons */
			'468px/60px' => _('468x60  - IAB Full Banner'), 
			'234px/60px' => _('234x60  - IAB Half Banner'), 
			'88px/31px' => _('88x31   - IAB Micro Bar'), 
			'120px/90px' => _('120x90  - IAB Button 1'), 
			'120px/60px' => _('120x60  - IAB Button 2'), 
			'120px/240px' => _('120x240 - IAB Vertical Banner'), 
			'125px/125px' => _('125x125 - IAB Square Button'), 
			'728px/90px' => _('728x90  - IAB Leaderboard'),
			        /* Skyscrapers */
			'160px/600px' => _('160x600 - IAB Wide Skyscraper'), 
			'120px/600px' => _('120x600 - IAB Skyscraper'), 
			'300px/600px' => _('300x600 - IAB Half Page Ad'));
	    }

    /** When a content object is set as Simple, it means that is is used merely to contain it's own data.  No title, description or other metadata will be set or displayed, during display or administration
     * @return true or false */
    public function isSimpleContent() {
        return true;
    }

    /**Get all elements.  Set the max witdh and height
     * @return an array of ContentGroupElement or an empty arrray */
    function getElements($additional_where = null) {
        $elements = parent :: getElements($additional_where);

        foreach ($elements as $element) {
            $picture = $element->getDisplayedContent();
            $picture->configEnableEditWidthHeight(false);
            $picture->setMaxDisplayWidth($this->getKVP(get_class($this) . '_max_width'));
            $picture->setMaxDisplayHeight($this->getKVP(get_class($this) . '_max_height'));
        }
        return $elements;
    }
    public function getAdminUI($subclass_admin_interface = null, $title = null) {
        $html = null;
        /* width and height */
        $max_width = $this->getKVP(get_class($this) . '_max_width');
        $max_height = $this->getKVP(get_class($this) . '_max_height');

        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Maximum display size") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "banner_add_group_{this->getId()}_widthxheight";
        $html .= FormSelectGenerator :: generateFromKeyLabelArray($this->predefinedSizes, $max_width . '/' . $max_height, $name, null, true, _('Use the values below for width and height'));
        $html .= "</div>\n";
        $html .= "</li>\n";

        /*max_width*/
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Width") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "banner_add_group_{this->getId()}_max_width";
        $html .= "<input type='text' size='6' value='$max_width' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";

        /*max_height*/
        $html .= "<li class='admin_element_item_container'>\n";
        $html .= "<div class='admin_element_label'>" . _("Height") . ": </div>\n";
        $html .= "<div class='admin_element_data'>\n";
        $name = "banner_add_group_{this->getId()}_max_height";
        $html .= "<input type='text' size='6' value='$max_height' name='$name'>\n";
        $html .= "</div>\n";
        $html .= "</li>\n";
        return parent :: getAdminUI($html, $title);
    }

    /**
    * Processes the input of the administration interface for Picture
    *
    * @return void
    */
    public function processAdminUI() {
        if ($this->DEPRECATEDisOwner(User :: getCurrentUser()) || User :: getCurrentUser()->DEPRECATEDisSuperAdmin()) {
            parent :: processAdminUI();
            /* width and height */
            $name = "banner_add_group_{this->getId()}_widthxheight";
            $widthxheight = FormSelectGenerator :: getResult($name, null);
            //pretty_print_r($widthxheight);

            $name = "banner_add_group_{this->getId()}_max_width";
            $max_width = $_REQUEST[$name];
            /*max_height*/
            $name = "banner_add_group_{this->getId()}_max_height";
            $max_height = $_REQUEST[$name];
            if (!empty ($widthxheight)) {
                $widthxheightArray = explode('/', $widthxheight);
                $max_width_select = $widthxheightArray[0];
                $max_height_select = $widthxheightArray[1];
                if (($max_width_select != $max_width || $max_height_select != $max_height) && ($max_width == $this->getKVP(get_class($this) . '_max_width') && $max_height == $this->getKVP(get_class($this) . '_max_height'))) {
                    /* Width and height weren't manually changed, or were empty */
                    $max_width = $max_width_select;
                    $max_height = $max_height_select;
                }
            }
            $this->setKVP(get_class($this) . '_max_width', $max_width);
            $this->setKVP(get_class($this) . '_max_height', $max_height);
        }
    }
    /**
     * Reloads the object from the database.
     *
     * Should normally be called after a set operation.
     *
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope
     *
     * @return void
    
     */
    private function refresh() {
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