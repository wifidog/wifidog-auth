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
/**@file Dependencies.php
 * @author Copyright (C) 2005 Philippe April <philippe@ilesansfil.org>
 */

class Dependencies
{
    static public function check($component, & $errmsg) {
        $components = array(
            "ImageGraph" => array(
                "name" => "PEAR::Image_Graph",
                "file" => "Image/Graph.php"
            )
        );

        if (isset($components[$component])) {
            $component_info = $components[$component];

            if (file_exists($component_info["file"])) {

                if (include_once($component_info["file"])) {
                    return true;
                } else {
                    $errmsg = $component_info["name"] . _(" is not working properly");
                    return false;
                }

            } else {
                $errmsg = $component_info["name"] . _(" is not installed");
                return false;
            }
        } else {
            throw new Exception("Component not found");
        }
    }

} // End class
?>