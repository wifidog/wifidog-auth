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
 * @author Copyright (C) 2004-2005 Benoit Grégoire (http://benoitg.coeus.ca/) et Technologies Coeus inc.
 */
//define('MAGPIE_DEBUG', 0);
define('DEFAULT_PUBLICATION_INTERVAL', (1 * 24 * 3600));

class RssPressReview
{
	var $output_encoding;
	var $magpie_dir = '';
	var $rss_sources; /**<
							$rss_sources is an array of arrays, each of which must contain:
							);*/

	var $algorithm_strength_modifier = 0.75;
	var $max_item_age = null;

	/** Allow you to select the absolute or relative directory path where the class can find Magpie.
	 * If you do not set it, the class will look in the current include path.
	 * */
	function _setMagpieDir($path)
	{
		$this->magpie_dir = $path;
	}

	function RssPressReview($magpie_dir, $output_encoding)
	{
		$this->_setOutputEncoding($output_encoding);
		$this->_setMagpieDir($magpie_dir);
		require_once ($this->magpie_dir.'rss_fetch.inc');
		require_once ($this->magpie_dir.'rss_utils.inc');
	}

	/** Set the current output encoding.  The default is defined by MAGPIE (usually ISO-8859-1), the list of possible value is returned by mb_list_encodings().  
	 * See http://ca.php.net/manual/en/function.mb-list-encodings.php
	 * Note that because of the way magpie is implemented, this function can only be called ONCE PER SCRIPT, even if you destroy and recreate the object.  
	 * Should you try to call it again, it will return false and have no effect.
	 * @return true if successfull, false if the encoding had already been specified */
	function _setOutputEncoding($encoding)
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
	function getOutputEncoding()
	{
		return MAGPIE_OUTPUT_ENCODING;
	}

	/** Set the how much bonus feeds that do not publish as often get over feed that publish more often.
	 * @param $algorithm_strength A positive float, defaults to 0.75.  Typical range is between 0 and 1. 
	 *  At 0, you have a normal RSS aggregator, meaning the n most recent entries picked from all feeds
	 *  will be displayed. 1 is usually as high as you'll want to go.  Assuming that all 
	 * feeds have an homogenous internal distribution (say one publishes exactly one entry a day, the 
	 * other one entry every two days, and the third one entry every three days), and you ask for 15 entries,
	 * there will be 5 of each.  While that may not sound usefull, it still is, as the feed's distribution is 
	 * usually not homogenous.  Most people will probably be happy with values between 0.5 and 1  
	 * @return true if successfull, false if $algorithm_strength < 0 */
	function setAlgorithmStrength($algorithm_strength = 0.75)
	{
		$retval = false;
		if ($algorithm_strength > 0)
		{
			$this->algorithm_strength_modifier = $algorithm_strength;
			$retval = true;
		}
		return $retval;
	}

	/** Set the oldest entries you are willing to see.  Any entries older than this will not
	 *  be considered at all for display.  Note that they will still be considered for 
	 * publication interval calculations.  Most people do NOT want to set this, it's only usefull if
	 *  all your feed publish very rarely, and you don't want entries older than a month, even if 
	 * there aren't enough items left to reach the number of items you asked for.
	 * @param $max_item_age: Age in seconds or null.   null means no limit (the default), a typical value would be 3600 * 24 * 30 (a month) 
	 * @return true if successfull, false if $max_item_age < 0 */
	function setMaxItemAge($max_item_age = null)
	{
		$retval = false;
		if (is_numeric($max_item_age) && $max_item_age >= 0)
		{
			$this->max_item_age = $max_item_age;
			$retval = true;
		}
		return $retval;
	}

	/** Figures out a date for the Magpie RSS item and return's it
	  * @return the date in timestamp format or -1 if unavailable
	  */
	function _get_item_date($item, $debug = 0)
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
							echo "<p>_get_item_date(): No date present!</p>";
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
				echo "<p>_get_item_date(): String to convert: $datestr</p>";
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
					echo "<p>_get_item_date(): Conversion of $datestr failed!</p>";
				}
				else
				{
					echo "<p>_get_item_date(): Conversion succeded</p>";
					setlocale(LC_TIME, "fr_CA");
					echo strftime("%c", $retval);
				}
			}
		}

		if ($debug)
		{
			echo "<p>_get_item_date(): Retval: $retval</p>";
		}
		return $retval;
	}

	/** Inverted compare function for adjusted date.  Used to sort by adjusted date, most recent first  */
	function _cmp_rpr_adjusted_date($a, $b)
	{
		if ($a['rpr_adjusted_date'] == $b['rpr_adjusted_date'])
		{
			return 0;
		}
		return ($a['rpr_adjusted_date'] > $b['rpr_adjusted_date']) ? -1 : 1;
	}

	/** This is the static comparing function to sort rss items in chronological order: */
	function _cmp_date_item($a, $b)
	{
		$a_date = RssPressReview :: _get_item_date($a);
		$b_date = RssPressReview :: _get_item_date($b);
		if ($a_date == $b_date)
		{
			return 0;
		}
		/*echo "_cmp_date_item(): a:$a_date, b:$b_date ";
		echo ($a_date < $b_date) ? +1 : -1;*/
		return ($a_date < $b_date) ? +1 : -1;
	}

	/** Add a RSS source feed to be merged into the review 
	 * @param $url:  The feed's URL
	 * @param $estimated_publication_interval:  The average time interval in seconds between items published on this 
	 * feed.  If unset (or set to null), defaults to  DEFAULT_PUBLICATION_INTERVAL (1 * 24 * 3600 (one day) as of this writing) 
	 * This parameter is ignored if the the value can be computed exactly, which is usually the case for
	 * RSS 2.0 and atom feeds.
	 * However, if the feed does not include the date, the system needs to be able to compute approximate 
	 * publication date for each entry, so the entry can be weighted against the others.  
	 * @param $source_bias Optional, integer > 0.  The bias to be given to the source by the selection algorithm.
	 *   Default is 1 (no bias).  A bias of 2 will cause the items to "look" twice as recent to the algorithm.
	 *   A bias of 0.5 to look twice as old.  Be carefull, a bias of 2 will statistically cause the 
	 * feed to have MORE than twice as many items displayed.  Typical values would be between 0.75 and 1.5
	 * @return true if successfull, false otherwise */

	function addSourceFeed($url, $estimated_publication_interval = DEFAULT_PUBLICATION_INTERVAL, $source_bias = 1)
	{
			//echo "RssPressReview::addSourceFeed($url, $estimated_publication_interval)<br>\n";
	$retval = false;
		if (empty ($estimated_publication_interval))
		{
			$estimated_publication_interval = DEFAULT_PUBLICATION_INTERVAL;
		}
		if (!empty ($url))
		{
			$old_error_level = error_reporting(E_ERROR);
			$rss = fetch_rss(trim($url));
			//echo "<pre>"; print_r($rss);echo "</pre>";
			error_reporting($old_error_level);

			if ($rss && $source_bias > 0)
			{
				$this->rss_sources[trim($url)] = array ('default_publication_interval' => $estimated_publication_interval, 'magpie_array' => $rss, 'source_bias' => $source_bias);
				$this->computePublicationInterval(trim($url));
				$retval = true;
			}

		}
		else
		{
			echo _('addSourceFeed(): url is empty!');
		}
		return $retval;
	}

	/** Is the feed in rss_sources.  Equivalent to checking the 
	 * return of addSourceFeed(), but can be called at any time.  Will
	 * return false if the feed could not be retrieven or coulsdn't be parsed.
	 * @param $url 
	 * @return true if the feed has been successfully retrieved */
	function isFeedAvailable($url)
	{
		if (isset ($this->rss_sources[$url]))
		{
			$retval = true;
		}
		else
		{
			$retval = false;
		}
		return $retval;
	}

	/** Return the title of the feed, if the feed contains title information.
	 * @param $url 
	 * @return Title or false if unavailable */
	function getFeedTitle($url)
	{
		$retval = false;
		if (isset ($this->rss_sources[$url]) && isset ($this->rss_sources[$url]['magpie_array']->channel['title']))
		{
			$retval = $this->rss_sources[$url]['magpie_array']->channel['title'];
		}
		return $retval;
	}

	/** Return the computed publication interval of the feed, if the feed contained date information.
	 * @param $url
	 * @return publication interval in seconds or false if unavailable */
	function getFeedPublicationInterval($url)
	{
		if (isset ($this->rss_sources[$url]['rpr_real_publication_interval']))
		{
			$retval = $this->rss_sources[$url]['rpr_real_publication_interval'];
		}
		else
		{
			$retval = false;
		}
		return $retval;
	}

	/** Calculate the publication interval of a feed's items.  This will fail if there is no date field that can be used.  In this case we calculate approximate intervals based on the default publication interval.  That is we assume one item is published every default publication interval, starting from half the interval in the past (to avoid always having one item published now, skewing the result.
	 * @param $url The feed's url
	 * @return true on success, false on failure
	 */
	function computePublicationInterval($url)
	{
		$i = 0;
		$real_date_missing = false; /**< Is at least one of the item missing a date? */
		$publication_interval_total = null;
		/**< running total of the difference between the date of the current item and the previous one */
		$feed_publication_interval = null;
		$previous_item_date = null;
		$rss = & $this->rss_sources[trim($url)]['magpie_array'];
		if (!$rss)
		{
			echo _("computePublicationInterval(): Missing feed!");
			return false;
		}

		//$rss->show_channel();
		//$rss->show_list();

		/* Sort the array in chronological order */

		/* foreach  ($rss->items as $item) {echo "$item[title] ". self::_get_item_date($item) . "<br>\n";}*/
		if (!uasort($rss->items, array ("RssPressReview", "_cmp_date_item")))
		{
			echo _('computePublicationInterval(): uasort() failed');
			return false;
		}
		/* foreach  ($rss->items as $item) {echo "$item[title] ". self::_get_item_date($item) . "<br>\n";}*/

		/* Calculate the publication interval for the feed */
		foreach ($rss->items as $item_key => $item)
		{
			$date = $this->_get_item_date($item);
			$rss->items[$item_key]['rpr_realphpdate'] = $date;
			if ($date == -1)
			{
				/*
				 *  If we do not know the date, for statistics purposes,
				 *  we will set the date as if a news item was published
				 *  every default_publication_interval, starting from half the interval in the past
				 *  (to avoid always having one item published now, skewing the result).
				 */
				$date = time() - ($i * $this->rss_sources[$url]['default_publication_interval'] + $this->rss_sources[$url]['default_publication_interval'] / 2);
				$real_date_missing = true;
			}

			if ($i > 0)
			{
				$publication_interval_total += $previous_item_date - $date;
			}

			$previous_item_date = $date;
			$rss->items[$item_key]['rpr_computed_phpdate'] = $date; /** So every item has sone kind of a date */
			$i ++;
		} // End foreach items

		$this->rss_sources[$url]['rpr_number_of_items'] = $i;

		if ($i >= 2 && $publication_interval_total != 0)
		{
			$feed_publication_interval = $publication_interval_total / ($i -1);
			$this->rss_sources[$url]['rpr_computed_publication_interval'] = $feed_publication_interval;
			if ($real_date_missing == false)
			{
				$this->rss_sources[$url]['rpr_real_publication_interval'] = $feed_publication_interval;
			}
		}
		else
		{
			$this->rss_sources[$url]['rpr_computed_publication_interval'] = $this->rss_sources[$url]['default_publication_interval'];
		}
		//echo "<pre>"; print_r($this->rss_sources[$url]);echo "</pre>";
		return true;
	}

	/** Return the combined RSS of all the sources, in an extended magpie format
	 @param $number_of_items_to_display The total number of items to display.
	 @param $last_visit:  Optionnal, timestamp.  The date of the user's last visit.  If set, allows the system to set the rpr_is_new field.  
	 Note that it will only do it for items that have the are 
	 in the return array
	 @return An array of RSS information, false on failure
	*/
	function get_rss_info($number_of_items_to_display = 20, $last_visit = null)
	{
		$debug = false;
		//echo "<pre>"; print_r($this->rss_sources);echo "</pre>";
		if ($debug)
			echo "RssPressReview::get_rss_info($number_of_items_to_display)<br>\n";

		if (!empty ($this->rss_sources))
		{
			/* Calculate the average publication interval for all feeds */
			$all_feed_publication_interval_total = null;
			reset($this->rss_sources);
			foreach ($this->rss_sources as $rss_source)
			{
				$all_feed_publication_interval_total += $rss_source['rpr_computed_publication_interval'];
			}
			$all_feed_publication_interval = $all_feed_publication_interval_total / count($this->rss_sources);

			/*Calculate the adjusted dates and create the date list array. */
			$item_date_array = array ();
			$item_date_array_index = 0;

			foreach ($this->rss_sources as $rss_sources_key => $rss_source)
			{
				foreach ($rss_source['magpie_array']->items as $item_key => $item)
				{
					/* Calculate the adjusted date */
					$feed_publication_interval = $rss_source['rpr_computed_publication_interval'];
					$source_bias = $rss_source['source_bias'];
					$distance_to_today = abs(time() - $item['rpr_computed_phpdate']);
					/* With no strength modifier, a feed with a publication twice as long will get a 2x bonus on the distance to today */
					$original_adjust_factor = $all_feed_publication_interval / $feed_publication_interval;
					if ($original_adjust_factor < 1)
					{
						$algorithm_strength_modifier = 1 / $this->algorithm_strength_modifier;
					}

					/*Algorithm strength modifier doit modifier la difference de lécart du ratio 
					 $all_feed_publication_interval / $feed_publication_interval avec 1/1.*/
					$adjust_factor = 1 - (1 - $original_adjust_factor) * $this->algorithm_strength_modifier;

					$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_adjusted_date'] = time() - ($distance_to_today * $adjust_factor) / $source_bias;

					/* Memorize each date, and publication intervals so we can determine the "oldest" item to publish
					 * Only consider items whose date is not farther from today than max_item_age */
					if ($this->max_item_age == null || abs(time() - $item['rpr_computed_phpdate']) < $this->max_item_age)
					{
						$item_date_array[$item_date_array_index]['rpr_adjusted_date'] = $this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_adjusted_date'];
						$item_date_array[$item_date_array_index]['rpr_computed_phpdate'] = $this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_computed_phpdate'];
						$item_date_array[$item_date_array_index]['rss_sources_key'] = $rss_sources_key;
						$item_date_array_index ++;
					}

					if (isset ($this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_realphpdate']))
					{
						/* Check if the item is newer than the last visit */
						if ($last_visit != null && $item['rpr_realphpdate'] > $last_visit)
						{
							$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_is_new'] = true;
						}

						/* Check if the item has been published today */
						$item_getdate = getdate($this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_realphpdate']);
						$today_getdate = getdate();
						if ($item_getdate['year'] == $today_getdate['year'] && $item_getdate['yday'] == $today_getdate['yday'])
						{
							$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_is_today'] = true;
						}
						else
						{
							$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_is_today'] = false;
						}
					}
					$content = null;
					if (!empty ($item['atom_content']))
					{
						$content = $item['atom_content'];
					}
					else
						if (!empty ($item['content']['encoded']))
						{
							$content = $item['content']['encoded'];
						}
						else
							if (!empty ($item['summary']))
							{
								$content = $item['summary'];
							}
					$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_content'] = $content;

					$summary = null;
					if (!empty ($item['summary']))
					{
						$summary = $item['summary'];
					}
					else
					{
						$summary = $content;
					}
					$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_summary'] = $summary;

				}

				//echo "<p>$i items, average_publication_interval (days) = ". $this->rss_sources[$rss_sources_key]['average_publication_interval']/(3600 * 24) . "</p>\n";
			} // End foreach rss feeds

			/* Sort the item date array by adjusted date. */
			usort($item_date_array, array ("RssPressReview", "_cmp_rpr_adjusted_date"));

			/*echo "<pre>";
			print_r($item_date_array);
			echo "</pre>";*/
			/* Find the "oldest" adjusted date to display */
			if (count($item_date_array) < $number_of_items_to_display)
			{
				$number_of_items_to_display = count($item_date_array);
			}
			$min_rpr_adjusted_date_to_display = $item_date_array[$number_of_items_to_display -1]['rpr_adjusted_date'];

			if ($debug)
				echo "min_rpr_adjusted_date_to_display: $min_rpr_adjusted_date_to_display<br>\n";
			/************** Now we actually display the feeds **************/
			reset($this->rss_sources);
			$rss_info_tmp = null;
			foreach ($this->rss_sources as $rss_sources_key => $rss_source)
			{
				if (!$rss_source['magpie_array'])
				{
					echo _('get_rss_info(): Feed missing');
					return false;
				}
				else
				{
					unset ($rss_info_tmp);
					$rss_info_tmp['channel'] = $this->rss_sources[$rss_sources_key]['magpie_array']->channel;

					$i = 0;
					/* Sort the items by date, so we get the most recent first */
					if (!uasort($this->rss_sources[$rss_sources_key]['magpie_array']->items, array ("RssPressReview", "_cmp_date_item")))
					{
						echo _('get_rss_info(): uasort() failed!');
						return false;
					}

					$rss_info_tmp['items'] = array ();
					foreach ($this->rss_sources[$rss_sources_key]['magpie_array']->items as $item_key => $item)
					{
						if ($debug)
							echo "Is item with rpr_adjusted_date: ".$item['rpr_adjusted_date']." after the minimum date: $min_rpr_adjusted_date_to_display?<br>\n";

						if ($item['rpr_adjusted_date'] >= $min_rpr_adjusted_date_to_display)
						{
							if ($debug)
								echo "YES!<br>\n";
							if (!empty ($item['dc']['creator']))
							{
								$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_author'] = trim($item['dc']['creator']);
							}
							elseif (!empty ($item['author']))
							{
								$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_author'] = trim($item['author']);
							}
							elseif (!empty ($item['author_name']))
							{
								$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_author'] = trim($item['author_name']);
							}
							else
							{
								$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_author'] = '';
							}

							$this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]['rpr_id'] = "summary_".mt_rand(1, 10000);
							array_push($rss_info_tmp['items'], $this->rss_sources[$rss_sources_key]['magpie_array']->items[$item_key]);
						}
						$i ++;
					} // End foreach items

					if ($debug)
						echo count($rss_info_tmp['items'])." items (out of ".count($rss->items).") are after the minimum date in feed $rss_sources_key<br>\n";

					$rss_info[$rss_sources_key] = $rss_info_tmp;
				}
			} // End foreach rss feeds
			/*echo "\n<pre>\n";
			print_r($rss_info);
			echo "\n</pre>\n";
			*/
			return $rss_info;
		}
		else
			return null;
	}

	function get_rss_header()
	{
		static $done_header = false;

		if (!$done_header)
		{
			$done_header = true;
			return '
																											
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
						if(layer.className != "rpr_popup_inner_div_expanded")
						{
						layer.style.visibility = style;
						}
						}
																													
						function changeclass(objet, myClass)
						{ 
						objet.className = myClass;
						}
						
						function toggleItemVisibility(item_id)
						{
						if (!(layer = MM_findObj(item_id))) return;
						if(layer.className != "rpr_popup_inner_div_expanded")
						{
						layer.style.visibility = "visible";
						layer.className = "rpr_popup_inner_div_expanded";
						}
						else
						{
						layer.style.visibility = "hidden";
						layer.className = "rpr_popup_inner_div";
						}
						}
						</script>
						
						<style type="text/css">
						
						/*h2 {color: green}*/
						.rpr_header {margin: 0em 0em 0em 0em;
						padding: 0em 0em 0em 0em;}
						.rpr_title { font-weight: bold; 
						             font-size: 90%}
						.rpr_title_date { font-weight: lighter; }
						
						.rpr_item_list {
						padding: 0em 1em 0em 1em;
						margin: 0em 1em 0em 1em;}
						.rpr_item { list-style-type: none; 
						}
						.rpr_item_title {
						margin: 0em 0em 0em 0em;
						padding: 0em 0em 0em 0em;}
						
						
						.rpr_item_link { }
						.rpr_expand_switch { font-weight: bolder;
						text-decoration: none;
						/*border: 1px solid black*/ }
						
						.rpr_popup_inner_div { 
						padding: 0.5em;
						border: 2px outset #324C48;
						background-color: #f9f9f9;
						visibility: hidden;
						position: absolute;
						left: 4em;
						top: 1.5em;
						width: 350px;
						-moz-opacity: 0.95; filter: alpha(opacity=95);
						z-index: 1;
						}
						
						.rpr_popup_inner_div_expanded {
						margin:  0.5em 1em 0em 1em;
						padding: 0.5em;
						border: 2px outset #324C48;
						background-color: #f9f9f9;
						} 

						.rpr_popup_outer_div { 
						 position: relative;
						}
						
						
						</style>
						
						';

		}
	}

	/**
	@param $show_empty_sources Should we show the feed information even if no news item has be selected?
	*/
	function get_rss_html($number_of_items_to_display = 20, $last_visit = null, $show_empty_sources = true)
	{
		$html = '';
		if (($rss_result = $this->get_rss_info($number_of_items_to_display, $last_visit)) !== null)
		{
			foreach ($rss_result as $feed_id => $feed)
			{
				if (count($feed['items']) > 0 || $show_empty_sources == true)
				{
					$feed_html = '';
					$dhtml_id = "feed_".md5($feed_id);
					$feed_html .= "<p class='rpr_header'>"._('Source: ');
					$feed_html .= "<span class='rpr_title' onMouseOver=\"changestyle('$dhtml_id','visible');\" onMouseOut=\"changestyle('$dhtml_id','hidden');\">\n";
					if (!empty ($feed['link']))
					{
						$feed_html .= "<a class='y' href='".$feed['link']."'>".$feed['channel']['title']."</a>";
					}
					else
					{
						$feed_html .= $feed['channel']['title'];
					}
					$feed_html .= "</span>\n";
					$feed_html .= "</p>\n";

					$feed_html .= "<div class='rpr_popup_outer_div'>\n";
					$feed_html .= "<div id='$dhtml_id' class='rpr_popup_inner_div'>\n";
					$feed_html .= "<p class='rpr_text'>{$feed['channel']['title']}  </p>\n";
					if (!empty ($feed['channel']['description']))
					{
						$description = strip_tags($feed['channel']['description'], "<p><a><img><b><i>");
						$feed_html .= "<p class='rpr_text'>{$description}</p>\n";
					}
					$feed_html .= "<p class='rpr_text'>{$feed_id}</p>\n";
					$feed_html .= "</div></div>\n";

					$feed_html .= "<ul class='rpr_item_list'>\n";

					foreach ($feed['items'] as $item)
					{
						if ($item['rpr_is_today'])
						{
							$display_date = _("Today");
						}
						else
							if ($item['rpr_realphpdate'] != -1)
							{
								setlocale(LC_TIME, "fr_CA");
								$display_date = strftime("%x", $item['rpr_realphpdate']);
							}
							else
							{
								$display_date = '';
								//$display_date = "Estimated: ".strftime("%x", $item['rpr_computed_phpdate']);
							}

						$dhtml_id = "summary_".mt_rand(1, 10000);
						$feed_html .= "<li class='rpr_item'>\n";
						$feed_html .= "<p class='rpr_item_title'>\n";
						$item['rpr_is_today'] ? $switch_content = '-' : $switch_content = '+';

						$feed_html .= "<a class='rpr_expand_switch' href='#dummy' onClick=\"toggleItemVisibility('$dhtml_id'); if(this.innerHTML=='+'){this.innerHTML='-';}else{this.innerHTML='+';}\">$switch_content</a> ";
						$feed_html .= "<span class='rpr_title_date'>$display_date</span>";
						$feed_html .= "<span class='rpr_title' onMouseOver=\"changestyle('$dhtml_id','visible');\" onMouseOut=\"changestyle('$dhtml_id','hidden');\">\n";
						if (!empty ($item['link']))
						{
							$feed_html .= "<a class='rpr_item_link' href='{$item['link']}'>{$item['title']}</a>";
						}
						else
						{
							$feed_html .= "{$item['title']}";
						}
						$feed_html .= "</span></p>\n";
						$feed_html .= "<div class='rpr_popup_outer_div'>\n";
						$class = 'rpr_popup_inner_div';
						$item['rpr_is_today'] ? $class = 'rpr_popup_inner_div_expanded' : $class = 'rpr_popup_inner_div';
						$style = '';
						//$item['rpr_is_today'] ? $style = 'z-index: 1000;' : $style = '';
						$item['rpr_is_today'] ? $script= "changestyle('$dhtml_id','visible');" : $script = '';
						$feed_html .= "<div class='$class' style='$style' id='$dhtml_id'>\n";
						//$feed_html .= "<script type=\"text/javascript\">$script</script>\n";
						$feed_html .= "<p class='rpr_text'>{$item['rpr_author']} ({$feed['channel']['title']}) $display_date</p>\n";
						$summary = strip_tags($item['rpr_content'], "<br><p><a><img><b><i>");
						$feed_html .= "<p class='rpr_text'>{$summary}</p></div>\n";
						$feed_html .= "</div>\n";
						$feed_html .= "</li>\n";
					}
					$feed_html .= "</ul>\n";

					$html .= $feed_html;
				}
			}
		}
		return $this->get_rss_header().$html;
	}
}
?>