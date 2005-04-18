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
/**@file Network.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>
 */
require_once BASEPATH.'include/common.php';

/** Abstract a Network.  A network is an administrative entity with it's own users, nodes and authenticator. */
class Network
{
	private $id; /**< The network id */
	/** Get an interface to pick a network.  If there is only onw network available, no interface is actually shown
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	 * @return html markup
	 */
	public static function getSelectNetworkUI($user_prefix)
	{
		global $AUTH_SOURCE_ARRAY;
		$html = '';
		$name = "select_network_{$user_prefix}_network_id";
		$html .= "Network: \n";
		$number_of_networks = count($AUTH_SOURCE_ARRAY);
		if ($number_of_networks > 1)
		{
			$i = 0;
			foreach ($AUTH_SOURCE_ARRAY as $network_id=>$network_info)
			{
				$tab[$i][0] = $network_id;
				$tab[$i][1] = $network_info['name'];
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);

		}
		else
		{
			foreach ($AUTH_SOURCE_ARRAY as $network_id=>$network_info) //iterates only once...
			{
				$html .= " $network_info[name] ";
				$html .= "<input type='hidden' name='$name' value='$network_id'>";
			}
		}
	return $html;
	}

	/** Get the selected Network object.
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the Network object
	 */
	static function processSelectNetworkUI($user_prefix)
	{
		$object = null;
		$name = "select_network_{$user_prefix}_network_id";
		return new self($_REQUEST[$name]);
	}

private function __construct($p_network_id)
{
		global $AUTH_SOURCE_ARRAY;
		$found=false;
					foreach ($AUTH_SOURCE_ARRAY as $network_id=>$network_info)
			{
				if($p_network_id==$network_id)
				{
					$found=true;
			}
		}
	if(!$found)
	{
		throw new Exception(_("The secified network doesn't exist: ").$p_network_id);
	}
	$this->id=$p_network_id;
}

	/** Retreives the id of the object 
	 * @return The id */
	public function getId()
	{
		return $this->id;
	}

} //End class
?>

