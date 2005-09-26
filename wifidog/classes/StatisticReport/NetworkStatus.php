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
/**@file NetworkStatus.php
 * @author Copyright (C) 2005 Technologies Coeus inc.
 */

require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/StatisticReport.php';

/* General report about a node */
class NetworkStatus extends StatisticReport
{
	/** Get the report's name.  Must be overriden by the report class 
	 * @return a localised string */
	public static function getReportName()
	{
		return _("Network status information");
	}

	/** Constructor
		 * @param $statistics_object Mandatory to give the report it's context */
	protected function __construct(Statistics $statistics_object)
	{
		parent :: __construct($statistics_object);
	}

	/** Get the actual report.  
	 * Classes must override this, but must call the parent's method with what
	 * would otherwise be their return value and return that instead.
	 * @param $child_html The child method's return value
	 * @return A html fragment 
	 */
	public function getReportUI($child_html = null)
	{
		global $db;
		$html = '';
		$selected_network = $this->stats->getSelectedNetworks();
		if (count($selected_network) == 0)
		{
			$html .= _("Sorry, this report requires you to select individual networks");
		}
		else
		{
			//pretty_print_r($this->stats->getSelectedNodes ());
			foreach ($selected_network as $network_id => $networkObject)
			{
				$html .= "<fieldset class='pretty_fieldset'>";
				$html .= "<legend>".$networkObject->getName()."</legend>";
				$html .= "<table>";

				$html .= "<tr>";
				$html .= "  <th>"._("Name")."</th>";
				$html .= "  <td>".$networkObject->getName()."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Creation date")."</th>";
				$html .= "  <td>".$networkObject->getCreationDate()."</td>";
				$html .= "</tr>";

				$html .= "<tr>";
				$html .= "  <th>"._("Homepage")."</th>";
				$html .= "  <td>".$networkObject->getHomepageURL()."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Tech support email")."</th>";
				$html .= "  <td>".$networkObject->getTechSupportEmail()."</td>";
				$html .= "</tr>";

				$html .= "<tr>";
				$html .= "  <th>"._("Validation grace time")."</th>";
				$html .= "  <td>".Utils :: convertSecondsToWords($networkObject->getValidationGraceTime())."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Validation email")."</th>";
				$html .= "  <td>".$networkObject->getValidationEmailFromAddress()."</td>";
				$html .= "</tr>";

				$html .= "<tr>";
				$html .= "  <th>"._("Allows multiple login")."?</th>";
				$html .= "  <td>". ($networkObject->getMultipleLoginAllowed() ? 'yes' : 'no')."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Splash only nodes allowed")."?</th>";
				$html .= "  <td>". ($networkObject->getSplashOnlyNodesAllowed() ? 'yes' : 'no')."</td>";
				$html .= "</tr>";

				$html .= "<tr>";
				$html .= "  <th>"._("Custom portal redirect nodes allowed")."?</th>";
				$html .= "  <td>". ($networkObject->getCustomPortalRedirectAllowed() ? 'yes' : 'no')."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Number of users").":</th>";
				$html .= "  <td>".$networkObject->getNumUsers()."</td>";
				$html .= "</tr>";

				$html .= "<tr>";
				$html .= "  <th>"._("Number of validated users").":</th>";
				$html .= "  <td>".$networkObject->getNumValidUsers()."</td>";
				$html .= "</tr>";

				$html .= "<tr class='odd'>";
				$html .= "  <th>"._("Number of users currently online").":</th>";
				$html .= "  <td>".$networkObject->getNumOnlineUsers()."</td>";
				$html .= "</tr>";
				$html .= "</table>";
				$html .= "</fieldset>";
			} //End foreach
		} //End else
		return parent :: getReportUI($html);
	}

} //End class
?>