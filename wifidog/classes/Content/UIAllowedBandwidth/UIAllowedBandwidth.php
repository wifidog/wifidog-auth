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
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2008 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $ Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/LocaleList.php');
require_once('classes/Locale.php');

/**
 * Shows the remaining bandwidth for the current user
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 */
class UIAllowedBandwidth extends Content
{

    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @return void     */
    protected function __construct($content_id)
    {
        parent::__construct($content_id);
    }

    /**
     * Retreives the admin interface of this object. Anything that overrides
     * this method should call the parent method with it's output at the END of
     * processing.
     * @param string $subclass_admin_interface HTML content of the interface
     * element of a children.
     * @param string $type_interface SIMPLE pour éditer un seul champ, COMPLETE
     *                               pour voir toutes les chaînes, LARGE pour
     *                               avoir un textarea.
     * @return string The HTML fragment for this interface.
     */
    public function getAdminUI($subclass_admin_interface = null, $title = null, $type_interface = "LARGE") {
        // Init values.
        $html = '';
        $html .= $subclass_admin_interface;
        if (!empty ($this->allowed_html_tags)) {
            $html .= "<div class='admin_section_hint'>" . _("This content type will display a a graph of the user's remaining bandwidth according to dyabuse control rules.") . "</div>";
        }
        return Content :: getAdminUI($html, $title);
    }
    /* Format human readable filesize */
    static function formatSize($size, $round = 0) {
        //Size must be bytes!
        $sizes = array(_('B'), _('kB'), _('MB'), _('GB'), _('TB'), _('PB'), _('EB'), _('ZB'), _('YB'));
        for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) $size /= 1024;
        return round($size,$round).$sizes[$i];
    }
    /**
     * Retreives the user interface of this object.
     *
     * Anything that overrides this method should call the parent method with
     * it's output at the END of processing.
     * @return string The HTML fragment for this interface
     */
    public function getUserUI() {
        // Init values
        $current_node = Node :: getCurrentNode();
        $smarty = SmartyWifidog::getObject();
        $html = null;

        $user = User::getCurrentUser();
        if ($user) {
            if($current_node){
                $abuseControlReport = User::getAbuseControlConnectionHistory($user, null, $current_node);
                if($abuseControlReport) {
                    //pretty_print_r($abuseControlReport);
                    $db = AbstractDb::getObject();
                    $html .= sprintf(_("During the last %s period, you transfered %s / %s and were connected %s / %s at this node.  Throughout the network, you transfered %s / %s and were connected %s / %s"),
                    $abuseControlReport['connection_limit_window']?$db->GetIntervalStrFromDurationArray($db->GetDurationArrayFromIntervalStr($abuseControlReport['connection_limit_window'])):_("Unknown"),
                    self::formatSize($abuseControlReport['node_total_bytes']),
                    $abuseControlReport['connection_limit_node_max_total_bytes']?self::formatSize($abuseControlReport['connection_limit_node_max_total_bytes']):_("Unlimited"),
                    $abuseControlReport['node_duration']?$db->GetIntervalStrFromDurationArray($db->GetDurationArrayFromIntervalStr($abuseControlReport['node_duration'])):_("None"),
                    $abuseControlReport['connection_limit_node_max_usage_duration']?$abuseControlReport['connection_limit_node_max_usage_duration']:_("Unlimited"),
                    self::formatSize($abuseControlReport['network_total_bytes']),
                    $abuseControlReport['connection_limit_network_max_total_bytes']?self::formatSize($abuseControlReport['connection_limit_network_max_total_bytes']):_("Unlimited"),
                    $abuseControlReport['network_duration']?$db->GetIntervalStrFromDurationArray($db->GetDurationArrayFromIntervalStr($abuseControlReport['network_duration'])):_("None"),
                    $abuseControlReport['connection_limit_network_max_usage_duration']?$abuseControlReport['connection_limit_network_max_usage_duration']:_("Unlimited")
                    );

                }
                else {
                    $html .= _("Abuse control is currently disabled");
                }
            }
            else {
                $html .= _("Unable to retrieve node specific restrictions (you are not at a node)");
            }
            $this->setUserUIMainDisplayContent($html);

            return Content :: getUserUI();
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


