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
  /**@file stats_all_networks.inc.php
   * @author Copyright (C) 2005 Philippe April
   */

$html .= "<fieldset class='pretty_fieldset'>";
$html .= "<legend>" . _("All networks") . "</legend>";
$html .= "<table>";

$even = 0;
foreach (Network::getAllNetworks() as $network) {
    $html .= $even ? "<tr>\n" : "<tr class='odd'>\n";
    if ($even == 0)
        $even = 1;
    else
        $even = 0;
    $html .= "  <td><a href='?network_id={$network->getId()}'>{$network->getName()}</a></td>\n";
    $html .= "</tr>";
}
$html .= "</table>";
$html .= "</fieldset>";
?>
