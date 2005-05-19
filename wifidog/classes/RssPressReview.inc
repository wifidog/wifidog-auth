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
   * @author Copyright (C) 2004 Benoit Gr�goire (http://benoitg.coeus.ca/) et Technologies Coeus inc.
   */
  //define('MAGPIE_DIR', './magpie/');
require_once(MAGPIE_DIR.'rss_fetch.inc');
require_once(MAGPIE_DIR.'rss_utils.inc');
define('MAGPIE_DEBUG',0);
define('DEFAULT_PUBLICATION_INTERVAL',(1*24*3600));
define('OUTPUT_ENCODING','UTF-8');
//mb_detect_order("UTF-8, ISO-8859-1, ASCII");

class RssPressReview
{
    /**
      * @return the date in timestamp format of -1 if unavailable
      */
    static private function get_item_date($item, $debug = 0) {
        $retval = -1;
        if(!empty($item['dc']['date'])) {
            $datestr = $item['dc']['date'];
        } else if (!empty($item['pubdate'])) {
            $datestr = $item['pubdate'];
        } else if (!empty($item['date'])) {
            $datestr = $item['date'];
        } else if (!empty($item['created'])) {
            $datestr = $item['created'];
        } else {
            if ($debug) {
                echo "<p>get_item_date(): No date present!</p>";
            }
            $datestr = null;
        }
	
        if ($datestr == null) {
            $retval = -1;
        } else {
            if ($debug) {
                echo "<p>get_item_date(): String to convert: $datestr</p>";	
            }
	
            $retval = parse_w3cdtf($datestr);
            if($retval == -1) {
                $retval = strtotime($datestr);
            }
		
            if ($debug) {
                if ($retval == -1) {
                    echo "<p>get_item_date(): Conversion of $datestr failed!</p>";	
                } else {
                    echo "<p>get_item_date(): Conversion succeded</p>";	
                    setlocale(LC_TIME, "fr_CA");
                    echo strftime("%c", $retval);
                }
            }
        }

        if ($debug) {
            echo "<p>get_item_date(): Retval: $retval</p>";
        }
        return $retval;
    }
    
    /** Inverted comparaison function for adjusted dates (so that the most recent are first */
    private function cmp($a, $b) {
        if ($a['adjusted_date'] == $b['adjusted_date']) {
            return 0;
        }
        return ($a['adjusted_date'] > $b['adjusted_date']) ? -1 : 1;
    }

    /**
      * @return The converted string
      */
    private function convert_string_encoding($string) {
     $detected_encoding = mb_detect_encoding($string);
     if(empty($detected_encoding))
     {
     	$input_encoding='ISO-8859-1';
     }
     else
     {
     	$input_encoding=$detected_encoding;
     }
     //echo "<p>convert_string_encoding() detected_encoding=$detected_encoding, input_encoding=$input_encoding, OUTPUT_ENCODING=".OUTPUT_ENCODING."</p>";
     if ($input_encoding!=OUTPUT_ENCODING)
     {
     $string = mb_convert_encoding($string, OUTPUT_ENCODING, $input_encoding);
     }
        return $string;
    }

    /** This is the static comparing function to sort rss items in chronological order: */
    static function cmp_date_item($a, $b) {
        $a_date=self::get_item_date($a);
        $b_date=self::get_item_date($b);
        if ($a_date == $b_date) {
            return 0;
        }
        /*echo "cmp_date_item(): a:$a_date, b:$b_date ";
        echo ($a_date < $b_date) ? +1 : -1;*/
        return ($a_date < $b_date) ? +1 : -1;
    }

  /*
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
  /**
   @param $rss_sources is an array of arrays, each of chich must contain:
   [0]['url']=Url of the rss feed
   [0]['default_publication_interval']= The average interval between publication of news items for this source (in seconds).  Will be used for calculations if the date of each news for the source cannot be determined
   @param $number_of_items_to_display The total number of items to display.
   @return The formatted html of the press review
  */		     
    public function get_rss($rss_sources, $number_of_items_to_display = 20) {
        $old_error_level = error_reporting(E_ERROR);
        $item_date_array = array();
        $item_date_array_index = 0;

        foreach ($rss_sources as $rss_sources_key => $rss_source) {
            $i = 0;
            $publication_interval_total = null;
            /* running total of the difference between the date of the current item and the previous one */
            $average_publication_interval = null;
            $previous_item_date = null;
			if(trim($rss_source['url'])=="")
				continue;
            $rss = fetch_rss( $rss_source['url'] );
            if (!$rss) {
                throw new Exception(_(magpie_error()));
            } else {
                //$rss_sources[$rss_sources_key]['rss'] = $rss;
                //$rss->show_channel();
                //$rss->show_list();
 
                /* Sort the array in chronological order */

                /* foreach  ($rss->items as $item) {echo "$item[title] ". self::get_item_date($item) . "<br>\n";}*/
                if (!uasort($rss->items, array("RssPressReview", "cmp_date_item"))) {
                    throw new Exception(_('uasort() failed'));
                }
                /* foreach  ($rss->items as $item) {echo "$item[title] ". self::get_item_date($item) . "<br>\n";}*/
                
                /* Calculate the publication interval for this source */
                foreach ($rss->items as $item) {
                    $date = self::get_item_date($item);
 
                    if ($date == -1) {
                        /*
                         *  If we do not know the date, for statistics purposes,
                         *  we will set the date as if a news item as published
                         *  every default_publication_interval, starting now.
                         */
                        $date = time() - ($i * $rss_sources[$rss_sources_key]['default_publication_interval']);
                    }
 
                    if ($i > 0) {
                        $publication_interval_total += $previous_item_date - $date;
                    }
		      
                    $previous_item_date = $date;
 
                    /* Memorize each date, and publication intervals so we can determine the "oldest" item to publish */

                    $item_date_array[$item_date_array_index]['date']=$date;
                    $item_date_array[$item_date_array_index]['rss_sources_key']=$rss_sources_key;
                    $item_date_array_index++;
                    $i++;
                }// End foreach items


                if ($i >= 2 && $publication_interval_total != 0) {
                    $average_publication_interval = $publication_interval_total/($i-1);
                    $rss_sources[$rss_sources_key]['average_publication_interval'] = $average_publication_interval;
                } else {
                    $rss_sources[$rss_sources_key]['average_publication_interval'] = $rss_sources[$rss_sources_key]['default_publication_interval'];
                }
                //echo "<p>$i items, average_publication_interval (days) = ". $rss_sources[$rss_sources_key]['average_publication_interval']/(3600 * 24) . "</p>\n";
 
                $rss_sources[$rss_sources_key]['number_of_items'] = $i;
            }
        }// End foreach rss feeds

    
        $all_feed_publication_interval_total = null;
        reset($rss_sources);
        foreach ($rss_sources as $rss_source) {
            $all_feed_publication_interval_total += $rss_source['average_publication_interval'];
        }
        $all_feed_publication_interval = $all_feed_publication_interval_total / count($rss_sources);
 
        for ($i = 0; isset($item_date_array[$i]); $i++) {
            $average_publication_interval = $rss_sources[$item_date_array[$i]['rss_sources_key']]['average_publication_interval'];
            $adjust_factor = $all_feed_publication_interval / $average_publication_interval;
            $item_date_array[$i]['adjusted_date'] = time() - ((time() - $item_date_array[$i]['date']) * $adjust_factor);
        }
    
        usort($item_date_array, array("RssPressReview", "cmp"));

        if (count($item_date_array) < $number_of_items_to_display) {
            $number_of_items_to_display = count($item_date_array);
        }

        $min_adjusted_date_to_display = $item_date_array[$number_of_items_to_display - 1]['adjusted_date'];

        /************** Now we actually display the feeds **************/
        $sidx = 0;
        reset($rss_sources);
        foreach ($rss_sources as $rss_sources_key => $rss_source) {
        	if(trim($rss_source['url'])=="")
        		continue;
        		
            $rss = fetch_rss($rss_source['url']);
            if (!$rss) {
                throw new Exception(_(magpie_error()));
            } else {
                unset($rss_info_tmp);
                $rss_info_tmp['title'] = $rss->channel['title'];
                $rss_info_tmp['link'] = $rss->channel['link'];
                $rss_info_tmp['encoding'] = $rss->encoding;

                $i = 0;
                /* Sort the items by date, so we get the most recent first */
                if (!uasort($rss->items, array("RssPressReview","cmp_date_item"))) {
                    throw new Exception(_('uasort() failed'));
                }

                $rss_info_tmp['items'] = array();
                foreach ($rss->items as $item){
                    $date = self::get_item_date($item);
                    $realdate = $date;
                    if ($date == -1) {
                        /*
                         * If we do not know the date, for statistics purposes,
                         * we will set the date as if a news item as published
                         * every default_publication_interval, starting now.
                         */
                        $date = time() - ($i * $rss_sources[$rss_sources_key]['default_publication_interval']);
                    }
 
                    $average_publication_interval = $rss_sources[$rss_sources_key]['average_publication_interval'];

                    $adjust_factor = $all_feed_publication_interval / $average_publication_interval;
                    $adjusted_date = time() - ((time() - $date) * $adjust_factor);
                    if ($adjusted_date >= $min_adjusted_date_to_display) {

                        $author = '';
                        if(!empty($item['dc']['creator'])) {
                            $author = trim($item['dc']['creator']);
                        } elseif (!empty($item['author'])) {
                            $author = trim($item['author']);
                        } elseif (!empty($item['author_name'])) {
                            $author = trim($item['author_name']);
                        }
                       
                        array_push($rss_info_tmp['items'], array(
                                "title"     => $item['title'],
                                "summary"   => $item['summary'],
                                "author"    => $author,
                                "date"      => $realdate,
                                "link"      => $item['link'],
                                "id"        => "summary_" . mt_rand(1, 10000). "_" . $sidx
                            ));
                    }
                    $i++;
                    $sidx++;
                }// End foreach items
                $rss_info[] = $rss_info_tmp;
            }
        }// End foreach rss feeds
        
        return $rss_info;
        error_reporting($old_error_level);
    }

    function get_rss_header() {
        static $done_header = false;

        if (!$done_header) {
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

    public function get_rss_html($rss_sources, $number_of_items_to_display = 20) {
        $rss_result = $this->get_rss($rss_sources, $number_of_items_to_display);

        $html = '';
        foreach ($rss_result as $feed) {
        		$feed_html = '';
            $feed_html .= "<p class=textegris>"._('Source: ')."<b>";
            if(!empty($feed['link'])) {
                $feed_html .= "<a class='y' href='".$feed['link']."'>" . $feed['title'] . "</a>";
            } else {
                $feed_html .= $channel_title;
            }
            $feed_html .= "</b></p>\n";
            $feed_html .= "<ul>\n";

            foreach ($feed['items'] as $item) {
                if ($item['date'] != -1) {
                    setlocale("LC_TIME", "fr_CA");
                    $display_date = strftime("%x", $item['date']);
                } else {
                    $display_date = '';
                }

                $dhtml_id = "summary_".mt_rand(1, 10000). "_".$sidx;
                $feed_html .= "<li>\n";
                $feed_html .= "<span class='textegrispetit'>$display_date</span>";
                $feed_html .= "<span class='y' onMouseOver=\"changestyle('$dhtml_id','visible');\" onMouseOut=\"changestyle('$dhtml_id','hidden');\">\n";
                if ($item['link']) {
                    $feed_html .= "<a class='y' href='{$item['link']}'><b>{$item['title']}</b></span></a><br />";
                } else {
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
            
            if($feed['encoding'] != OUTPUT_ENCODING)
            {
            		$html .= mb_convert_encoding($feed_html, OUTPUT_ENCODING, $feed['encoding']);
            }
            else
            {
            		$html .= $feed_html;
            }
        }
        return $this->get_rss_header().$html;
    }
}
?>
