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
require_once BASEPATH.'classes/GenericObject.php';
require_once BASEPATH.'classes/Content.php';

/** Abstract a Network.  A network is an administrative entity with it's own users, nodes and authenticator. */
class Network implements GenericObject
{
	private $id; /**< The network id */

	/** Get an instance of the object
	* @see GenericObject
	* @param $id The object id
	* @return the Content object, or null if there was an error (an exception is also thrown)
	*/
	static public function getObject($id)
	{
		return new self($id);
	}

	/** Create a new Content object in the database 
	 * @see GenericObject
	 * @return the newly created object, or null if there was an error
	 */
	static function createNewObject()
	{
		return null; /* Unsupported */
	}

	/** Get an interface to pick a network.  If there is only one network available, no interface is actually shown
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	* @return html markup
	*/
	public static function getSelectNetworkUI($user_prefix)
	{
		global $AUTH_SOURCE_ARRAY;
		$html = '';
		$name = "{$user_prefix}";
		$html .= "Network: \n";
		$number_of_networks = count($AUTH_SOURCE_ARRAY);
		if ($number_of_networks > 1)
		{
			$i = 0;
			foreach ($AUTH_SOURCE_ARRAY as $network_id => $network_info)
			{
				$tab[$i][0] = $network_id;
				$tab[$i][1] = $network_info['name'];
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);

		}
		else
		{
			foreach ($AUTH_SOURCE_ARRAY as $network_id => $network_info) //iterates only once...
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
		$name = "{$user_prefix}";
		return new self($_REQUEST[$name]);
	}

	private function __construct($p_network_id)
	{
		global $AUTH_SOURCE_ARRAY;
		$found = false;
		foreach ($AUTH_SOURCE_ARRAY as $network_id => $network_info)
		{
			if ($p_network_id == $network_id)
			{
				$found = true;
			}
		}
		if (!$found)
		{
			throw new Exception(_("The secified network doesn't exist: ").$p_network_id);
		}
		$this->id = $p_network_id;
	}

	/** Retreives the id of the object 
	 * @return The id */
	public function getId()
	{
		return $this->id;
	}
	/**Get an array of all Content linked to the network
	* @return an array of Content or an empty arrray */
	function getAllContent()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM network_has_content WHERE network_id='$this->id' ORDER BY subscribe_timestamp";
		$db->ExecSql($sql, $content_rows, false);
		if ($content_rows != null)
		{
			foreach ($content_rows as $content_row)
			{
				$retval[] = Content :: getObject($content_row['content_id']);
			}
		}
		return $retval;
	}

	/** Retreives the admin interface of this object.
	 * @return The HTML fragment for this interface */
	public function getAdminUI()
	{
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Network (".get_class($this)." instance)</div>\n";

		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Network content:")."</div>\n";

		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->getAllContent() as $content)
		{
			$html .= "<li class='admin_section_list_item'>\n";
			$html .= "<div class='admin_section_data'>\n";
			$html .= $content->getListUI();
			$html .= "</div'>\n";
			$html .= "<div class='admin_section_tools'>\n";
			$name = "content_group_".$this->id."_element_".$content->GetId()."_erase";
			$html .= "<input type='submit' name='$name' value='"._("Remove")."' onclick='submit();'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}
		$html .= "<li class='admin_section_list_item'>\n";
		$name = "network_{$this->id}_new_content";
		$html .= Content :: getSelectContentUI($name, "AND content_id NOT IN (SELECT content_id FROM network_has_content WHERE network_id='$this->id')");
		$name = "network_{$this->id}_new_content_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add")."' onclick='submit();'>";
		$html .= "</li>\n";
		$html .= "</ul>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		return $html;
	}
	/** Process admin interface of this object.
	*/
	public function processAdminUI()
	{

		foreach ($this->getAllContent() as $content)
		{
			$name = "content_group_".$this->id."_element_".$content->GetId()."_erase";
			if (!empty ($_REQUEST[$name]))
			{
				$this->removeContent($content);
			}
		}

		$name = "network_{$this->id}_new_content_submit";
		if (!empty ($_REQUEST[$name]))
		{
			$name = "network_{$this->id}_new_content";
			$content = Content :: processSelectContentUI($name);
			$this->addContent($content);
		}
	}

/** Add network-wide content to this network */
	public function addContent(Content $content)
	{
		global $db;
		$content_id=$db->EscapeString($content->getId());
		$sql = "INSERT INTO network_has_content (network_id, content_id) VALUES ('$this->id','$content_id')";
		$db->ExecSqlUpdate($sql, false);
	}
	
/** Remove network-wide content from this network */
	public function removeContent(Content $content)
	{
		global $db;
		$content_id=$db->EscapeString($content->getId());
		$sql = "DELETE FROM network_has_content WHERE network_id='$this->id' AND content_id='$content_id'";
		$db->ExecSqlUpdate($sql, false);
	}

	/** Delete this Object form the it's storage mechanism 
	 * @param &$errmsg Returns an explanation of the error on failure
	 * @return true on success, false on failure or access denied */
	public function delete(& $errmsg)
	{
		$errmsg = _("Network::delete() not supported");
		return false;
	}

	/** Reloads the object from the database.  Should normally be called after a set operation */
	protected function refresh()
	{
		$this->__construct($this->id);
	}

} //End class
?>