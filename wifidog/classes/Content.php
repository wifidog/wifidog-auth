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
/**@file Content.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/FormSelectGenerator.php';
require_once BASEPATH.'classes/GenericObject.php';

/** Any type of content */
class Content implements GenericObject
{
	protected $id;
	protected $content_row;
	private $content_type;
	private $is_trivial_content;
	private $is_logging_enabled;

	/** Create a new Content object in the database 
	 * @param $content_type Optionnal, the content type to be given to the new object
	 * @param $id Optionnal, the id to be given to the new Content.  If null, a new id will be assigned
	 * @return the newly created Content object, or null if there was an error (an exception is also trown
	 */
	static function createNewObject($content_type = 'Content', $id = null)
	{
		global $db;
		if (empty ($id))
		{
			$content_id = get_guid();
		}
		else
		{
			$content_id = $db->EscapeString($id);
		}

		if (empty ($content_type))
		{
			throw new Exception(_('Content type is optionnal, but cannot be empty!'));
		}
		else
		{
			$content_type = $db->EscapeString($content_type);
		}
		$sql = "INSERT INTO content (content_id, content_type) VALUES ('$content_id', '$content_type')";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to insert new content into database!'));
		}

		$object = self :: getObject($content_id);
		/* At least add the current user as the default owner */
		$object->AddOwner(User :: getCurrentUser());
		/* By default, make it persistent */
		$object->setIsPersistent(true);

		return $object;
	}
	
		/** Get an interface to create a new object.
	* @return html markup
	*/
	public static function getCreateNewObjectUI()
	{
			$html ='';
			$html .= _("You must select a content type: ");
			$i = 0;
			foreach (self :: getAvailableContentTypes() as $classname)
			{
				$tab[$i][0] = $classname;
				$tab[$i][1] = $classname;
				$i ++;
			}
			$name = "new_content_content_type";
			$default = 'TrivialLangstring';
			$html .= FormSelectGenerator :: generateFromArray($tab, $default, $name, "Content", false);
		
		return $html;
	}

	/** Process the new object interface. 
	 *  Will       return the new object if the user has the credentials
	 * necessary (Else an exception is thrown) and and the form was fully
	 * filled (Else the object returns null).
	 * @return the node object or null if no new node was created.
	 */
	static function processCreateNewObjectUI()
{
	$retval = null;
	$name = "new_content_content_type";
	$content_type = FormSelectGenerator :: getResult($name, "Content");
    if($content_type)
    {
    	$retval = self::createNewObject($content_type);
    }
	
	return $retval;
}
	
	/** Get the Content object, specific to it's content type 
	 * @param $content_id The content id
	 * @return the Content object, or null if there was an error (an exception is also thrown)
	 */
	static function getObject($content_id)
	{
		global $db;
		$content_id = $db->EscapeString($content_id);
		$sql = "SELECT content_type FROM content WHERE content_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
		}
		$content_type = $row['content_type'];
		$object = new $content_type ($content_id);
		return $object;
	}

	/** Get the list of available content type on the system 
	 * @return an array of class names */
	public static function getAvailableContentTypes()
	{
		$dir = BASEPATH.'classes/Content';
		if ($handle = opendir($dir))
		{
			$tab = Array ();
			$i = 0;
			/* This is the correct way to loop over the directory. */
			while (false !== ($file = readdir($handle)))
			{
				if ($file != '.' && $file != '..')
				{
					if (preg_match("/^.*\.php$/", $file) > 0)
					{
						$tab[$i] = $file;
						$i ++;
					}
				}
			}
			closedir($handle);
			//echo $gfs->genererDeArray($tab, $this->GetStylesheet(), "stylesheet", "Theme::AfficherInterfaceAdmin", true, 'Style par défaut ou style du parent');
		}
		else
		{
			throw new Exception(_('Unable to open directory ').$dir);
		}
		$tab = str_ireplace('.php', '', $tab);
		sort($tab);
		return $tab;
	}
    
    /**
     * Get all content, can be restricted to a given content type
     */
    public static function getAllContent($content_type = "")
    {
        global $db;
        $where_clause = "";
        if(!empty($content_type))
        {
            $content_type = $db->EscapeString($content_type);
            $where_clause = "WHERE content_type = '$content_type'";
        } 
        $db->ExecSql("SELECT content_id FROM content $where_clause", $rows, false);
        $objects = array();
        if($rows)
            foreach($rows as $row)
                $objects[] = self::getObject($row['content_id']);
        return $objects;
    }

	/** Get a flexible interface to generate new content objects
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	 * @param $content_type If set, the created content will be of this type, otherwise, the user will have to chose
	 * @return html markup
	 */
	static function getNewContentUI($user_prefix, $content_type = null)
	{
		global $db;
		$html = '';
		$available_content_types = self :: getAvailableContentTypes();

		$name = "get_new_content_{$user_prefix}_content_type";
		if (empty ($content_type))
		{
			$html .= _("Content type: ");
			$i = 0;
			foreach ($available_content_types as $classname)
			{
				$tab[$i][0] = $classname;
				$tab[$i][1] = $classname;
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, 'TrivialLangstring', $name, null, false);
		}
		else
		{
			if (false === array_search($content_type, $available_content_types, true))
			{
				throw new Exception(_("The following content type isn't valid: ").$content_type);
			}
			$html .= "<input type='hidden' name='$name' value='$content_type'>";
		}
		$name = "get_new_content_{$user_prefix}_add";

		if ($content_type)
		{
			$value = _("Add a")." $content_type";
		}
		else
		{
			$value = _("Add");
		}
		$html .= "<input type='submit' name='$name' value='$value'>";
		return $html;
	}

	/** Get the created Content object, IF one was created 
	 * OR Get existing content ( depending on what the user clicked ) 
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @param $associate_existing_content boolean if true allows to get existing
	 * object
	 * @return the Content object, or null if the user didn't greate one
	 */
	static function processNewContentUI($user_prefix, $associate_existing_content = false)
	{
		$object = null;
		if ($associate_existing_content == true)
			$name = "{$user_prefix}_add";
		else
			$name = "get_new_content_{$user_prefix}_add";
		if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
		{
			if ($associate_existing_content == true)
				$name = "{$user_prefix}";
			else
				$name = "get_new_content_{$user_prefix}_content_type";
			
            // The result can be either a Content type or a Content ID depending on the form ( associate_existing_content or NOT )
            $content_ui_result = FormSelectGenerator :: getResult($name, null);
            if($associate_existing_content == true)
                $object = self :: getObject($content_ui_result);
            else
                $object = self :: createNewObject($content_ui_result);
		}
		return $object;
	}

	/** Get an interface to pick content from all persistent content.
	* @param $user_prefix A identifier provided by the programmer to recognise it's generated html form
	  @param $sql_additional_where Addidional where conditions to restrict the candidate objects
	* @return html markup
	*/
	public static function getSelectContentUI($user_prefix, $sql_additional_where = null)
	{
		$html = '';
		$name = "{$user_prefix}";
		$html .= _("Select existing Content : ")."\n";
		global $db;
		$retval = array ();
		$sql = "SELECT * FROM content WHERE is_persistent=TRUE $sql_additional_where ORDER BY creation_timestamp";
		$db->ExecSql($sql, $content_rows, false);
		if ($content_rows != null)
		{
			$i = 0;
			foreach ($content_rows as $content_row)
			{
				$content = Content :: getObject($content_row['content_id']);
				$tab[$i][0] = $content->getId();
				$tab[$i][1] = $content->__toString()." (".get_class($content).")";
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, $name, null, false);

		}
		else
		{
			$html .= "<div class='warningmsg'>"._("Sorry, no content available in the database")."</div>\n";
		}
		return $html;
	}

	/** Get the selected Content object.
	 * @param $user_prefix A identifier provided by the programmer to recognise it's generated form
	 * @return the Content object
	 */
	static function processSelectContentUI($user_prefix)
	{
		$name = "{$user_prefix}";
        if(!empty($_REQUEST[$name]))
            return Content :: getObject($_REQUEST[$name]);
        else
            return null;
	}

	private function __construct($content_id)
	{
		global $db;

		$content_id = $db->EscapeString($content_id);
		$sql = "SELECT * FROM content WHERE content_id='$content_id'";
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
		}
		$this->content_row = $row;
		$this->id = $row['content_id'];
		$this->content_type = $row['content_type'];

		// By default Content display logging is enabled
		$this->setLoggingStatus(true);
	}

	/** A short string representation of the content */
	public function __toString()
	{
		if (empty ($this->content_row['title']))
		{
			$string = _("Untitled content");
		}
		else
		{
			$title = self :: getObject($this->content_row['title']);
			$string = $title->__toString();
		}
		return $string;
	}

	/** Get the true object type represented by this isntance 
	 * @return an array of class names */
	public function getObjectType()
	{
		return $this->content_type;
	}
	
	/**
	 * Get content title
	 * @return content a content sub-class
	 */
	public function getTitle()
	{
		try 
		{
			return self :: getObject($this->content_row['title']);
		}
		catch(Exception $e)
		{
			return null;
		}
	}
	
	/**
	 * Get content description
	 * @return content a content sub-class
	 */
	public function getDescription()
	{
		try 
		{
			return self :: getObject($this->content_row['description']);
		}
		catch(Exception $e)
		{
			return null;
		}
	}
	
	/**
	 * Get content long description
	 * @return content a content sub-class
	 */
	public function getLongDescription()
	{
		try 
		{
			return self :: getObject($this->content_row['long_description']);
		}
		catch(Exception $e)
		{
			return null;
		}
	}
	
	/**
	 * Get content project info
	 * @return content a content sub-class
	 */
	public function getProjectInfo()
	{
		try 
		{
			return self :: getObject($this->content_row['project_info']);
		}
		catch(Exception $e)
		{
			return null;
		}
	}
	
	/**
	 * Get content sponsor info
	 * @return content a content sub-class
	 */
	public function getSponsorInfo()
	{
		try 
		{
			return self :: getObject($this->content_row['sponsor_info']);
		}
		catch(Exception $e)
		{
			return null;
		}
	}

	/** Set the object type of this object 
	 * Note that after using this, the object must be re-instanciated to have the right type
	 * */
	private function setContentType($content_type)
	{
		global $db;
		$content_type = $db->EscapeString($content_type);
		$available_content_types = self :: getAvailableContentTypes();
		if (false === array_search($content_type, $available_content_types, true))
		{
			throw new Exception(_("The following content type isn't valid: ").$content_type);
		}
		$sql = "UPDATE content SET content_type = '$content_type' WHERE content_id='$this->id'";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_("Update was unsuccessfull (database error)"));
		}

	}

	/** Check if a user is one of the owners of the object
	 * @param $user The user to be added to the owners list
	 * @param $is_author Optionnal, true or false.  Set to true if the user is one of the actual authors of the Content
	 * @return true on success, false on failure */
	public function addOwner(User $user, $is_author = false)
	{
		global $db;
		$content_id = "'".$this->id."'";
		$user_id = "'".$db->EscapeString($user->getId())."'";
		$is_author ? $is_author = 'TRUE' : $is_author = 'FALSE';
		$sql = "INSERT INTO content_has_owners (content_id, user_id, is_author) VALUES ($content_id, $user_id, $is_author)";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to insert the new Owner into database.'));
		}

		return true;
	}

	/** Remove an owner of the content
	 * @param $user The user to be removed from the owners list
	 */
	public function deleteOwner(User $user, $is_author = false)
	{
		global $db;
		$content_id = "'".$this->id."'";
		$user_id = "'".$db->EscapeString($user->getId())."'";

		$sql = "DELETE FROM content_has_owners WHERE content_id=$content_id AND user_id=$user_id";

		if (!$db->ExecSqlUpdate($sql, false))
		{
			throw new Exception(_('Unable to remove the owner from the database.'));
		}

		return true;
	}

	/**
	 * Indicates display logging status
	 */
	public function getLoggingStatus()
	{
		return $this->is_logging_enabled;
	}

	/**
	 * Sets display logging status
	 */
	public function setLoggingStatus($status)
	{
		if (is_bool($status))
			$this->is_logging_enabled = $status;
	}

	/** Is this Content element displayable at this hotspot, many classer override this
	 * @param $node Node, optionnal
	 * @return true or false */
	public function isDisplayableAt($node)
	{
		return true;
	}

	/** Check if a user is one of the owners of the object
	 * @param $user User object:  the user to be tested.
	 * @return true if the user is a owner, false if he isn't of the user is null */
	public function isOwner($user)
	{
		global $db;
		$retval = false;
		if ($user != null)
		{
			$user_id = $db->EscapeString($user->GetId());
			$sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id' AND user_id='$user_id'";
			$db->ExecSqlUniqueRes($sql, $content_owner_row, false);
			if ($content_owner_row != null)
			{
				$retval = true;
			}
		}

		return $retval;
	}
	/** Get the authors of the Content
	 * @return null or array of User objects */
	public function getAuthors()
	{
		global $db;
		$retval = array ();
		$sql = "SELECT user_id FROM content_has_owners WHERE content_id='$this->id' AND is_author=TRUE";
		$db->ExecSqlUniqueRes($sql, $content_owner_row, false);
		if ($content_owner_row != null)
		{
			$user = User :: getObject($content_owner_row['user_id']);
			$retval[] = $user;
		}

		return $retval;
	}
	/** @see GenricObject
	 * @return The id */
	public function getId()
	{
		return $this->id;
	}

	/** When a content object is set as trivial, it means that is is used merely to contain it's own data.  No title, description or other data will be set or displayed, during display or administration 
	 * @param $is_trivial true or false */
	public function setIsTrivialContent($is_trivial)
	{
		$this->is_trivial_content = $is_trivial;
	}

	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI($subclass_user_interface = null)
	{
		$html = '';
		$html .= "<div class='user_ui_main_outer'>\n";
		$html .= "<div class='user_ui_main_inner'>\n";
		$html .= "<div class='user_ui_object_class'>Content (".get_class($this)." instance)</div>\n";

		if (!empty ($this->content_row['title']))
		{
			$html .= "<div class='user_ui_title'>\n";
			$title = self :: getObject($this->content_row['title']);
			// If the content logging is disabled, all the children will inherit this property temporarly
			if ($this->getLoggingStatus() == false)
				$title->setLoggingStatus(false);
			$html .= $title->getUserUI();
			$html .= "</div>\n";
		}

		$html .= "<table><tr><td>\n";
		$authors = $this->getAuthors();
		if (count($authors) > 0)
		{
			$html .= "<div class='user_ui_authors'>\n";
			$html .= _("Author(s):");
			foreach ($authors as $user)
			{
				$html .= $user->getUsername()." ";
			}
			$html .= "</div>\n";
		}

		if (!empty ($this->content_row['description']))
		{
			$html .= "<div class='user_ui_description'>\n";
			$description = self :: getObject($this->content_row['description']);
			// If the content logging is disabled, all the children will inherit this property temporarly
			if ($this->getLoggingStatus() == false)
				$description->setLoggingStatus(false);
			$html .= $description->getUserUI();
			$html .= "</div>\n";
		}

		if (!empty ($this->content_row['project_info']) || !empty ($this->content_row['sponsor_info']))
		{
			if (!empty ($this->content_row['project_info']))
			{
				$html .= "<div class='user_ui_projet_info'>\n";
				$html .= "<b>"._("Project information:")."</b>";
				$project_info = self :: getObject($this->content_row['project_info']);
				// If the content logging is disabled, all the children will inherit this property temporarly
				if ($this->getLoggingStatus() == false)
					$project_info->setLoggingStatus(false);
				$html .= $project_info->getUserUI();
				$html .= "</div>\n";
			}

			if (!empty ($this->content_row['sponsor_info']))
			{
				$html .= "<div class='user_ui_sponsor_info'>\n";
				$html .= "<b>"._("Project sponsor:")."</b>";
				$sponsor_info = self :: getObject($this->content_row['sponsor_info']);
				// If the content logging is disabled, all the children will inherit this property temporarly
				if ($this->getLoggingStatus() == false)
					$sponsor_info->setLoggingStatus(false);
				$html .= $sponsor_info->getUserUI();
				$html .= "</div>\n";
			}
		}

		$html .= "</td>\n";

		$html .= "<td>\n$subclass_user_interface</td>\n";
		$html .= "</tr></table>\n";

		$html .= "</div>\n";
		$html .= "</div>\n";
		$this->logContentDisplay();
		return $html;
	}

	/** Log that this content has just been displayed to the user.  Will only log if the user is logged in */
	private function logContentDisplay()
	{
		if ($this->getLoggingStatus() == true)
		{
			// DEBUG::
			//echo "Logging ".get_class($this)." :: ".$this->__toString()."<br>";
			$user = User :: getCurrentUser();
			$node = Node :: getCurrentNode();
			if ($user != null && $node != null)
			{
				$user_id = $user->getId();
				$node_id = $node->getId();
				global $db;

				$sql = "SELECT * FROM content_display_log WHERE user_id='$user_id' AND node_id='$node_id' AND content_id='$this->id'";
				$db->ExecSql($sql, $log_rows, false);
				if ($log_rows != null)
				{
					$sql = "UPDATE content_display_log SET last_display_timestamp = NOW() WHERE user_id='$user_id' AND content_id='$this->id' AND node_id='$node_id'";
				}
				else
				{
					$sql = "INSERT INTO content_display_log (user_id, content_id, node_id) VALUES ('$user_id', '$this->id', '$node_id')";
				}
				$db->ExecSqlUpdate($sql, false);
			}
		}
	}

	/** Retreives the list interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getListUI($subclass_list_interface = null)
	{
		$html = '';
		$html .= "<div class='list_ui_container'>\n";
		$html .= $this->__toString()." (".get_class($this).")\n";
		$html .= $subclass_list_interface;
		$html .= "</div>\n";
		return $html;
	}

	/** Retreives the admin interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getAdminUI($subclass_admin_interface = null)
	{
		global $db;
		$html = '';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Content (".get_class($this)." instance)</div>\n";
		if ($this->getObjectType() == 'Content') /* The object hasn't yet been typed */
		{
			$html .= _("You must select a content type: ");
			$i = 0;
			foreach (self :: getAvailableContentTypes() as $classname)
			{
				$tab[$i][0] = $classname;
				$tab[$i][1] = $classname;
				$i ++;
			}
			$html .= FormSelectGenerator :: generateFromArray($tab, null, "content_".$this->id."_content_type", "Content", false);
		}
		else
			if ($this->is_trivial_content == false)
			{
				/* title */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("Title:")."</div>\n";
				$html .= "<div class='admin_section_data'>\n";
				if (empty ($this->content_row['title']))
				{
					$html .= self :: getNewContentUI("title_{$this->id}_new");
					$html .= "</div>\n";
				}
				else
				{
					$title = self :: getObject($this->content_row['title']);
					$html .= $title->getAdminUI();
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";
					$name = "content_".$this->id."_title_erase";
					$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
					$html .= "</div>\n";
				}
				$html .= "</div>\n";

				/* is_persistent */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>Is persistent (reusable and read-only)?: </div>\n";
				$html .= "<div class='admin_section_data'>\n";
				$name = "content_".$this->id."_is_persistent";
				$this->isPersistent() ? $checked = 'CHECKED' : $checked = '';
				$html .= "<input type='checkbox' name='$name' $checked>\n";
				$html .= "</div>\n";
				$html .= "</div>\n";

				/* description */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("Description:")."</div>\n";
				$html .= "<div class='admin_section_data'>\n";
				if (empty ($this->content_row['description']))
				{
					$html .= self :: getNewContentUI("description_{$this->id}_new");
					$html .= "</div>\n";
				}
				else
				{
					$description = self :: getObject($this->content_row['description']);
					$html .= $description->getAdminUI();
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";
					$name = "content_".$this->id."_description_erase";
					$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
					$html .= "</div>\n";
				}
				$html .= "</div>\n";
				
				/* long description */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("Long description:")."</div>\n";
				$html .= "<div class='admin_section_data'>\n";
				if (empty ($this->content_row['long_description']))
				{
					$html .= self :: getNewContentUI("long_description_{$this->id}_new");
					$html .= "</div>\n";
				}
				else
				{
					$description = self :: getObject($this->content_row['long_description']);
					$html .= $description->getAdminUI();
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";
					$name = "content_".$this->id."_long_description_erase";
					$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
					$html .= "</div>\n";
				}
				$html .= "</div>\n";

				/* project_info */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("Information on this project:")."</div>\n";
				$html .= "<div class='admin_section_data'>\n";
				if (empty ($this->content_row['project_info']))
				{
					$html .= self :: getNewContentUI("project_info_{$this->id}_new");
					$html .= "</div>\n";
				}
				else
				{
					$project_info = self :: getObject($this->content_row['project_info']);
					$html .= $project_info->getAdminUI();
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";
					$name = "content_".$this->id."_project_info_erase";
					$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
					$html .= "</div>\n";
				}
				$html .= "</div>\n";

				/* sponsor_info */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("Sponsor of this project:")."</div>\n";
				$html .= "<div class='admin_section_data'>\n";
				if (empty ($this->content_row['sponsor_info']))
				{
					$html .= self :: getNewContentUI("sponsor_info_{$this->id}_new");
					$html .= "</div>\n";
				}
				else
				{
					$sponsor_info = self :: getObject($this->content_row['sponsor_info']);
					$html .= $sponsor_info->getAdminUI();
					$html .= "</div>\n";
					$html .= "<div class='admin_section_tools'>\n";
					$name = "content_".$this->id."_sponsor_info_erase";
					$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
					$html .= "</div>\n";
				}
				$html .= "</div>\n";

				/* content_has_owners */
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<span class='admin_section_title'>"._("Content owner list")."</span>\n";
				$html .= "<ul class='admin_section_list'>\n";

				global $db;
				$sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id'";
				$db->ExecSql($sql, $content_owner_rows, false);
				if ($content_owner_rows != null)
				{
					foreach ($content_owner_rows as $content_owner_row)
					{
						$html .= "<li class='admin_section_list_item'>\n";
						$html .= "<div class='admin_section_data'>\n";
						$user = User :: getObject($content_owner_row['user_id']);

						$html .= $user->getUserListUI();
						$name = "content_".$this->id."_owner_".$user->GetId()."_is_author";
						$html .= " Is content author? ";

						$content_owner_row['is_author'] == 't' ? $checked = 'CHECKED' : $checked = '';
						$html .= "<input type='checkbox' name='$name' $checked>\n";
						$html .= "</div>\n";
						$html .= "<div class='admin_section_tools'>\n";
						$name = "content_".$this->id."_owner_".$user->GetId()."_remove";
						$html .= "<input type='submit' name='$name' value='"._("Remove")."'>";
						$html .= "</div>\n";
						$html .= "</li>\n";
					}
				}

				$html .= "<li class='admin_section_list_item'>\n";
				$html .= "<div class='admin_section_data'>\n";
				$html .= User :: getSelectUserUI("content_{$this->id}_new_owner");
				$html .= "</div>\n";
				$html .= "<div class='admin_section_tools'>\n";
				$name = "content_{$this->id}_add_owner_submit";
				$value = _("Add owner");
				$html .= "<input type='submit' name='$name' value='$value'>";
				$html .= "</div>\n";
				$html .= "</li>\n";
				$html .= "</ul>\n";
				$html .= "</div>\n";
			}
		$html .= $subclass_admin_interface;
		$html .= "</div>\n";
		return $html;
	}
	/** Process admin interface of this object.  When an object overrides this method, they should call the parent processAdminUI at the BEGINING of processing.
	
	*/
	public function processAdminUI()
	{
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
        {
    		global $db;
    		if ($this->getObjectType() == 'Content') /* The object hasn't yet been typed */
    		{
    			$content_type = FormSelectGenerator :: getResult("content_".$this->id."_content_type", "Content");
    			$this->setContentType($content_type);
    		}
    		else
    			if ($this->is_trivial_content == false)
    			{
    				/* title */
    				if (empty ($this->content_row['title']))
    				{
    					$title = self :: processNewContentUI("title_{$this->id}_new");
    					if ($title != null)
    					{
    						$title_id = $title->GetId();
    						$db->ExecSqlUpdate("UPDATE content SET title = '$title_id' WHERE content_id = '$this->id'", FALSE);
    					}
    				}
    				else
    				{
    					$title = self :: getObject($this->content_row['title']);
    					$name = "content_".$this->id."_title_erase";
    					if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
    					{
    						$db->ExecSqlUpdate("UPDATE content SET title = NULL WHERE content_id = '$this->id'", FALSE);
    						$title->delete($errmsg);
    					}
    					else
    					{
    						$title->processAdminUI();
    					}
    				}
    
    				/* is_persistent */
    				$name = "content_".$this->id."_is_persistent";
    				!empty ($_REQUEST[$name]) ? $this->setIsPersistent(true) : $this->setIsPersistent(false);
    
    				/* description */
    				if (empty ($this->content_row['description']))
    				{
    					$description = self :: processNewContentUI("description_{$this->id}_new");
    					if ($description != null)
    					{
    						$description_id = $description->GetId();
    						$db->ExecSqlUpdate("UPDATE content SET description = '$description_id' WHERE content_id = '$this->id'", FALSE);
    					}
    				}
    				else
    				{
    					$description = self :: getObject($this->content_row['description']);
    					$name = "content_".$this->id."_description_erase";
    					if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
    					{
    						$db->ExecSqlUpdate("UPDATE content SET description = NULL WHERE content_id = '$this->id'", FALSE);
    						$description->delete($errmsg);
    					}
    					else
    					{
    						$description->processAdminUI();
    					}
    				}
    				
    				/* long description */
    				if (empty ($this->content_row['long_description']))
    				{
    					$long_description = self :: processNewContentUI("long_description_{$this->id}_new");
    					if ($long_description != null)
    					{
    						$long_description_id = $long_description->GetId();
    						$db->ExecSqlUpdate("UPDATE content SET long_description = '$long_description_id' WHERE content_id = '$this->id'", FALSE);
    					}
    				}
    				else
    				{
    					$long_description = self :: getObject($this->content_row['long_description']);
    					$name = "content_".$this->id."_long_description_erase";
    					if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
    					{
    						$db->ExecSqlUpdate("UPDATE content SET long_description = NULL WHERE content_id = '$this->id'", FALSE);
    						$long_description->delete($errmsg);
    					}
    					else
    					{
    						$long_description->processAdminUI();
    					}
    				}
    
    				/* project_info */
    				if (empty ($this->content_row['project_info']))
    				{
    					$project_info = self :: processNewContentUI("project_info_{$this->id}_new");
    					if ($project_info != null)
    					{
    						$project_info_id = $project_info->GetId();
    						$db->ExecSqlUpdate("UPDATE content SET project_info = '$project_info_id' WHERE content_id = '$this->id'", FALSE);
    					}
    				}
    				else
    				{
    					$project_info = self :: getObject($this->content_row['project_info']);
    					$name = "content_".$this->id."_project_info_erase";
    					if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
    					{
    						$db->ExecSqlUpdate("UPDATE content SET project_info = NULL WHERE content_id = '$this->id'", FALSE);
    						$project_info->delete($errmsg);
    					}
    					else
    					{
    						$project_info->processAdminUI();
    					}
    				}
    
    				/* sponsor_info */
    				if (empty ($this->content_row['sponsor_info']))
    				{
    					$sponsor_info = self :: processNewContentUI("sponsor_info_{$this->id}_new");
    					if ($sponsor_info != null)
    					{
    						$sponsor_info_id = $sponsor_info->GetId();
    						$db->ExecSqlUpdate("UPDATE content SET sponsor_info = '$sponsor_info_id' WHERE content_id = '$this->id'", FALSE);
    					}
    				}
    				else
    				{
    					$sponsor_info = self :: getObject($this->content_row['sponsor_info']);
    					$name = "content_".$this->id."_sponsor_info_erase";
    					if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true)
    					{
    						$db->ExecSqlUpdate("UPDATE content SET sponsor_info = NULL WHERE content_id = '$this->id'", FALSE);
    						$sponsor_info->delete($errmsg);
    					}
    					else
    					{
    						$sponsor_info->processAdminUI();
    					}
    				}
    				/* content_has_owners */
    				$sql = "SELECT * FROM content_has_owners WHERE content_id='$this->id'";
    				$db->ExecSql($sql, $content_owner_rows, false);
    				if ($content_owner_rows != null)
    				{
    					foreach ($content_owner_rows as $content_owner_row)
    					{
    						$user = User :: getObject($content_owner_row['user_id']);
    						$user_id = $user->getId();
    						$name = "content_".$this->id."_owner_".$user->GetId()."_remove";
    						if (!empty ($_REQUEST[$name]))
    						{
    							$this->deleteOwner($user);
    						}
    						else
    						{
    							$name = "content_".$this->id."_owner_".$user->GetId()."_is_author";
    							$content_owner_row['is_author'] == 't' ? $is_author = true : $is_author = false;
    							!empty ($_REQUEST[$name]) ? $should_be_author = true : $should_be_author = false;
    							if ($is_author != $should_be_author)
    							{
    								$should_be_author ? $is_author_sql = 'TRUE' : $is_author_sql = 'FALSE';
    								$sql = "UPDATE content_has_owners SET is_author=$is_author_sql WHERE content_id='$this->id' AND user_id='$user_id'";
    
    								if (!$db->ExecSqlUpdate($sql, false))
    								{
    									throw new Exception(_('Unable to set as author in the database.'));
    								}
    
    							}
    
    						}
    					}
    				}
    				$user = User :: processSelectUserUI("content_{$this->id}_new_owner");
    				$name = "content_{$this->id}_add_owner_submit";
    				if (!empty ($_REQUEST[$name]) && $user != null)
    				{
    					$this->addOwner($user);
    				}
    
    			}
    		$this->refresh();
        }
	}
    
    /**
     * Tell if a given user is already subscribed to this content
     * @param User the given user
     * @return boolean
     */
    public function isUserSubscribed(User $user)
    {
        global $db;
        $sql = "SELECT content_id FROM user_has_content WHERE user_id = '{$user->getId()}' AND content_id = '{$this->getId()}';";
        $db->ExecSqlUniqueRes($sql, $row, false);
        
        if($row)
            return true;
        else
            return false;
    }
    
	/** Subscribe to the project 
	 * @return true on success, false on failure */
	public function subscribe(User $user)
	{
		return $user->addContent($this);
	}
	/** Unsubscribe to the project
	 * @return true on success, false on failure */
	public function unsubscribe(User $user)
	{
		return $user->removeContent($this);
	}

	/** Persistent (or read-only) content is meant for re-use.  It will not be deleted when the delete() method is called.  When a containing element (ContentGroup, ContentGroupElement) is deleted, it calls delete on all the content it includes.  If the content is persistent, only the association will be removed.
	* @return true or false */
	public function isPersistent()
	{
		if ($this->content_row['is_persistent'] == 't')
		{
			$retval = true;
		}
		else
		{
			$retval = false;
		}
		return $retval;
	}

	/** Set if the content group is persistent
	 * @param $is_locative_content true or false
	 * */
	public function setIsPersistent($is_persistent)
	{
		if ($is_persistent != $this->isPersistent()) /* Only update database if there is an actual change */
		{
			$is_persistent ? $is_persistent_sql = 'TRUE' : $is_persistent_sql = 'FALSE';

			global $db;
			$db->ExecSqlUpdate("UPDATE content SET is_persistent = $is_persistent_sql WHERE content_id = '$this->id'", false);
			$this->refresh();
		}

	}

	/** Reloads the object from the database.  Should normally be called after a set operation */
	protected function refresh()
	{
		$this->__construct($this->id);
	}

	/** @see GenericObject
	 * @note Persistent content will not be deleted
	*/
	public function delete(& $errmsg)
	{
		$retval = false;
		if ($this->isPersistent())
		{
			$errmsg = _("Content is persistent (you must make it non persistent before you can delete it)");
		}
		else
		{
			global $db;
			if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
			{
				$sql = "DELETE FROM content WHERE content_id='$this->id'";
				$db->ExecSqlUpdate($sql, false);
				$retval = true;
			}
			else
			{
				$errmsg = _("Access denied (not owner of content)");
			}
		}
		return $retval;
	}

} // End class

/* This allows the class to enumerate it's children properly */
$class_names = Content :: getAvailableContentTypes();
foreach ($class_names as $class_name)
{
	require_once BASEPATH.'classes/Content/'.$class_name.'.php';
}
?>