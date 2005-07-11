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
/**@file HotspotRss.php
 * @author Copyright (C) 2004-2005 Benoit Grégoire, Technologies Coeus inc.
*/

require_once BASEPATH.'classes/FormSelectGenerator.php';
require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/LocaleList.php';
require_once BASEPATH.'classes/Locale.php';

error_reporting(E_ALL);

/** Interim code to display the RSS feed for a hotspot
 */
class HotspotRss extends Content
{
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
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
			$node=Node::getCurrentNode();
			$hotspot_rss_url = $node->getRSSURL();
			
			require_once BASEPATH.'classes/RssPressReview.inc';
			RssPressReview::setMagpieDir(BASEPATH.MAGPIE_REL_PATH);
						RssPressReview::setOutputEncoding("UTF-8");
			$press_review = new RssPressReview;

			
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
			
			$press_review = new RssPressReview;
			$hotspot_rss_html = null;
			if (!empty ($hotspot_rss_url))
			{
				$extract_array = null;
				$extract_array = preg_split($tokens, $hotspot_rss_url);
				//print_r($extract_array);
				foreach ($extract_array as $source)
				{
					$press_review->addSourceFeed($source,  7 * 24 * 3600);
				}
                try {
				    $hotspot_rss_html = $press_review->get_rss_html(5);
                } catch(Exception $e)
                {
                    $hotspot_rss_html = _("Could not get hotspot RSS feed");
                }
			}

			//echo $networkrss_html;
			$html .= $network_rss_html;

			//echo $hotspot_rss_html;
			$html .= $hotspot_rss_html;
			//   error_reporting($old_error_level);
		}

		$html .= "</div>\n";

		return parent :: getUserUI($html);
	}

} /* end class */
?>