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
/**@file ContentGroupElement.php
 * This file isn't in Content because it must only be instanciated as part of a ContentGroup
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */
require_once BASEPATH.'classes/Content/ContentGroup/ContentGroup.php';
require_once BASEPATH.'classes/Node.php';

/** the elements of a ContentGroup */
class ContentGroupElement extends Content
{
	private $content_group_element_row;

	/** Like the same method as defined in Content, this method will create a ContentGroupElement based on the content type specified by getNewContentUI
     * OR get an existing element by getSelectContentUI
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @param $content_group Must be present
     * @param $associate_existing_content boolean : If set to true, will get an
     * existing element instead of creating a new content.
	 * @return the ContentGroup object, or null if the user didn't greate one
	 */
	static function processNewContentUI($user_prefix, ContentGroup $content_group, $associate_existing_content = false)
	{
		global $db;
		$content_group_element_object = null;
        if($associate_existing_content == true)
            $name = "{$user_prefix}_add";
		else
            $name = "get_new_content_{$user_prefix}_add";
		if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
		{
			/* Get the display order to add the GontentGroupElement at the end */
			$sql = "SELECT MAX(display_order) as max_display_order FROM content_group_element WHERE content_group_id='".$content_group->getId()."'";
			$db->execSqlUniqueRes($sql, $max_display_order_row, false);
			$display_order = $max_display_order_row['max_display_order'] + 1;

            if($associate_existing_content == true)
                $name = "{$user_prefix}";
			else
                $name = "get_new_content_{$user_prefix}_content_type";

			$content_id = get_guid();
			$content_type = 'ContentGroupElement';
			$sql = "INSERT INTO content (content_id, content_type) VALUES ('$content_id', '$content_type');";
			if (!$db->ExecSqlUpdate($sql, false))
			{
				throw new Exception(_('Unable to insert new content into database!'));
			}
			$sql = "INSERT INTO content_group_element (content_group_element_id, content_group_id, display_order) VALUES ('$content_id', '".$content_group->GetId()."', $display_order);";
			if (!$db->ExecSqlUpdate($sql, false))
			{
				throw new Exception(_('Unable to insert new content into database!'));
			}
			$content_group_element_object = self :: getObject($content_id);

            $content_ui_result = FormSelectGenerator :: getResult($name, null);
            if($associate_existing_content == true)
                $displayed_content_object = self :: getObject($content_ui_result);
            else
                $displayed_content_object = self :: createNewObject($content_ui_result);
			$content_group_element_object->replaceDisplayedContent($displayed_content_object);
		}
		return $content_group_element_object;
	}

	function __construct($content_id)
	{
		parent :: __construct($content_id);
		$this->setIsTrivialContent(true);

		global $db;
		$content_id = $db->EscapeString($content_id);

		$sql_select = "SELECT * FROM content_group_element WHERE content_group_element_id='$content_id'";
		$db->ExecSqlUniqueRes($sql_select, $row, false);
		if ($row == null)
		{
			$sql = "--The database was corrupted, let's fix it \n"."DELETE FROM content WHERE content_id='$content_id'";
			$db->ExecSqlUpdate($sql, true);
		}
		$this->content_group_element_row = $row;
		/* A content group element is NEVER persistent */
		parent :: setIsPersistent(false);

	}

	public function getAdminUI($subclass_admin_interface = null)
	{
		$html = '';
		$html .= "<div class='admin_class'>ContentGroupElement (".get_class($this)." instance)</div>\n";

		/* display_order */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>Display order: </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "content_group_element_".$this->id."_display_order";
		$html .= "<input type='text' name='$name' value='".$this->getDisplayOrder()."' size='2'>\n";
		$html .= _("(Ignored if display type is random)")."\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* content_group_element_has_allowed_nodes */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("AllowedNodes:")."</div>\n";
		$html .= _("(Content can be displayed on ANY node unless one or more nodes are selected)")."\n";
		$html .= "<ul class='admin_section_list'>\n";

		global $db;
		$sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
		$db->ExecSql($sql, $allowed_node_rows, false);
		if ($allowed_node_rows != null)
		{
			foreach ($allowed_node_rows as $allowed_node_row)
			{
				$node = Node :: getObject($allowed_node_row['node_id']);
				$html .= "<li class='admin_section_list_item'>\n";
				$html .= "<div class='admin_section_data'>\n";
				$html .= "".$node->GetId().": ".$node->GetName()."";
				$html .= "</div>\n";
				$html .= "<div class='admin_section_tools'>\n";
				$name = "content_group_element_".$this->id."_allowed_node_".$node->GetId()."_remove";
				$html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
				$html .= "</div>\n";
				$html .= "</li>\n";

			}
		}

		$html .= "<li class='admin_section_list_item'>\n";

		$sql_additional_where = "AND node_id NOT IN (SELECT node_id FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id')";
		$name = "content_group_element_{$this->id}_new_allowed_node";
		$html .= Node :: getSelectNodeUI($name, $sql_additional_where);
		$name = "content_group_element_{$this->id}_new_allowed_node_submit";
		$html .= "<input type='submit' name='$name' value='"._("Add new allowed node")."'>";
		$html .= "</li'>\n";

		$html .= "</ul>\n";
		$html .= "</div>\n";

		/* displayed_content_id */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<span class='admin_section_title'>"._("Displayed content:")."</span>\n";
		if (empty ($this->content_group_element_row['displayed_content_id']))
		{
            $html .= "<b>"._("Add a new displayed content OR select an existing one")."</b><br>";
			$html .= self :: getNewContentUI("content_group_element_{$this->id}_new_displayed_content")."<br>";
            $html .= self :: getSelectContentUI("content_group_element_{$this->id}_new_displayed_existing_element", "AND content_id != '$this->id'");
            $html .= "<input type='submit' name='content_group_element_{$this->id}_new_displayed_existing_element_add' value='"._("Add")."'>";
		}
		else
		{
			$displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
			$html .= $displayed_content->getAdminUI();
			$html .= "<div class='admin_section_tools'>\n";
			$name = "content_group_element_{$this->id}_erase_displayed_content";
			$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
			$html .= "</div>\n";
		}
		$html .= "</div>\n";

		$html .= $subclass_admin_interface;
		return parent :: getAdminUI($html);
	}

	/**Replace and delete the old displayed_content (if any) by the new content (or no content)
	 * @param $new_displayed_content Content object or null.  If null the old content is still deleted.
	 */
	private function replaceDisplayedContent($new_displayed_content)
	{
		global $db;
		$old_displayed_content = null;
		if (!empty ($this->content_group_element_row['displayed_content_id']))
		{
			$old_displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
		}
		if ($new_displayed_content != null)
		{
			$new_displayed_content_id_sql = "'".$new_displayed_content->GetId()."'";
		}
		else
		{
			$new_displayed_content_id_sql = "NULL";
		}

		$db->ExecSqlUpdate("UPDATE content_group_element SET displayed_content_id = $new_displayed_content_id_sql WHERE content_group_element_id = '$this->id'", FALSE);

		if ($old_displayed_content != null)
		{
			$old_displayed_conten->delete($errmsg);
		}

	}

	function processAdminUI()
	{
		parent :: processAdminUI();

		/* display_order */
		$name = "content_group_element_".$this->id."_display_order";
		$this->setDisplayOrder($_REQUEST[$name]);

		/* content_group_element_has_allowed_nodes */
		global $db;
		$sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
		$db->ExecSql($sql, $allowed_node_rows, false);
		if ($allowed_node_rows != null)
		{
			foreach ($allowed_node_rows as $allowed_node_row)
			{
				$node = Node :: getObject($allowed_node_row['node_id']);
				$name = "content_group_element_".$this->id."_allowed_node_".$node->GetId()."_remove";
				if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
				{
					$sql = "DELETE FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id' AND node_id='".$node->GetId()."'";
					$db->ExecSqlUpdate($sql, false);
				}
			}
		}
		$name = "content_group_element_{$this->id}_new_allowed_node_submit";
		if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
		{
			$name = "content_group_element_{$this->id}_new_allowed_node";
			$node = Node :: processSelectNodeUI($name);
			$node_id = $node->GetId();
			$db->ExecSqlUpdate("INSERT INTO content_group_element_has_allowed_nodes (content_group_element_id, node_id) VALUES ('$this->id', '$node_id')", FALSE);
		}

		/* displayed_content_id */
		if (empty ($this->content_group_element_row['displayed_content_id']))
		{
            // Could be either a new content or existing content ( try both successively )
			$displayed_content = Content :: processNewContentUI("content_group_element_{$this->id}_new_displayed_content");
            if ($displayed_content == null)
                $displayed_content = Content :: processNewContentUI("content_group_element_{$this->id}_new_displayed_existing_element", true);
                
			if ($displayed_content != null)
			{
				$displayed_content_id = $displayed_content->GetId();
				$db->ExecSqlUpdate("UPDATE content_group_element SET displayed_content_id = '$displayed_content_id' WHERE content_group_element_id = '$this->id'", FALSE);
				$displayed_content->setIsPersistent(false);
			}
		}
		else
		{
			$displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
			$name = "content_group_element_{$this->id}_erase_displayed_content";
			if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
			{
                if($displayed_content->delete($errmsg) != false)
				    $db->ExecSqlUpdate("UPDATE content_group_element SET displayed_content_id = NULL WHERE content_group_element_id = '$this->id'", FALSE);
                else
                    echo $errmsg;
			}
			else
			{
				$displayed_content->processAdminUI();
			}
		}

	}

	/** Get the order of the element in the content group
	 * @return the order of the element in the content group */
	public function getDisplayOrder()
	{
		return $this->content_group_element_row['display_order'];
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI($subclass_user_interface = null)
	{
		if (!empty ($this->content_group_element_row['displayed_content_id']))
		{
			$displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
            // If the content group logging is disabled, all the children will inherit this property temporarly
            if($this->getLoggingStatus() == false)
                $displayed_content->setLoggingStatus(false);
			$displayed_content_html = $displayed_content->getUserUI();
		}
		$html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>ContentGroupElement (".get_class($this)." instance)</div>\n";
		$html .= $displayed_content_html;
		$html .= $subclass_user_interface;
		$html .= "</div>\n";
		return parent :: getUserUI($html);
	}

	/** Is this Content element displayable at this hotspot
	 * @param $node Node, optionnal
	 * @return true or false */
	public function isDisplayableAt($node)
	{
		$retval = false;

		global $db;
		$sql = "SELECT * FROM content_group_element_has_allowed_nodes WHERE content_group_element_id='$this->id'";
		$db->ExecSql($sql, $allowed_node_rows, false);
		if ($allowed_node_rows != null)
		{
			if ($node)
			{
				$node_id = $node->getId();
				/** @todo:  Proper algorithm, this is a dirty and slow hack */
				foreach ($allowed_node_rows as $allowed_node_row)
				{
					if ($allowed_node_row['node_id'] == $node_id)
					{
						$retval = true;
					}
				}
			}
			else
			{
				/* There are allowed nodes, but we don't know at which node we want to display */
				$retval = false;
			}
		}
		else
		{
			/* No allowed node means all nodes are allowed */
			$retval = true;
		}

		return $retval;
	}

	/** Set the order of the element in the content group
	 * @param $order*/
	public function setDisplayOrder($order)
	{
		if ($order != $this->getDisplayOrder()) /* Only update database if there is an actual change */
		{
			global $db;
			$order = $db->EscapeString($order);
			$db->ExecSqlUpdate("UPDATE content_group_element SET display_order = $order WHERE content_group_element_id = '$this->id'", false);
		}
	}

	/** Override the method in Content.  The owners of the content element are always considered to be the ContentGroup's
	 * @param $user User object:  the user to be tested.
	 * @return true if the user is a owner, false if he isn't of the user is null */
	public function isOwner($user)
	{
		$content_group = Content :: getObject($this->content_group_element_row['content_group_id']);
		return $content_group->isOwner($user);
	}

	/** Delete this Content from the database 
	 * @todo Implement proper Access control */
	public function delete(& $errmsg)
	{
		if ($this->isPersistent() == false && !empty ($this->content_group_element_row['displayed_content_id']))
		{
			$displayed_content = self :: getObject($this->content_group_element_row['displayed_content_id']);
            print_r($displayed_content);
			/*if(!$displayed_content->delete($errmsg))
               return false;*/
		}
		//return parent :: delete($errmsg);
	}
} // End class
?>