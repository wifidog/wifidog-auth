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
/**@file RssAggregator.php
 * @author Copyright (C) 2005 Benoit Grégoire, Technologies Coeus inc.
*/

require_once BASEPATH.'classes/FormSelectGenerator.php';
require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/LocaleList.php';
require_once BASEPATH.'classes/Locale.php';
define('MAGPIE_CACHE_DIR', BASEPATH.'tmp/magpie_cache/');

error_reporting(E_ALL);

/** Interim code to display the RSS feed for a hotspot
 */
class RssAggregator extends Content
{
	private $content_rss_aggregator_row;
	private $content_rss_aggregator_feeds_rows;
	private $press_review;
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;
		$content_id = $db->EscapeString($content_id);

		$sql = "SELECT *, EXTRACT(EPOCH FROM max_item_age) as max_item_age_seconds FROM content_rss_aggregator WHERE content_id='$content_id'";
		$row = null;
		$db->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			/*Since the parent Content exists, the necessary data in content_group had not yet been created */
			$sql_new = "INSERT INTO content_rss_aggregator (content_id) VALUES ('$content_id')";
			$db->ExecSqlUpdate($sql_new, false);
			$db->ExecSqlUniqueRes($sql, $row, false);
			if ($row == null)
			{
				throw new Exception(_("The RssAggregator content with the following id could not be found in the database: ").$content_id);
			}
		}
		$this->content_rss_aggregator_row = $row;

		$sql = "SELECT * FROM content_rss_aggregator_feeds WHERE content_id='$content_id'";
		$content_rss_aggregator_rows = null;
		$db->ExecSql($sql, $content_rss_aggregator_rows, false);
		if ($content_rss_aggregator_rows != null)
		{
			$this->content_rss_aggregator_feeds_rows = $content_rss_aggregator_rows;
		}
		else
		{
			$this->content_rss_aggregator_feeds_rows = array ();
		}

		if (RSS_SUPPORT)
		{
			require_once BASEPATH.'lib/RssPressReview/RssPressReview.php';
			$this->press_review = new RssPressReview(BASEPATH.MAGPIE_REL_PATH, "UTF-8");
			$this->press_review->setAlgorithmStrength($this->content_rss_aggregator_row['algorithm_strength']);
			$this->press_review->setMaxItemAge($this->content_rss_aggregator_row['max_item_age']);
			foreach ($this->content_rss_aggregator_feeds_rows as $feed_row)
			{
				$this->press_review->addSourceFeed($feed_row['url'], $feed_row['default_publication_interval'], $feed_row['bias']);
				$title = $this->press_review->getFeedTitle($feed_row['url']);
				/* Update the stored feed title if it changed.  
				 * This allows the system to know every feed's title without continuously looking them up 
				 */
				 if(!empty($title) && $title!=$feed_row['title'])
				{
						$title = $db->EscapeString($title);
						$url = $db->EscapeString($feed_row['url']);
						$db->ExecSqlUpdate("UPDATE content_rss_aggregator_feeds SET title = '$title' WHERE url='$url'", false);
						$this->refresh();
				}
			}
		}
		else
		{
			$html = _("RSS support is disabled");
		}

		$this->setIsTrivialContent(false);
	}

	/** "Total number of items to display (from all feeds)
	* @return integer */
	public function getDisplayNumItems()
	{
		return $this->content_rss_aggregator_row['number_of_display_items'];
	}

	/**
	* @param $num_items Total number of items to display (from all feeds).
	* @return true if successfull
	* */
	public function setDisplayNumItems($num_items, & $errormsg = null)
	{
		$retval = false;
		if (($num_items >= 1) && $num_items != $this->getDisplayNumItems()) /* Only update database if the mode is valid and there is an actual change */
		{
			global $db;
			$num_items = $db->EscapeString($num_items);
			$db->ExecSqlUpdate("UPDATE content_rss_aggregator SET number_of_display_items = $num_items WHERE content_id = '$this->id'", false);
			$this->refresh();
			$retval = true;
		}
		elseif ($num_items < 1)
		{
			$errormsg = _("You must display at least one element");
			$retval = false;
		}
		else
		{
			/* Successfull, but nothing modified */
			$retval = true;
		}
		return $retval;
	}

	/** How much bonus feeds that do not publish as often get over feed that publish more often.
	* @return integer */
	public function getAlgorithmStrength()
	{
		return $this->content_rss_aggregator_row['algorithm_strength'];
	}

	/**How much bonus feeds that do not publish as often get over feed that publish more often.
	* @param $strength	  	The default is 0.75, with a typical range between 0 and 1.  
	 	At 0, you have a classic RSS aggregator, meaning the n most recent entries picked from all feeds
	 	will be displayed. 1 is usually as high as you'll want to go:  Assuming that all 
	 	an homogenous internal distribution (ex:  one feed publishes exactly one entry a day, the 
	 	second once every two days, and the third once every three days), and you ask for 15 entries,
	 	there will be 5 of each.  While that may not sound usefull, it still is, as the feed's distribution is 
	 	usually not homogenous.
	* @return true if successfull
	* */
	public function setAlgorithmStrength($strength, & $errormsg = null)
	{
		$retval = false;
		if ($strength != $this->getAlgorithmStrength()) /* Only update database if the mode is valid and there is an actual change */
		{
			global $db;
			$strength = $db->EscapeString($strength);
			$db->ExecSqlUpdate("UPDATE content_rss_aggregator SET algorithm_strength = '$strength' WHERE content_id = '$this->id'", false);
			$this->refresh();
			$retval = true;
		}
		else
		{
			/* Successfull, but nothing modified */
			$retval = true;
		}
		return $retval;
	}

	/** The maximum age of the items displayed
	* @return integer or null*/
	public function getMaxItemAge()
	{
		$retval = $this->content_rss_aggregator_row['max_item_age_seconds'];
		if (empty ($retval))
		{
			$retval = null;
		}
		return $retval;
	}

	/**Set the oldest entries (in seconds) you are willing to see.  Any entries older than this will not
	 	be considered at all for display, even if it means that the configured number of items to be displayed isn't reached.
		It's only usefull if all your feeds publish very rarely, and you don't want very old entries to show up.
	* @param $max_item_age	null or max age in seconds
	* @return true if successfull
	* */
	public function setMaxItemAge($max_item_age, & $errormsg = null)
	{
		$retval = false;
		if (empty ($max_item_age))
		{
			$max_item_age = null;
		}
		if (($max_item_age == null || is_numeric($max_item_age) && ($max_item_age > 0)) && $max_item_age != $this->getMaxItemAge()) /* Only update database if the mode is valid and there is an actual change */
		{
			global $db;
			if ($max_item_age == null)
			{
				$max_item_age = 'NULL';
			}
			$max_item_age = $db->EscapeString($max_item_age);
			$db->ExecSqlUpdate("UPDATE content_rss_aggregator SET max_item_age = '$max_item_age seconds' WHERE content_id = '$this->id'", false);
			$this->refresh();
			$retval = true;
		}
		elseif ($max_item_age <= 0)
		{
			$errormsg = _("The maximum age must be a positive integer or null");
			$retval = false;
		}
		else
		{
			/* Successfull, but nothing modified */
			$retval = true;
		}
		return $retval;
	}

	/** Add a new feed to the aggregator
	 * @param $url feed's url 
	 * @return true on success, false on failure **/
	public function addFeed($url)
	{
		global $db;
		$retval = false;
		if (!empty ($url))
		{
			$url = $db->EscapeString($url);
			$sql = "INSERT INTO content_rss_aggregator_feeds (content_id, url) VALUES ('{$this->id}', '$url') ";
			$content_rss_aggregator_rows = null;
			$retval = $db->ExecSqlUpdate($sql, false);
			$this->refresh();
		}
		return $retval;
	}

	/** Remove a feed from the aggregator
	* @param $url feed's url 
	* @return true on success, false on failure **/
	public function removeFeed($url)
	{
		global $db;
		$retval = false;
		if (!empty ($url))
		{
			$url = $db->EscapeString($url);
			$sql = "DELETE FROM content_rss_aggregator_feeds WHERE content_id='{$this->id}' AND url = '$url' ";
			$content_rss_aggregator_rows = null;
			$retval = $db->ExecSqlUpdate($sql, false);
			$this->refresh();
		}
		return $retval;
	}
	public function getAdminUI($subclass_admin_interface = null)
	{
		$html = '';
		$html .= "<div class='admin_class'>ContentGroup (".get_class($this)." instance)</div>\n";

		/* number_of_display_items */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Total number of items to display (from all feeds)").": </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "rss_aggregator_".$this->id."_display_num_items";
		$value = $this->getDisplayNumItems();
		$html .= "<input type='text' size='2' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* algorithm_strength */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>\n";
		$html .= _("How much bonus feeds that do not publish as often get over feed that publish more often.
					  	The default is 0.75, with a typical range between 0 and 1.  
					 	At 0, you have a classic RSS aggregator, meaning the n most recent entries picked from all feeds
					 	will be displayed. 1 is usually as high as you'll want to go:  Assuming that all 
					 	an homogenous internal distribution (ex:  one feed publishes exactly one entry a day, the 
					 	second once every two days, and the third once every three days), and you ask for 15 entries,
					 	there will be 5 of each.  While that may not sound usefull, it still is, as the feed's distribution is 
					 	usually not homogenous.");
		$html .= ": </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "rss_aggregator_".$this->id."_algorithm_strength";
		$value = $this->getAlgorithmStrength();
		$html .= "<input type='text' size='2' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* max_item_age */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>\n";
		$html .= _("Set the oldest entries (in seconds) you are willing to see.  Any entries older than this will not
					 	be considered at all for display, even if it means that the configured number of items to be displayed isn't reached.
						It's only usefull if
					 	all your feed publish very rarely, and you don't want very old entries to show up.");
		$html .= ": </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "rss_aggregator_".$this->id."_max_item_age";
		$value = $this->getMaxItemAge();
		$html .= "<input type='text' size='10' value='$value' name='$name'>\n";
		$html .= _("seconds");
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* rss_aggregator_element (table)*/
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("Feeds:")."</div>\n";

		$html .= "<ul class='admin_section_list'>\n";
		foreach ($this->content_rss_aggregator_feeds_rows as $feed_row)
		{
			$html .= "<li class='admin_section_list_item'>\n";

			$html .= "<div class='admin_section_data'>\n";
			$html .= $this->getFeedAdminUI($feed_row);
			$html .= "</div'>\n";
			$html .= "<div class='admin_section_tools'>\n";
			/* Delete feeds */
			$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_delete";
			$html .= "<input type='submit' name='$name' value='"._("Delete")."'>";
			$html .= "</div>\n";
			$html .= "</li>\n";
		}

		/* Add new feed */
		$html .= "<li class='admin_section_list_item'>\n";
		$html .= "<b>"._("Add a new feed or pick one from the other feeds in the system (most_popular_first)")."</b><br>";
		global $db;
		$sql = "SELECT count, content_rss_aggregator_feeds.url, title FROM content_rss_aggregator_feeds 
JOIN (SELECT url, count(content_rss_aggregator_feeds.url) as count 
FROM content_rss_aggregator_feeds 
 WHERE content_rss_aggregator_feeds.url NOT IN (SELECT url FROM content_rss_aggregator_feeds WHERE content_id='{$this->id}') 
GROUP BY content_rss_aggregator_feeds.content_id, content_rss_aggregator_feeds.url)
AS available_feeds
 ON (available_feeds.url=content_rss_aggregator_feeds.url) 
ORDER by count DESC";
		$feed_urls = null;
		$db->ExecSql($sql, $feed_urls, false);
		$tab = array ();
		$i = 0;
		foreach ($feed_urls as $feed_row)
		{
			$tab[$i][0] = $feed_row['url'];
			empty($feed_row['title'])?$title=$feed_row['url']:$title=$feed_row['title'];
			$tab[$i][1] = sprintf(_("%s, used %d times"), $title, $feed_row['count']);
			$i ++;
		}
		$name = "rss_aggregator_{$this->id}_feed_add";
		$html .= "<input type='text' size='60' value='' name='$name' id='$name'>\n";
		$html .= FormSelectGenerator :: generateFromArray($tab, null, 'existing_feeds', 'RssAggregator', true, _('Type URL manually'), "onchange='this.form.$name.value=this.value;'");
		$name = "rss_aggregator_{$this->id}_feed_add_button";
		$html .= "<input type='submit' name='$name' value='"._("Add")."'>";

		$html .= "</li>\n";
		$html .= "</ul>\n";
		$html .= "</div>\n";

		$html .= $subclass_admin_interface;
		return parent :: getAdminUI($html);
	}

	function processAdminUI()
	{
		if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
		{
			//pretty_print_r($_REQUEST);
			parent :: processAdminUI();

			/* number_of_display_items */
			$name = "rss_aggregator_".$this->id."_display_num_items";
			$this->setDisplayNumItems($_REQUEST[$name]);

			/* algorithm_strength */
			$name = "rss_aggregator_".$this->id."_algorithm_strength";
			$this->setAlgorithmStrength($_REQUEST[$name]);

			/* max_item_age */
			$name = "rss_aggregator_".$this->id."_max_item_age";
			$this->setMaxItemAge($_REQUEST[$name]);

			/* Add new feed */
			$name = "rss_aggregator_{$this->id}_feed_add";
			if (!empty ($_REQUEST[$name]))
			{
				$this->addFeed($_REQUEST[$name]);
			}
			foreach ($this->content_rss_aggregator_feeds_rows as $feed_row)
			{
				$this->processFeedAdminUI($feed_row);
				/* Delete feeds */
				$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_delete";
				if (isset ($_REQUEST[$name]))
				{
					$this->removeFeed($feed_row['url']);
				}
			}

		}
	}
	/** Feed-specific section of the admin interface
	 * @param  $feed_row The database row of the content_rss_aggregator_feeds table
	 * */
	private function getFeedAdminUI($feed_row)
	{
		$html = '';
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>".$feed_row['title']."</div>\n";

		$html .= "</div>\n";
		$html .= "<div class='admin_section_data'>\n";
				
		/* url */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("URL").": \n";
		if(	!$this->press_review->isFeedAvailable($feed_row['url']))
{
	$html .= "<br/><span class='warningmsg'>"._("WARNING:  Either the feed couldn't be retrieved, or it couldn't be parsed.  Please double check the URL.")."</span>";
}
$html .= "</div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_url";
		$value = $feed_row['url'];
		$html .= "<input type='text' size='60' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		/* default_publication_interval */
		$html .= "<div class='admin_section_container'>\n";
		$calculated_pub_interval = $this->press_review->getFeedPublicationInterval($feed_row['url']);
		if ($calculated_pub_interval == true)
		{
			$html .= sprintf(_("The feed publishes an item every %.2f day(s)"), $calculated_pub_interval / (60 * 60 * 24));
		}
		else
		{
			$html .= "<div class='admin_section_title'><span class='warningmsg'>"._("WARNING:  This feed does not include the publication dates. 
			The system needs to be able to compute approximate publication date for each entry, so the entry can be weighted against the others. In order for the aggregator to do a good job, you need to estimate fublication frequency of the items, in days.
			  If unset, defaults to one day. 
					").": </span></div>\n";
			$html .= "<div class='admin_section_data'>\n";
			$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_default_publication_interval";
			//$value = $feed_row['default_publication_interval'];
			if (!empty ($feed_row['default_publication_interval']))
			{
				$value = $feed_row['default_publication_interval'] / (60 * 60 * 24);
			}
			else
			{
				$value = '';
			}
			$html .= "<input type='text' size='60' value='$value' name='$name'>\n";
			$html .= "</div>\n";
		}
		$html .= "</div>\n";

		/* bias */
		$html .= "<div class='admin_section_container'>\n";
		$html .= "<div class='admin_section_title'>"._("The bias to be given to the source by the selection algorithm.
					    Bias must be > 0 , typical values would be between 0.75 and 1.5 and default is 1 (no bias).  A bias of 2 will cause the items to \"look\" twice as recent to the algorithm.
					    A bias of 0.5 to look twice as old.  Be carefull, a bias of 2 will statistically cause the 
					  feed to have MORE than twice as many items displayed. ").": </div>\n";
		$html .= "<div class='admin_section_data'>\n";
		$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_bias";
		$value = $feed_row['bias'];
		$html .= "<input type='text' size='60' value='$value' name='$name'>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		$html .= "</div>\n";
		return $html;
	}

	/** Feed-specific section of the admin interface 
	 *  @param  $feed_row The database row of the content_rss_aggregator_feeds table
	 * */
	private function processFeedAdminUI($feed_row)
	{
		global $db;
		$original_url = $db->EscapeString($feed_row['url']);
		/* bias */
		$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_bias";
		$original_bias = $db->EscapeString($feed_row['bias']);
		$bias = $db->EscapeString($_REQUEST[$name]);
		if (is_numeric($bias) && $bias > 0 && $bias != $original_bias) /* Only update database if the mode is valid and there is an actual change */
		{
			$db->ExecSqlUpdate("UPDATE content_rss_aggregator_feeds SET bias = '$bias' WHERE content_id = '$this->id' AND url='$original_url'", false);
			$this->refresh();
		}
		elseif (!is_numeric($bias) || $bias <= 0)
		{
			echo "processFeedAdminUI():"._("The bias must be a positive real number");
		}
		else
		{
			/* Successfull, but nothing modified */
		}

		/* default_publication_interval */
		$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_default_publication_interval";
		if (isset ($_REQUEST[$name]))
		{
			$original_default_publication_interval = $db->EscapeString($feed_row['default_publication_interval']);
			$default_publication_interval = $db->EscapeString($_REQUEST[$name] * (60 * 60 * 24));
			if ((empty ($default_publication_interval) || (is_numeric($default_publication_interval) && $default_publication_interval > 0)) && $default_publication_interval != $original_default_publication_interval) /* Only update database if the mode is valid and there is an actual change */
			{
				if (empty ($default_publication_interval))
				{
					$default_publication_interval = 'NULL';
				}
				$db->ExecSqlUpdate("UPDATE content_rss_aggregator_feeds SET default_publication_interval = $default_publication_interval WHERE content_id = '$this->id' AND url='$original_url'", false);
				$this->refresh();
			}
			elseif (!is_numeric($bias) || $bias <= 0)
			{
				echo "processFeedAdminUI():"._("The default publication must must be a positive integer or empty");
			}
			else
			{
				/* Successfull, but nothing modified */
			}
		}

		/* url, we must change it last or we won't find the row again */
		$name = "rss_aggregator_".$this->id."_feed_".md5($feed_row['url'])."_url";
		$url = $db->EscapeString($_REQUEST[$name]);

		if (!empty ($url) && $url != $feed_row['url']) /* Only update database if the mode is valid and there is an actual change */
		{
			$db->ExecSqlUpdate("UPDATE content_rss_aggregator_feeds SET url = '$url' WHERE content_id = '$this->id' AND url='$original_url'", false);
			$this->refresh();
		}
		elseif (empty ($url))
		{
			echo "processFeedAdminUI():"._("The URL cannot be empty!");
		}
		else
		{
			/* Successfull, but nothing modified */
		}

		return true;
	}

	/** Retreives the user interface of this object.
	 * @return The HTML fragment for this interface */
	public function getUserUI()
	{
		$html = '';
		$html .= "<div class='user_ui_data'>\n";
		$html .= "<div class='user_ui_object_class'>Content (".get_class($this)." instance)</div>\n";
		if (RSS_SUPPORT)
		{
			try
			{
				$html = $this->press_review->get_rss_html($this->content_rss_aggregator_row['number_of_display_items']);
			}
			catch (Exception $e)
			{
				$html = sprintf(_("Could not get RSS feed: %s"), $feed_row['url']);
			}
		}
		else
		{
			$html = _("RSS support is disabled");
		}

		$html .= "</div>\n";

		return parent :: getUserUI($html);
	}

} /* end class */
?>