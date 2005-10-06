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
/**@file FlickrPhotostream.php
 * @author Copyright (C) 2005 François Proulx <francois.proulx@gmail.com>,
 * Technologies Coeus inc.
 */
 
require_once BASEPATH.'classes/Dependencies.php';

// Make sure the Phlickr support is installed
if (Dependencies::check("Phlickr", $errmsg))
{
	require_once BASEPATH.'classes/Content.php';
	require_once BASEPATH.'classes/FormSelectGenerator.php';

	// Phlickr classes
	require_once "Phlickr/Api.php";
	require_once "Phlickr/User.php";
	require_once "Phlickr/Group.php";

	/**
	 * A Flickr Photostreams wrapper
	 * 	- Flexible administrative options
	 */
	class FlickrPhotostream extends Content
	{
		/* Photo selection modes */
		const SELECT_BY_GROUP = 'PSM_GROUP';
		const SELECT_BY_USER = 'PSM_USER';
		const SELECT_BY_TAGS = 'PSM_TAGS';

		/* Tags matching mode */
		const TAG_MODE_ANY = 'ANY_TAG';
		const TAG_MODE_ALL = 'ALL_TAGS';

		/* Sizes */
		const SIZE_SQUARED_75x75 = 's';
		const SIZE_THUMB_100x75 = 't';
		const SIZE_SMALL_240x180 = 'm';
		const SIZE_MEDIUM_500x375 = '';
		const SIZE_ORIGINAL = 'o';

		const GRID_X = 3;
		const GRID_Y = 3;

		/* 15 minutes cache age SHOULD ADD cron tab */
		const MAX_CACHE_AGE = 600;

		/* Private Phlickr objects */
		private $flickr_api;

		protected function __construct($content_id)
		{
			parent :: __construct($content_id);
			global $db;
			$content_id = $db->EscapeString($content_id);

			$sql = "SELECT *, EXTRACT(EPOCH FROM AGE(NOW(), cache_update_timestamp)) as cache_age FROM flickr_photostream WHERE flickr_photostream_id='$content_id'";
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				/*Since the parent Content exists, the necessary data in content_group had not yet been created */
				$sql = "INSERT INTO flickr_photostream (flickr_photostream_id, preferred_size) VALUES ('$content_id', '".self :: SIZE_SMALL_240x180."')";
				$db->ExecSqlUpdate($sql, false);

				$sql = "SELECT * FROM flickr_photostream WHERE flickr_photostream_id='$content_id'";
				$db->ExecSqlUniqueRes($sql, $row, false);
				if ($row == null)
				{
					throw new Exception(_("The content with the following id could not be found in the database: ").$content_id);
				}

			}

			$this->flickr_photostream_row = $row;
			$this->flickr_api = null;
			$this->mBd = & $db;
		}

		private function loadCacheFromDatabase()
		{
			//echo "<h2>DEBUG :: Cache Age :: {$this->flickr_photostream_row['cache_age']}</h2>";
			if (!is_null($this->flickr_photostream_row['requests_cache']) && !is_null($this->flickr_photostream_row['cache_age']) && ($this->flickr_photostream_row['cache_age'] < self :: MAX_CACHE_AGE))
			{
				//echo "<h2>DEBUG :: Loading Flickr cache from database</h2>";
				$obj = unserialize($this->mBd->UnescapeBinaryString($this->flickr_photostream_row['requests_cache']));
				$this->getFlickrApi()->setCache($obj);
			}
		}

		private function writeCacheToDatabase($force_overwrite = false)
		{
			//echo "<h2>DEBUG :: Writing cache to database</h2>";
			$api = $this->getFlickrApi();
			if ($api)
			{
				$new_cache = serialize($api->getCache());
				$old_cache = $this->mBd->UnescapeBinaryString($this->flickr_photostream_row['requests_cache']);
				$age = is_null($this->flickr_photostream_row['cache_age']) ? self :: MAX_CACHE_AGE : $this->flickr_photostream_row['cache_age'];
				if ($force_overwrite === true || ($age >= self :: MAX_CACHE_AGE) || ($new_cache !== $old_cache))
					$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET cache_update_timestamp = NOW(), requests_cache = '".$this->mBd->EscapeBinaryString($new_cache)."' WHERE flickr_photostream_id = '".$this->getId()."'", false);
			}
		}

		private function getFlickrApi()
		{
			if ($this->getApiKey() && $this->flickr_api == null)
				$this->flickr_api = new Phlickr_Api($this->getApiKey(), $this->getApiSharedSecret());
			return $this->flickr_api;
		}

		private function setFlickrApi($api)
		{
			$this->flickr_api = $api;
		}

		public function getSelectionMode()
		{
			return $this->flickr_photostream_row['photo_selection_mode'];
		}

		public function setSelectionMode($selection_mode)
		{
			switch ($selection_mode)
			{
				case self :: SELECT_BY_GROUP :
				case self :: SELECT_BY_USER :
				case self :: SELECT_BY_TAGS :
					$selection_mode = $this->mBd->EscapeString($selection_mode);
					$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET photo_selection_mode = '".$selection_mode."' WHERE flickr_photostream_id = '".$this->getId()."'");
					$this->refresh();
					break;
				default :
					throw new Exception(_("Illegal Flickr Photostream selection mode."));
			}
		}

		public function getPhotoBatchSize()
		{
			return $this->flickr_photostream_row['photo_batch_size'];
		}

		public function setPhotoBatchSize($size)
		{
			//TODO: Add photo batch size support in getAdminUI()
			if (is_numeric($size))
			{
				$size = $this->mBd->EscapeString($size);
				$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET photo_batch_size ='$size' WHERE flickr_photostream_id = '".$this->getId()."'");
				$this->refresh();
				return true;
			}
			else
				return false;
		}

		public function getApiKey()
		{
			return $this->flickr_photostream_row['api_key'];
		}
		
		public function getApiSharedSecret()
		{
			return $this->flickr_photostream_row['api_shared_secret'];
		}
		
		public function setApiSharedSecret($api_shared_secret)
		{
			$api_shared_secret = $this->mBd->EscapeString($api_shared_secret);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET api_shared_secret ='$api_shared_secret' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
			$this->setFlickrApi(null);
		}

		public function setApiKey($api_key)
		{
			$api_key = $this->mBd->EscapeString($api_key);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET api_key ='$api_key' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
			$this->setFlickrApi(null);
		}

		public function pingFlickr()
		{
			if ($this->getFlickrApi())
			{
				try
				{
					$request = $$this->getFlickrApi()->createRequest("flickr.test.echo", null);
					$request->setExceptionThrownOnFailure(true);
					$resp = $request->execute();
					return true;
				}
				catch (Phlickr_Exception $ex)
				{
					return false;
				}
			}
			else
				return false;
		}

		private function getUserByEmail($email)
		{
			if ($this->getFlickrApi())
			{
				try
				{
					$request = $this->getFlickrApi()->createRequest("flickr.people.findByEmail", array ("find_email" => $email));
					$request->setExceptionThrownOnFailure(true);
					$resp = $request->execute();
					return new Phlickr_User($this->getFlickrApi(), (string) $resp->xml->user['id']);
				}
				catch (Phlickr_Exception $ex)
				{
					return null;
				}
			}
			else
				return null;
		}

		public function getFlickrUserId()
		{
			return $this->flickr_photostream_row['user_id'];
		}

		public function setUserId($user_id)
		{
			$user_id = $this->mBd->EscapeString($user_id);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET user_id ='$user_id' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function getUserName()
		{
			return $this->flickr_photostream_row['user_name'];
		}

		public function setUserName($user_name)
		{
			$user_name = $this->mBd->EscapeString($user_name);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET user_name = '$user_name' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function getGroupId()
		{
			return $this->flickr_photostream_row['group_id'];
		}

		public function setGroupId($group_id)
		{
			$group_id = $this->mBd->EscapeString($group_id);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET group_id = '$group_id' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function getTags()
		{
			return $this->flickr_photostream_row['tags'];
		}

		public function setTags($tags)
		{
			$tags = $this->mBd->EscapeString($tags);
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET tags = '$tags' WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function getTagMode()
		{
			return $this->flickr_photostream_row['tag_mode'];
		}

		public function setTagMode($mode)
		{
			switch ($mode)
			{
				case self :: TAG_MODE_ANY :
				case self :: TAG_MODE_ALL :
					$mode = $this->mBd->EscapeString($mode);
					$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET tag_mode = '$mode' WHERE flickr_photostream_id = '".$this->getId()."'");
					$this->refresh();
					break;
				default :
					throw new Exception("Illegal tag matching mode.");
			}
		}

		public function getPreferredSize()
		{
			return $this->flickr_photostream_row['preferred_size'];
		}

		public function setPreferredSize($size)
		{
			switch ($size)
			{
				case self :: SIZE_SQUARED_75x75 :
				case self :: SIZE_THUMB_100x75 :
				case self :: SIZE_SMALL_240x180 :
				case self :: SIZE_MEDIUM_500x375 :
				case self :: SIZE_ORIGINAL :
					$size = $this->mBd->EscapeString($size);
					$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET preferred_size = '$size' WHERE flickr_photostream_id = '".$this->getId()."'");
					$this->refresh();
					break;
				default :
					throw new Exception("Illegal size.");
			}
		}

		public function shouldDisplayTitle()
		{
			return $this->flickr_photostream_row['display_title'] == "t";
		}

		public function setDisplayTitle($display_title)
		{
			$display_title = $display_title == true ? "true" : "false";
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET display_title = $display_title WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function shouldDisplayTags()
		{
			return $this->flickr_photostream_row['display_tags'] == "t";
		}

		public function setDisplayTags($display_tags)
		{
			$display_tags = $display_tags == true ? "true" : "false";
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET display_tags = $display_tags WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function shouldDisplayDescription()
		{
			return $this->flickr_photostream_row['display_description'] == "t";
		}

		public function setDisplayDescription($display_description)
		{
			$display_description = $display_description == true ? "true" : "false";
			$this->mBd->ExecSqlUpdate("UPDATE flickr_photostream SET display_description = $display_description WHERE flickr_photostream_id = '".$this->getId()."'");
			$this->refresh();
		}

		public function getAdminUI($subclass_admin_interface = null)
		{
			$generator = new FormSelectGenerator();

			$html = '';
			$html .= "<div class='admin_class'>Flickr Photostream (".get_class($this)." instance)</div>\n";

			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Flickr API key")." <a href='http://www.flickr.com/services/api/misc.api_keys.html'>(?)</a> : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$name = "flickr_photostream_".$this->id."_api_key";
			$html .= "<input type='text' name='$name' value='".$this->getApiKey()."'\n";
			$html .= "</div>\n";
			$html .= "</div>\n";
			
			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Shared secret")." : </div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$name = "flickr_photostream_".$this->id."_api_shared_secret";
			$html .= "<input type='text' name='$name' value='".$this->getApiSharedSecret()."'\n";
			$html .= "</div>\n";
			$html .= "</div>\n";

			$html .= "<div class='admin_section_container'>\n";
			$html .= "<div class='admin_section_title'>"._("Flick photo selection mode :")."</div>\n";
			$html .= "<div class='admin_section_data'>\n";

			$selection_modes = array (array (0 => self :: SELECT_BY_GROUP, 1 => _("Select by group")), array (0 => self :: SELECT_BY_TAGS, 1 => _("Select by tags")), array (0 => self :: SELECT_BY_USER, 1 => _("Select by user")));
			$html .= $generator->generateFromArray($selection_modes, $this->getSelectionMode(), "SelectionMode".$this->getID(), "FlickrPhotostream", false, null, "onChange='submit()'");

			// Check for existing API key
			if ($this->getAPIKey())
			{
				try
				{
					switch ($this->getSelectionMode())
					{
						// Process common data ( User ID + User name )
						case self :: SELECT_BY_GROUP :
						case self :: SELECT_BY_USER :
							if ($this->getFlickrUserId())
							{
								$html .= "<div class='admin_section_container'>\n";
								$html .= "<div class='admin_section_title'>"._("Flickr User ID + Username")." : </div>\n";
								$html .= "<div class='admin_section_data'>\n";
								$html .= $this->getUserName()." [".$this->getFlickrUserId()."]";
								$name = "flickr_photostream_".$this->id."_reset_user_id";
								$html .= " <b>( <input type='checkbox' name='$name' value='true'>"._("Reset Flickr User ID")." )</b>";
								$html .= "</div>\n";
								$html .= "</div>\n";
							}
							else
							{
								$html .= "<div class='admin_section_container'>\n";
								$html .= "<div class='admin_section_title'>"._("Flickr User E-mail")." : </div>\n";
								$html .= "<div class='admin_section_data'>\n";
								$name = "flickr_photostream_".$this->id."_email";
								$html .= "<input type='text' name='$name' value=''>";
								$html .= "</div>\n";
								$html .= "</div>\n";
							}
							break;
					}

					switch ($this->getSelectionMode())
					{
						case self :: SELECT_BY_GROUP :
							if ($this->getFlickrUserId())
							{
								$html .= "<div class='admin_section_container'>\n";
								$html .= "<div class='admin_section_title'>"._("Group Photo Pool")." : </div>\n";
								$html .= "<div class='admin_section_data'>\n";
								$group_photo_pools = array ();

								$flickr_user = new Phlickr_User($this->getFlickrApi(), $this->getFlickrUserId());
								$groups = array ();
								$group_photo_pools = $flickr_user->getGroupList()->getGroups();
								foreach ($group_photo_pools as $group_photo_pool)
									$groups[] = array (0 => $group_photo_pool->getId(), 1 => $group_photo_pool->getName());

								if (count($groups) > 0)
									$html .= $generator->generateFromArray($groups, $this->getGroupId(), "GroupPhotoPool".$this->getID(), "FlickrPhotostream", false, null, "onChange='submit()'");
								else
									$html .= _("Could not find any group photo pool.");

								$html .= "</div>\n";
								$html .= "</div>\n";
							}
							break;
						case self :: SELECT_BY_TAGS :
							$html .= "<div class='admin_section_container'>\n";
							$html .= "<div class='admin_section_title'>"._("Tags (comma-separated)")." : </div>\n";
							$html .= "<div class='admin_section_data'>\n";
							$name = "flickr_photostream_".$this->id."_tags";
							$html .= "<input type='text' name='$name' value='".$this->getTags()."'>";
							$tag_modes = array (array (0 => self :: TAG_MODE_ANY, 1 => _("Match any tag")), array (0 => self :: TAG_MODE_ALL, 1 => _("Match all tags")));
							$html .= $generator->generateFromArray($tag_modes, $this->getTagMode(), "TagMode".$this->getID(), "FlickrPhotostream", false, null, "onChange='submit()'");
							$html .= "</div>\n";
							$html .= "</div>\n";
							break;
					}

					$html .= "<div class='admin_section_container'>\n";
					$html .= "<div class='admin_section_title'>"._("Flickr photo display options")." : </div>\n";
					$html .= "<div class='admin_section_data'>\n";

					$html .= "<div class='admin_section_container'>\n";
					$html .= "<div class='admin_section_title'>"._("Show Flickr photo title ?")." : </div>\n";
					$html .= "<div class='admin_section_data'>\n";
					$name = "flickr_photostream_".$this->id."_display_title";
					$this->shouldDisplayTitle() ? $checked = 'CHECKED' : $checked = '';
					$html .= "<input type='checkbox' name='$name' $checked>\n";
					$html .= "</div>\n";
					$html .= "</div>\n";

					$html .= "<div class='admin_section_container'>\n";
					$html .= "<div class='admin_section_title'>"._("Show Flickr tags ?")." : </div>\n";
					$html .= "<div class='admin_section_data'>\n";
					$name = "flickr_photostream_".$this->id."_display_tags";
					$this->shouldDisplayTags() ? $checked = 'CHECKED' : $checked = '';
					$html .= "<input type='checkbox' name='$name' $checked>\n";
					$html .= "</div>\n";
					$html .= "</div>\n";

					$html .= "<div class='admin_section_container'>\n";
					$html .= "<div class='admin_section_title'>"._("Show Flickr photo description ?")." : </div>\n";
					$html .= "<div class='admin_section_data'>\n";
					$name = "flickr_photostream_".$this->id."_display_description";
					$this->shouldDisplayDescription() ? $checked = 'CHECKED' : $checked = '';
					$html .= "<input type='checkbox' name='$name' $checked>\n";
					$html .= "</div>\n";
					$html .= "</div>\n";

					$html .= "<div class='admin_section_container'>\n";
					$html .= "<div class='admin_section_title'>"._("Preferred size")." : </div>\n";
					$html .= "<div class='admin_section_data'>\n";
					$preferred_sizes = array (array (0 => self :: SIZE_SQUARED_75x75, 1 => _("Squared 75x75")), array (0 => self :: SIZE_THUMB_100x75, 1 => _("Thumbnail 100x75")), array (0 => self :: SIZE_SMALL_240x180, 1 => _("Small 240x180")), array (0 => self :: SIZE_MEDIUM_500x375, 1 => _("Medium 500x375")), array (0 => self :: SIZE_ORIGINAL, 1 => _("Original size")));
					$html .= $generator->generateFromArray($preferred_sizes, $this->getPreferredSize(), "PreferredSize".$this->getID(), "FlickrPhotostream", false, null, "onChange='submit()'");
					$html .= "</div>\n";
					$html .= "</div>\n";

					//TODO: Add photo batch size support
					//TODO: Add photo count support ( number of photos to display at once )
					//TODO: Add random support (checkbox)
					//TODO: Add date range support

					$html .= "</div>\n";
					$html .= "</div>\n";
				}
				catch (Phlickr_ConnectionException $e)
				{
					$html .= _("Unable to connect to Flickr API.");
					echo $e;
				}
				catch (Phlickr_MethodFailureException $e)
				{
					$html .= _("Some of the request parameters provided to Flickr API are invalid.");
				}
				catch (Phlickr_XmlParseException $e)
				{
					$html .= _("Unable to parse Flickr's response.");
				}
				catch (Phlickr_Exception $e)
				{
					$html .= _("Could not get content from Flickr : ").$e;
				}
			}
			else
			{
				$html .= "<div class='admin_section_container'>\n";
				$html .= "<div class='admin_section_title'>"._("YOU MUST SPECIFY AN API KEY BEFORE YOU CAN GO ON.")."</div>\n";
				$html .= "</div>\n";
			}

			$html .= $subclass_admin_interface;
			return parent :: getAdminUI($html);
		}

		function processAdminUI()
		{
			if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
			{
				parent :: processAdminUI();
				$generator = new FormSelectGenerator();

				$name = "flickr_photostream_".$this->id."_api_key";
				!empty ($_REQUEST[$name]) ? $this->setApiKey($_REQUEST[$name]) : $this->setApiKey(null);
				
				$name = "flickr_photostream_".$this->id."_api_shared_secret";
				!empty ($_REQUEST[$name]) ? $this->setApiSharedSecret($_REQUEST[$name]) : $this->setApiSharedSecret(null);
				
				if ($generator->isPresent("SelectionMode".$this->getID(), "FlickrPhotostream"))
					$this->setSelectionMode($generator->getResult("SelectionMode".$this->getID(), "FlickrPhotostream"));

				// Check for existing API key
				if ($this->getAPIKey() && $this->getSelectionMode())
				{
					try
					{
						switch ($this->getSelectionMode())
						{
							// Process common data for groups and users
							case self :: SELECT_BY_GROUP :
								if ($generator->isPresent("GroupPhotoPool".$this->getID(), "FlickrPhotostream"))
									$this->setGroupId($generator->getResult("GroupPhotoPool".$this->getID(), "FlickrPhotostream"));
							case self :: SELECT_BY_USER :
								$name = "flickr_photostream_".$this->id."_reset_user_id";
								if (!empty ($_REQUEST[$name]) || !$this->getFlickrUserId())
								{
									$this->setUserId(null);
									$name = "flickr_photostream_".$this->id."_email";
									if (!empty ($_REQUEST[$name]) && ($flickr_user = $this->getUserByEmail($_REQUEST[$name])) != null)
									{
										$this->setUserId($flickr_user->getId());
										$this->setUserName($flickr_user->getName());
									}
									else
										echo _("Could not find a Flickr user with this e-mail.");
								}
								break;
							case self :: SELECT_BY_TAGS :
								$name = "flickr_photostream_".$this->id."_tags";
								if (!empty ($_REQUEST[$name]))
									$this->setTags($_REQUEST[$name]);
								else
									$this->setTags(null);
								if ($generator->isPresent("TagMode".$this->getID(), "FlickrPhotostream"))
									$this->setTagMode($generator->getResult("TagMode".$this->getID(), "FlickrPhotostream"));
								break;
						}
					}
					catch (Exception $e)
					{
						echo _("Could not complete successfully the saving procedure.");
					}

					$name = "flickr_photostream_".$this->id."_display_title";
					!empty ($_REQUEST[$name]) ? $this->setDisplayTitle(true) : $this->setDisplayTitle(false);
					$name = "flickr_photostream_".$this->id."_display_tags";
					!empty ($_REQUEST[$name]) ? $this->setDisplayTags(true) : $this->setDisplayTags(false);
					$name = "flickr_photostream_".$this->id."_display_description";
					!empty ($_REQUEST[$name]) ? $this->setDisplayDescription(true) : $this->setDisplayDescription(false);

					if ($generator->isPresent("PreferredSize".$this->getID(), "FlickrPhotostream"))
						$this->setPreferredSize($generator->getResult("PreferredSize".$this->getID(), "FlickrPhotostream"));
				}
			}
		}

		/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
		* @param $subclass_admin_interface Html content of the interface element of a children
		* @return The HTML fragment for this interface */
		public function getUserUI($subclass_user_interface = null, $force_data_refresh = false)
		{
			$html = '';
			$html .= "<div class='user_ui_container'>\n";
			$html .= "<div class='user_ui_object_class'>FlickrPhotostream (".get_class($this)." instance)</div>\n";

			$api = $this->getFlickrApi();
			if (!is_null($api))
			{
				// Load cache only on the User UI side
				if ($force_data_refresh === false)
					$this->loadCacheFromDatabase();

				try
				{
					$photos = null;
					switch ($this->getSelectionMode())
					{
						case self :: SELECT_BY_GROUP :
							if ($this->getGroupId())
							{
								$photo_pool = new Phlickr_Group($api, $this->getGroupId());
								$photos = $photo_pool->getPhotoList($this->getPhotoBatchSize())->getPhotos();
							}
							break;
						case self :: SELECT_BY_TAGS :
							if ($this->getTags())
							{
								$request = $api->createRequest('flickr.photos.search', array ('tags' => $this->getTags(), 'tag_mode' => $this->getTagMode()));
								$photo_list = new Phlickr_PhotoList($request, $this->getPhotoBatchSize());
								$photos = $photo_list->getPhotos();
							}
							break;
						case self :: SELECT_BY_USER :
							if ($this->getFlickrUserId())
							{
								$user = new Phlickr_User($api, $this->getFlickrUserId());
								$photos = $user->getPhotoList($this->getPhotoBatchSize())->getPhotos();
							}
							break;
					}

					if (is_array($photos) && !empty ($photos))
					{
						$size = $this->getPreferredSize();
						if (empty ($size))
							$size = null;

						// Preload authors ( this will be cached )
						foreach ($photos as $cache_authors)
							$author = new Phlickr_User($api, $cache_authors->getUserId());

						// If there's enough photo show a grid
						if (count($photos) >= self :: GRID_X * self :: GRID_Y)
						{
							$grid_photos_idx = array ();
							$html .= "<table>\n";
							for ($i = 0; $i < self :: GRID_X; $i ++)
							{
								$html .= "<tr>\n";
								for ($j = 0; $j < self :: GRID_Y; $j ++)
								{
									// Get random values for each grid positions (assert no double)
									$idx = mt_rand(0, count($photos) - 1);
									while (in_array($idx, $grid_photos_idx))
										$idx = mt_rand(0, count($photos) - 1);
									$grid_photos_idx[] = $idx;
									$photo = $photos[$idx];
									if (is_object($photo))
									{
										$author = new Phlickr_User($api, $photo->getUserId());
										$html .= '<td><div class="flickr_photo"><a href="'.$photo->buildUrl().'"><img title="['.$author->getName()."] ".$photo->getTitle().'" src="'.$photo->buildImgUrl($size).'"></a></div></td>'."\n";
									}
								}
								$html .= "</tr>\n";
							}
							$html .= "</table>\n";
						}
						else
						{
							// Get a photo at random
							$photo = $photos[mt_rand(0, count($photos) - 1)];
							if (is_object($photo))
							{
								$html .= '<div class="flickr_photo_block">'."\n";
								if ($this->shouldDisplayTitle())
								{
									$title = $photo->getTitle();
									if (!empty ($title))
									{
										$html .= '<div class="flickr_title"><h3>'.$photo->getTitle().'</h3></div>'."\n";
									}
								}

								$html .= '<div class="flickr_photo"><a href="'.$photo->buildUrl().'"><img src="'.$photo->buildImgUrl($size).'"></a></div>'."\n";

								if ($this->shouldDisplayTags())
								{
									$tags = $photo->getTags();
									if (!empty ($tags))
									{
										$html .= '<div class="flickr_tags">'."\n";
										$html .= '<h3>'._("Tags")."</h3>\n";
										$html .= '<ul>'."\n";
										foreach ($tags as $tag)
										{
											$url_encoded_tag = urlencode($tag);
											$html .= '<li><a href="http://www.flickr.com/photos/tags/'.$url_encoded_tag.'/">'.$tag.'</a></li>'."\n";
										}
										$html .= '</ul>'."\n";
										$html .= '</div>'."\n";
									}
								}
								if ($this->shouldDisplayDescription())
								{
									$description = $photo->getDescription();
									if (!empty ($description))
										$html .= '<div class="flickr_description">'.$description.'</div>'."\n";
								}

								foreach ($photos as $cache_authors)
									$author = new Phlickr_User($api, $cache_authors->getUserId());

								$author = new Phlickr_User($api, $photo->getOwnerId());
								$html .= '<div class="flickr_description"><a href="'.$author->buildUrl().'">'.$author->getName().'</a></div>'."\n";
								$html .= '</div>'."\n";
							}
						}
					}
					else
					{
						$html .= _("No Flickr content matches the request !");
					}
				}
				catch (Phlickr_ConnectionException $e)
				{
					$html .= _("Unable to connect to Flickr API.");
				}
				catch (Phlickr_MethodFailureException $e)
				{
					$html .= _("Some of the request parameters provided to Flickr API are invalid.");
					$html .= "<br>".$e->getMessage();
				}
				catch (Phlickr_XmlParseException $e)
				{
					$html .= _("Unable to parse Flickr's response.");
				}
				catch (Phlickr_Exception $e)
				{
					$html .= _("Could not get content from Flickr : ").$e;
				}

				// Overwrite cache if needed
				$this->writeCacheToDatabase($force_data_refresh);
			}

			$html .= $subclass_user_interface;
			$html .= "</div>\n";
			return parent :: getUserUI($html);
		}

	/** Reloads the object from the database.  Should normally be called after a set operation.
	 * This function is private because calling it from a subclass will call the
	 * constructor from the wrong scope */
	private function refresh()
	{
		$this->__construct($this->id);
	}
	
		/** Delete this Content from the database */
		public function delete(& $errmsg)
		{
			$user = User :: getCurrentUser();
			if (!$this->isOwner($user) || !$user->isSuperAdmin())
			{
				$errmsg = _('Access denied!');
			}

			if ($this->isPersistent() == false)
			{
				$this->mBd->ExecSqlUpdate("DELETE FROM flickr_photostream WHERE flickr_photostream_id = '".$this->getId()."'", false);
			}
			return parent :: delete($errmsg);
		}

	} // End class
}
else
{
	class FlickrPhotostream extends Content
	{
		protected function __construct($content_id)
		{
			parent :: __construct($content_id);
		}
		public function getAdminUI($subclass_admin_interface = null)
		{
			$html = '';
			$html .= "<div class='admin_class'>Flickr Photostream (".get_class($this)." instance)</div>\n";
			$html .= _("PEAR::Phlickr is not installed");
			
			return parent :: getAdminUI($html);
		}

	}
}
?>