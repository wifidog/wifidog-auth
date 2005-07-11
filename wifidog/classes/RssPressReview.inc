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
/**@file RssPressReview.inc
 * @author Copyright (C) 2004 Benoit Grégoire (http://benoitg.coeus.ca/) et Technologies Coeus inc.
 */
define('MAGPIE_DEBUG', 0);
define('DEFAULT_PUBLICATION_INTERVAL', (1 * 24 * 3600));

class RssPressReview
{
	private $output_encoding;
	private static $magpie_dir = '';
	private $rss_sources; /**<
			$rss_sources is an array of arrays, each of which must contain:
			['url']=Url of the rss feed
			[0]['default_publication_interval']= The average interval between publication of news items for this source (in seconds).  Will be used for calculations if the date of each news for the source cannot be determined
			
			Example array of sources:
			
			$rss_sources = array(
			array('url' => "http://www.abondance.com/rss/rss.xml", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://manchettes.branchez-vous.com/benefice-net.xml", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://www.infobourg.com/filnouvelles/breves.xml", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://p7app.geneve.ch:8007/lesemeur/_backend-breves.php3?id_rubrique=16", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://linuxeduquebec.org/backend.php3", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://thot.cursus.edu/breves/breves.xml", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL),
			array('url' => "http://fynl.free.fr/dotclear/rss.php", 'default_publication_interval' => DEFAULT_PUBLICATION_INTERVAL)
			);*/
	/** Allow you to select the absolute or relative directory path where the class can find Magpie.
	 * If you do not set it, the class will look in the current include path.
	 * */
	public function setMagpieDir($path)
	{
		self :: $magpie_dir = $path;
	}

	public function __construct()
	{
		require_once (self :: $magpie_dir.'rss_fetch.inc');
		require_once (self :: $magpie_dir.'rss_utils.inc');
	}

	/** Set the current output encoding.  The default is defined by MAGPIE (usually ISO-8859-1), the list of possible value is returned by mb_list_encodings().  
	 * See http://ca.php.net/manual/en/function.mb-list-encodings.php
	 * Note that because of the way magpie is implemented, this function can only be called ONCE.  
	 * Should you try to call it again, it will return false and have no effect.
	 * @return true if successfull, false if the encoding had already been specified */
	public function setOutputEncoding($encoding)
	{
		if (!defined('MAGPIE_OUTPUT_ENCODING'))
		{
			define('MAGPIE_OUTPUT_ENCODING', $encoding);
			$retval = true;
		}
		else
		{
			$retval = false;
		}
		return $retval;
	}

	/** Get the current output encoding */
	public function getOutputEncoding()
	{
		return $this->output_encoding;
	}

	/** Figures out a date for the Magpie RSS item and return's it
	  * @return the date in timestamp format or -1 if unavailable
	  */
	static private function get_item_date($item, $debug = 0)
	{
		$retval = -1;
		if (!empty ($item['dc']['date']))
		{
			$datestr = $item['dc']['date'];
		}
		else
			if (!empty ($item['pubdate']))
			{
				$datestr = $item['pubdate'];
			}
			else
				if (!empty ($item['date']))
				{
					$datestr = $item['date'];
				}
				else
					if (!empty ($item['created']))
					{
						$datestr = $item['created'];
					}
					else
					{
						if ($debug)
						{
							echo "<p>get_item_date(): No date present!</p>";
						}
						$datestr = null;
					}

		if ($datestr == null)
		{
			$retval = -1;
		}
		else
		{
			if ($debug)
			{
				echo "<p>get_item_date(): String to convert: $datestr</p>";
			}

			$retval = parse_w3cdtf($datestr);
			if ($retval == -1)
			{
				$retval = strtotime($datestr);
			}

			if ($debug)
			{
				if ($retval == -1)
				{
					echo "<p>get_item_date(): Conversion of $datestr failed!</p>";
				}
				else
				{
					echo "<p>get_item_date(): Conversion succeded</p>";
					setlocale(LC_TIME, "fr_CA");
					echo strftime("%c", $retval);
				}
			}
		}

		if ($debug)
		{
			echo "<p>get_item_date(): Retval: $retval</p>";
		}
		return $retval;
	}

	/** Inverted compare function for adjusted date.  Used to sort by adjusted date, most recent first  */
	static private function cmp($a, $b)
	{
		if ($a['adjusted_date'] == $b['adjusted_date'])
		{
			return 0;
		}
		return ($a['adjusted_date'] > $b['adjusted_date']) ? -1 : 1;
	}

	/** This is the static comparing function to sort rss items in chronological order: */
	static private function cmp_date_item($a, $b)
	{
		$a_date = self :: get_item_date($a);
		$b_date = self :: get_item_date($b);
		if ($a_date == $b_date)
		{
			return 0;
		}
		/*echo "cmp_date_item(): a:$a_date, b:$b_date ";
		echo ($a_date < $b_date) ? +1 : -1;*/
		return ($a_date < $b_date) ? +1 : -1;
	}

	/** Add a RSS source feed to be merged into the review 
	 * @param $url:  The feed's URL
	 * @param $estimated_publication_interval:  The average time interval in seconds between items published on this 
	 * feed.  If unset, defaults to  DEFAULT_PUBLICATION_INTERVAL (1 * 24 * 3600 (one day) as of this writing) 
	 * This parameter is ignored if the the value can be computed exactly, which is usually the case for
	 * RSS 2.0 and atom feeds.
	 * However, if the feed does not include the date, the system needs to be able to compute approximate 
	 * publication date for each entry, so the entry can be weighted against the others.  
	 * @return true if successfull, false otherwise */

	public function addSourceFeed($url, $estimated_publication_interval = DEFAULT_PUBLICATION_INTERVAL)
	{
			//echo "RssPressReview::addSourceFeed($url, $estimated_publication_interval)<br>\n";
	$retval = false;
		if (!empty ($url))
		{

			$rss = fetch_rss(trim($url));
			if ($rss)
			{
				$this->rss_sources[trim($url)] = array ('default_publication_interval' => $estimated_publication_interval, 'magpie_array' => $rss);
				$this->computePublicationInterval(trim($url));
				$retval = true;
			}

		}
		return $retval;
	}

	/** Calculate the publication interval of a feed's items.  This will fail if there is no date field that can be used.  In this case we calculate approximate intervals based on the default publication interval
	 * @param $url The feed's url
	 */
	private function computePublicationInterval($url)
	{
		$i = 0;
		$real_date_missing = false; /**< Is at least one of the item missing a date? */
		$publication_interval_total = null;
		/**< running total of the difference between the date of the current item and the previous one */
		$average_publication_interval = null;
		$previous_item_date = null;
		$rss = & $this->rss_sources[trim($url)]['magpie_array'];
		if (!$rss)
		{
			throw new Exception(_("computePublicationInterval(): Missing feed!"));
		}

		//$rss->show_channel();
		//$rss->show_list();

		/* Sort the array in chronological order */

		/* foreach  ($rss->items as $item) {echo "$item[title] ". self::get_item_date($item) . "<br>\n";}*/
		if (!uasort($rss->items, array ("RssPressReview", "cmp_date_item")))
		{
			throw new Exception(_('uasort() failed'));
		}
		/* foreach  ($rss->items as $item) {echo "$item[title] ". self::get_item_date($item) . "<br>\n";}*/

		/* Calculate the publication interval for the feed */
		foreach ($rss->items as & $item)
		{
			$date = self :: get_item_date($item);
			$item['rpr_real_date'] = $date;
			if ($date == -1)
			{
				/*
				 *  If we do not know the date, for statistics purposes,
				 *  we will set the date as if a news item as published
				 *  every default_publication_interval, starting now.
				 */
				$date = time() - ($i * $this->rss_sources[$url]['default_publication_interval']);
				$real_date_missing = true;
			}

			if ($i > 0)
			{
				$publication_interval_total += $previous_item_date - $date;
			}

			$previous_item_date = $date;
			$item['rpr_computed_date'] = $date; /** So every item has sone kind of a date */
			$i ++;
		} // End foreach items

		$this->rss_sources[$url]['rpr_number_of_items'] = $i;

		if ($i >= 2 && $publication_interval_total != 0)
		{
			$average_publication_interval = $publication_interval_total / ($i -1);
			$this->rss_sources[$url]['rpr_computed_publication_interval'] = $average_publication_interval;
			if ($real_date_missing == false)
			{
				$this->rss_sources[$url]['rpr_real_publication_interval'] = $average_publication_interval;
			}
		}
		else
		{
			$this->rss_sources[$url]['rpr_computed_publication_interval'] = $this->rss_sources[$url]['default_publication_interval'];
		}
		//echo "<pre>"; print_r($this->rss_sources[$url]);echo "</pre>";
	}

	/** Return the combined RSS of all the sources, in magpie format
	 @param $number_of_items_to_display The total number of items to display.
	 @return The formatted html of the press review
	*/
	public function get_rss($number_of_items_to_display = 20)
	{
			$old_error_level = error_reporting(E_ERROR);
	$debug = false;
		//echo "<pre>"; print_r($this->rss_sources);echo "</pre>";
		if ($debug)
			echo "RssPressReview::get_rss($number_of_items_to_display)<br>\n";
		$item_date_array = array ();
		$item_date_array_index = 0;

		foreach ($this->rss_sources as $rss_sources_key => $rss_source)
		{
			foreach ($rss_source['magpie_array']->items as $item)
			{ /* Memorize each date, and publication intervals so we can determine the "oldest" item to publish */

				$item_date_array[$item_date_array_index]['date'] = $item['rpr_computed_date'];
				$item_date_array[$item_date_array_index]['rss_sources_key'] = $rss_sources_key;
				$item_date_array_index ++;
			}

			//echo "<p>$i items, average_publication_interval (days) = ". $this->rss_sources[$rss_sources_key]['average_publication_interval']/(3600 * 24) . "</p>\n";
		} // End foreach rss feeds

		$all_feed_publication_interval_total = null;
		reset($this->rss_sources);
		foreach ($this->rss_sources as $rss_source)
		{
			$all_feed_publication_interval_total += $rss_source['rpr_computed_publication_interval'];
		}
		$all_feed_publication_interval = $all_feed_publication_interval_total / count($this->rss_sources);

		for ($i = 0; isset ($item_date_array[$i]); $i ++)
		{
			$average_publication_interval = $this->rss_sources[$item_date_array[$i]['rss_sources_key']]['rpr_computed_publication_interval'];
			$adjust_factor = $all_feed_publication_interval / $average_publication_interval;
			$item_date_array[$i]['adjusted_date'] = time() - ((time() - $item_date_array[$i]['date']) * $adjust_factor);
		}

		usort($item_date_array, array ("RssPressReview", "cmp"));

		/*echo "<pre>";
		print_r($item_date_array);
		echo "</pre>";*/

		if (count($item_date_array) < $number_of_items_to_display)
		{
			$number_of_items_to_display = count($item_date_array);
		}

		$min_adjusted_date_to_display = $item_date_array[$number_of_items_to_display -1]['adjusted_date'];

		if ($debug)
			echo "min_adjusted_date_to_display: $min_adjusted_date_to_display<br>\n";
		/************** Now we actually display the feeds **************/
		reset($this->rss_sources);
		foreach ($this->rss_sources as $rss_sources_key => $rss_source)
		{
			$rss = & $rss_source['magpie_array'];
			if (!$rss)
			{
				throw new Exception(_('Feed missing'));
			}
			else
			{
				unset ($rss_info_tmp);
				$rss_info_tmp['title'] = $rss->channel['title'];
				$rss_info_tmp['link'] = $rss->channel['link'];
				$rss_info_tmp['encoding'] = $rss->encoding;

				$i = 0;
				/* Sort the items by date, so we get the most recent first */
				if (!uasort($rss->items, array ("RssPressReview", "cmp_date_item")))
				{
					throw new Exception(_('uasort() failed'));
				}

				$rss_info_tmp['items'] = array ();
				foreach ($rss->items as $item)
				{
					$date = $item['rpr_computed_date'];
					$realdate = $item['rpr_real_date'];

					$average_publication_interval = $this->rss_sources[$rss_sources_key]['rpr_computed_publication_interval'];

					$adjust_factor = $all_feed_publication_interval / $average_publication_interval;
					$adjusted_date = time() - ((time() - $date) * $adjust_factor);
					if ($debug)
						echo "Is item with adjusted_date: $adjusted_date after the minimum date: $min_adjusted_date_to_display?<br>\n";

					if ($adjusted_date >= $min_adjusted_date_to_display)
					{
						if ($debug)
							echo "YES!<br>\n";
						$author = '';
						if (!empty ($item['dc']['creator']))
						{
							$author = trim($item['dc']['creator']);
						}
						elseif (!empty ($item['author']))
						{
							$author = trim($item['author']);
						}
						elseif (!empty ($item['author_name']))
						{
							$author = trim($item['author_name']);
						}

						$link = '';
						if (!empty ($item['link']))
						{
							$link = $item['link'];
						}
						array_push($rss_info_tmp['items'], array ("title" => $item['title'], "summary" => $item['summary'], "author" => $author, "date" => $realdate, "link" => $link, "id" => "summary_".mt_rand(1, 10000)));
					}
					$i ++;
				} // End foreach items

				if ($debug)
					echo count($rss_info_tmp['items'])." items (out of ".count($rss->items).") are after the minimum date in feed $rss_sources_key<br>\n";

				$rss_info[] = $rss_info_tmp;
			}
		} // End foreach rss feeds
		/*echo "\n<pre>\n";
		print_r($rss_info);
		echo "\n</pre>\n";
		*/
		return $rss_info;
		error_reporting($old_error_level);
	}

	function get_rss_header()
	{
		static $done_header = false;

		if (!$done_header)
		{
			$done_header = true;
			return '
															<style>
															div.introduction {
															  font-size: 12px;
															 padding: 5px;
															 border: 1px solid #324C48;
															     background-color: #E0EBE9;
															 visibility: hidden;
															 position: absolute;
															 left: 25px;
															 top: 0px;
															 width: 350px;
															   -moz-opacity: 0.95; filter: alpha(opacity=95);
															}
															
															</style>
															
															<script language="JavaScript" type="text/javascript">
															  function MM_findObj(n, d) { //v4.0
															  var p,i,x;
															  if(!d) d=document; 
															  if((p=n.indexOf("?"))>0&&parent.frames.length) {
															    d=parent.frames[n.substring(p+1)].document; 
															    n=n.substring(0,p);
															  }
															  if(!(x=d[n])&&d.all) x=d.all[n]; 
															  for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
															  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
															  if(!x && document.getElementById) x=document.getElementById(n); return x;
															}
																	
															function changestyle(couche, style) {
															  if (!(layer = MM_findObj(couche))) return;
															  layer.style.visibility = style;
															}
																	
															function changeclass(objet, myClass)
															{ 
															  objet.className = myClass;
															}
															</script>';
		}
	}

	public function get_rss_html($number_of_items_to_display = 20)
	{
		$rss_result = $this->get_rss($number_of_items_to_display);

		$html = '';
		foreach ($rss_result as $feed)
		{
			$feed_html = '';
			$feed_html .= "<p class=textegris>"._('Source: ')."<b>";
			if (!empty ($feed['link']))
			{
				$feed_html .= "<a class='y' href='".$feed['link']."'>".$feed['title']."</a>";
			}
			else
			{
				$feed_html .= $channel_title;
			}
			$feed_html .= "</b></p>\n";
			$feed_html .= "<ul>\n";

			foreach ($feed['items'] as $item)
			{
				if ($item['date'] != -1)
				{
					setlocale(LC_TIME, "fr_CA");
					$display_date = strftime("%x", $item['date']);
				}
				else
				{
					$display_date = '';
				}

				$dhtml_id = "summary_".mt_rand(1, 10000);
				$feed_html .= "<li>\n";
				$feed_html .= "<span class='textegrispetit'>$display_date</span>";
				$feed_html .= "<span class='y' onMouseOver=\"changestyle('$dhtml_id','visible');\" onMouseOut=\"changestyle('$dhtml_id','hidden');\">\n";
				if ($item['link'])
				{
					$feed_html .= "<a class='y' href='{$item['link']}'><b>{$item['title']}</b></span></a><br />";
				}
				else
				{
					$feed_html .= "<b>{$item['title']}</b></span><br />";
				}
				$feed_html .= "<div style='z-index: 1000; position: relative'>\n";
				$feed_html .= "<div style='z-index: 1000;' id='$dhtml_id' class='introduction'><p>{$item['author']} ({$feed['title']}) $display_date</p>\n";
				$summary = strip_tags($item['summary'], "<p><a><img><b><i>");
				$feed_html .= "<p>{$summary}</p></div>\n";
				$feed_html .= "</div>\n";
				$feed_html .= "</li>\n";
			}
			$feed_html .= "</ul>\n";

			$html .= $feed_html;
		}
		return $this->get_rss_header().$html;
	}
}
?>





