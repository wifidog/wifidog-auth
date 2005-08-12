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

error_reporting(E_ALL);

/** Interim code to display the RSS feed for a hotspot
 */
class RssAggregator extends Content
{
	private $content_rss_aggregator_row;
	private $content_rss_aggregator_feeds_rows;	
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;
		$content_id = $db->EscapeString($content_id);

		$sql = "SELECT * FROM content_rss_aggregator WHERE content_id='$content_id'";
		$row=null;
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
		$content_rss_aggregator_rows=null;
        $db->ExecSql($sql, $content_rss_aggregator_rows, false);
        if($content_rss_aggregator_rows!=null)
        {
        	        $this->content_rss_aggregator_feeds_rows = $content_rss_aggregator_rows;
        }
        else
        {
        	        $this->content_rss_aggregator_feeds_rows = array();
        }
        
		$this->setIsTrivialContent(true);
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
			require_once BASEPATH.'lib/RssPressReview/RssPressReview.php';
			$press_review = new RssPressReview(BASEPATH.MAGPIE_REL_PATH, "UTF-8");

			/*
			$tokens = "/[\s,]+/";
			$network_rss_sources = NETWORK_RSS_URL;
			$network_rss_html = null;
			if (!empty ($network_rss_sources))
			{

				$extract_array = null;
				$extract_array = preg_split($tokens, $network_rss_sources);
				//print_r($extract_array);
				foreach ($extract_array as $source)
				{
				$press_review->addSourceFeed($source,  7 * 24 * 3600);
				}
                try {
				    $network_rss_html = $press_review->get_rss_html(5);
                } catch(Exception $e)
                {
                    $network_rss_html = _("Could not get network RSS feed");
                }
			}
			*/
			$press_review = new RssPressReview(BASEPATH.MAGPIE_REL_PATH, "UTF-8");
			$press_review->setAlgorithmStrength($this->content_rss_aggregator_row['algorithm_strength']);
			$press_review->setMaxItemAge($this->content_rss_aggregator_row['max_item_age']);
				foreach ($this->content_rss_aggregator_feeds_rows as $feed_row)
				{
					$press_review->addSourceFeed($feed_row['url'],  $feed_row['default_publication_interval'], $feed_row['bias']);
				}
                try {
				    $html = $press_review->get_rss_html($this->content_rss_aggregator_row['number_of_display_items']);
                } catch(Exception $e)
                {
                    $html = sprintf(_("Could not get RSS feed: %s"), $feed_row['url']);
                }
			//   error_reporting($old_error_level);
		}

		$html .= "</div>\n";

		return parent :: getUserUI($html);
	}

} /* end class */
?>