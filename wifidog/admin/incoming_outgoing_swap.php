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
 * Network status page
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire <bock@step.polymtl.ca> - Technologies Coeus
 * inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

/**
 * @ignore
 */
define('BASEPATH','../');

require_once BASEPATH.'admin/admin_common.php';

echo "<div id='head'><h1>incoming_outgoing_swap</h1></div>\n";
echo "<div id='navLeft'>\n";
//echo get_user_management_menu();
echo "</div>\n";

echo "<div id='content'>\n";
echo "<h3>This script was meant for auth servers that rean prior to the release of wifidog gateway 1.0.2.  Version prior to this would log incomint and outgoing traffic reversed.  This will fix your logs.  You must uncomment COMMIT; in the code to actually swap data, and obviously run it ONLY ONCE!</h3>";
    $results = null;
    $db->ExecSql("SELECT * FROM connections ORDER BY conn_id",$results, true);
    if ($results!=null)
    {
            echo "<PRE>";
            $sql = "BEGIN;\n";
        foreach($results as $row)
        {
        //echo "$row[conn_id]: incoming: $row[incoming], outgoing: $row[outgoing]\n";
         $sql .= "UPDATE connections SET incoming=$row[outgoing], outgoing=$row[incoming] WHERE conn_id=$row[conn_id];\n";


        }
    $sql .= "ROLLBACK;\n" ;
//	$sql .= "COMMIT;\n" ;
                 //echo "$sql\n</pre>";
    }
    $db->ExecSqlUpdate($sql, true);

    echo "</div>\n";

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
