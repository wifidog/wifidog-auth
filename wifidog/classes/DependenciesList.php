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
 * @copyright  2006-2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

// Detect Gettext support
if (!function_exists('gettext')) {
    /**
     * Load Locale class if Gettext support is not available
     */
    require_once ('classes/Locale.php');
}

require_once ('classes/Utils.php');

/**
 * This class checks the existence of components required by WiFiDog.
 * Note that it implicitely depends on the defines in include/path_defines_base.php
 *
 * @package    WiFiDogAuthServer
 * @author     Philippe April
 * @author     Max Horváth <max.horvath@freenet.de>
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2007 Philippe April
 * @copyright  2005-2007 Max Horváth, Horvath Web Consulting
 * @copyright  2006-2007 Benoit Grégoire, Technologies Coeus inc.
 */

/**
 * Load required classes
 */
/*WARNING:  You must NOT require anything, or extend anything except Dependency.php.  DependenciesList is used from the install script.*/
require_once ('classes/Dependency.php');
class DependenciesList
{
    public static function &getObject($id)
    {
        $retval = new self();
        return $retval;
    }
        /** Retreives the admin interface of this object.
     * @return The HTML fragment for this interface, or null.
     * If it returns null, this object does not support new object creation */
    public function getAdminUI($userData = null) {
    return self::getAdminUIStatic($userData);
    }
    /** Retreives the admin interface of this object.
     * @return The HTML fragment for this interface, or null.
     * If it returns null, this object does not support new object creation */
    static public function getAdminUIStatic($userData = null) {
        
        $html = '';
        /* PHP version check */
        $okMsg = '<td ALIGN="CENTER" STYLE="background:lime;">OK</td>';
        $errorMsg = '<td ALIGN="CENTER" STYLE="background:red;">ERROR</td>';
        $warningMsg = '<td ALIGN="CENTER" STYLE="background:yellow;">Warning</td>';

        $html .= "<table BORDER=\"1\">";

        /* PHP version check */
        $requiredPHPVersion = '5.0';
        $phpVersion = phpversion();
        $html .= "<tr><td>PHP</td>";
        if (version_compare($phpVersion, $requiredPHPVersion, ">=")) {
            $html .= "$okMsg<td>$phpVersion</td>"; // Version 5.0.0 or later
        }
        else {
            $html .= "$errorMsg<td>".sprintf(_("Version %s needed"), $requiredPHPVersion)."</td>"; // Version < 5.0.0
            $userData['error'] = 1;
        }
        $html .= "</tr>";

        //Be carefull, postgres version check will fail if there wasn't a db connexion yet.
        $pgVersionArray = @pg_version();
        $pgVersionArray?$pgVersion=$pgVersionArray['server']:$pgVersion=null;
if($pgVersion){
        $postgresRecommendedVersion = '8.0';
        $postgresRequiredVersion = '7.4';
        $html .= "<tr><td>PostgreSQL</td>";
        if (version_compare($pgVersion, $postgresRecommendedVersion, ">=")) {
            $html .= "$okMsg<td>$pgVersion</td>"; // Version 5.0.0 or later
        }
        else if (version_compare($pgVersion, $postgresRequiredVersion, ">=")) {
            $html .= "$warningMsg<td>".sprintf(_("%s may work, but version %s is recommended"), $pgVersion, $postgresRecommendedVersion)."</td>"; // Version < 5.0.0
        }
        else {
            $html .= "$errorMsg<td>".sprintf(_("%s is too old, version %s needed"),$pgVersion, $postgresRecommendedVersion)."</td>"; // Version < 5.0.0
            $userData['error'] = 1;
        }
}
        $html .= "</tr>";
        $html .= "</table>";
        $components = Dependency::getDependency();
        $html .= "<table BORDER=\"1\">\n";
        $html .= "<tr><th>"._("Component").'<br/>'._("Click for the component's website")."</th>\n";
        $html .= "<th>"._("Type")."</th>\n";
        $html .= "<th>"._("Status")."</th>\n";
        $html .= "<th>"._("Description")."</th>\n";
        $html .= "<th>"._("Message")."</th>\n";
        $html .= "</tr>\n";
         
        foreach ($components as $dependency) {
            $html .= "<tr>\n";
            $websiteUrl = $dependency->getWebsiteURL();
            $component_key = $dependency->getId();
            $description = $dependency->getDescription();
            $mandatory = $dependency->isMandatory();
            $type = $dependency->getType();
            if($websiteUrl){
                $html .= "<td><A HREF=\"$websiteUrl\">$component_key</A></td>\n";
            }
            else{
                $html .= "<td>$component_key</td>\n";
            }
            $html .= "<td>$type</td>\n";
            $available = Dependency::check($component_key, $message);
            if ($available) {
                $html .=  "$okMsg<td>$description</td><td>&nbsp;</td></tr>\n";
            }
            else {
                if ($mandatory) {
                    $html .=  "$errorMsg<td>$description</td><td>$message</td></tr>\n";
                    $error = 1;
                }
                else {
                    $html .=  "$warningMsg<td>$description</td><td>$message</td></tr>\n";
                }
            }
        }
        $html .=  "</table>\n";
         
        return $html;
    }

    /** Process admin interface of this object.
     */
    public function processAdminUI() {
        return null;
    }

    /** Menu hook function */
    static public function hookMenu() {
        $items = array();
        $server = Server::getServer();
        if(Security::hasPermission(Permission::P('SERVER_PERM_EDIT_SERVER_CONFIG'), $server))
        {
            $items[] = array('path' => 'server/admin',
            'title' => _("Dependencies"),
            'url' => BASE_URL_PATH."admin/generic_object_admin.php?object_class=DependenciesList&action=edit&object_id=DUMMY"
		);
        }
        return $items;
    }
}//End class

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
