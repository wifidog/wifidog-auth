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
  //define('MAGPIE_DIR', './magpie/');
require_once(MAGPIE_DIR.'rss_fetch.inc');
require_once(MAGPIE_DIR.'rss_utils.inc');
define('MAGPIE_DEBUG',0);
define('DEFAULT_PUBLICATION_INTERVAL',(1*24*3600));
define('OUTPUT_ENCODING','ISO-8859-1');
//mb_detect_order("UTF-8, ISO-8859-1, ASCII");
?>
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
</script>
<?php

class RssPressReview
{
  /**
   @return the date in timestamp format of -1 if unavailable
  */
  private function get_item_date ( $item, $debug = 0 )
  {
    $retval = -1;
    if(!empty($item['dc']['date']))
      {
	$datestr = $item['dc']['date'];
      }
    else if (!empty($item['pubdate']))
      {
	$datestr = $item['pubdate'];
      }
    else if (!empty($item['date']))
      {
	$datestr = $item['date'];
      }
    else if (!empty($item['created']))
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
		
    if ($datestr==null)
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
	if($retval == -1)
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
    
  /** Inverted comparaison function for adjusted dates (so that the most recent are first */
  private function cmp($a, $b)
  {
    if ($a['adjusted_date'] == $b['adjusted_date']) {
      return 0;
    }
    return ($a['adjusted_date'] > $b['adjusted_date']) ? -1 : 1;
  }

  /**
   * @return The converted string
   */
  private function convert_string_encoding($string)
  {
    /*
     $input_encoding = mb_detect_encoding($string);
     //echo "<p>convert_string_encoding() input_encoding=$input_encoding, OUTPUT_ENCODING=".OUTPUT_ENCODING.", string: $string</p>";
     if ($input_encoding!=OUTPUT_ENCODING)
     {
     $string = mb_convert_encoding($string, OUTPUT_ENCODING, $input_encoding);
     }
    */
    return $string;
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
  public function get_rss_html($rss_sources, $number_of_items_to_display=20)
  {
    $old_error_level = error_reporting(E_ERROR);
    $item_date_array = array();
    $item_date_array_index = 0;
    
    foreach ($rss_sources as $rss_sources_key => $rss_source)
      {
	//echo "<h1>$rss_source[url]</h1>";
	$i = 0;
	$publication_interval_total = null; //running total of the difference between the date of the current item and the previous one
	$average_publication_interval = null;
	$previous_item_date;
	$rss = fetch_rss( $rss_source['url'] );
	if ( !$rss )
	  {
	    echo _("Error: ") . magpie_error() ;
	  }
	else 
	  {
	    //$rss_sources[$rss_sources_key]['rss']=$rss;
	    //$rss->show_channel();
	    //$rss->show_list();
	      
	    foreach ($rss->items as $item)
	      {
		$date = $this->get_item_date($item);
		      
		if ($date == -1)
		  {
		    //If we do not know the date, for statistics purposes, we will set the date as if a news item as published every default_publication_interval, starting now.
		    $date = time() - ($i * $rss_sources[$rss_sources_key]['default_publication_interval']);
		  }
		      
		if($i >0)
		  {
		    $publication_interval_total += $previous_item_date - $date;
		  }
		      
		$previous_item_date = $date;
		      
		/* Memorize each date, and publication intervals so we can determine the "oldest" item to publish */

		$item_date_array[$item_date_array_index]['date']=$date;
		$item_date_array[$item_date_array_index]['rss_sources_key']=$rss_sources_key;
		$item_date_array_index++;
		$i++;
	      }// End foreach items
	    if($i >= 2  && $publication_interval_total != 0)
	      {
		$average_publication_interval = $publication_interval_total/($i-1);
		$rss_sources[$rss_sources_key]['average_publication_interval']=$average_publication_interval;
	      }
	    else
	      {
		$rss_sources[$rss_sources_key]['average_publication_interval']=$rss_sources[$rss_sources_key]['default_publication_interval'];
	      }
	    //echo "<p>$i items, average_publication_interval (days) = ". $rss_sources[$rss_sources_key]['average_publication_interval']/(3600 * 24) . "</p>\n";
	      
	    $rss_sources[$rss_sources_key]['number_of_items']=$i;	  
	  }
      }// End foreach rss feeds
    

    $all_feed_publication_interval_total = null;
    reset($rss_sources);
    foreach ($rss_sources as $rss_source)
      {
	$all_feed_publication_interval_total+= $rss_source['average_publication_interval'];
      }
    $all_feed_publication_interval = $all_feed_publication_interval_total / count($rss_sources);
    //echo "<p>all_feed_publication_interval (days) = ". $all_feed_publication_interval/(3600 * 24) . "</p>\n";
	      
    for($i=0; isset($item_date_array[$i]); $i++)
      {
	$average_publication_interval = $rss_sources[$item_date_array[$i]['rss_sources_key']]['average_publication_interval'];
	$adjust_factor = $all_feed_publication_interval/$average_publication_interval;
	$item_date_array[$i]['adjusted_date']= time()- ((time()-$item_date_array[$i]['date'])*$adjust_factor);
      }
    
    //echo "<pre>\n";
    //print_r($rss_sources);
    //print_r($item_date_array);

    usort($item_date_array, "$this->cmp");
    //print_r($item_date_array);
    //echo "</pre>\n";

    if(count($item_date_array)<$number_of_items_to_display)
      {
	$number_of_items_to_display = count($item_date_array);
      }

    $min_adjusted_date_to_display = $item_date_array[$number_of_items_to_display-1]['adjusted_date'];

    /************** Now we actually display the feeds **************/
    $sidx=0;
    foreach ($rss_sources as $rss_sources_key => $rss_source)
      {
	//echo "<h1>$rss_source[url]</h1>";
	$rss = fetch_rss( $rss_source['url'] );
	if ( !$rss )
	  {
	    echo _("Error: ") . magpie_error() ;
	  }
	else 
	  {
	    //$rss->show_channel();
	    //$rss->show_list();
	    $rss_html = '';
	    $rss_header='';
	    if ($rss_sources_key!=0)
	      {
		$rss_header .= "<hr />\n";
	      }
	    $rss_header .= "<p class=textegris>"._('Source: ')."<b>";
	    $channel_title = $this->convert_string_encoding($rss->channel['title']);
	    if(!empty($rss->channel['link']))
	      {
		$rss_header .= "<a class='y' href='".$rss->channel['link']."'>$channel_title</a>";
	      }
	    else
	      {
		$rss_header .= $channel_title;
	      }
	    $rss_header .= "</b></p>\n";
	    $rss_header .= "<ul>\n";
	    $header_is_displayed = false;
	    $i = 0;
	    foreach ($rss->items as $item)
	      {
		$date = $this->get_item_date($item);
		$realdate = $date;
		if ($date == -1)
		  {
		    //If we do not know the date, for statistics purposes, we will set the date as if a news item as published every default_publication_interval, starting now.
		    $date = time() - ($i * $rss_sources[$rss_sources_key]['default_publication_interval']);
		  }
		      
		$average_publication_interval = $rss_sources[$rss_sources_key]['average_publication_interval'];
		$adjust_factor = $all_feed_publication_interval/$average_publication_interval;
		$adjusted_date = time()- ((time()-$date)*$adjust_factor);

		//echo "adjusted_date $adjusted_date, min_adjusted_date_to_display $min_adjusted_date_to_display<br>";
		if ($adjusted_date >= $min_adjusted_date_to_display)
		  {
		    if(!$header_is_displayed)
		      {
			$rss_html .= $rss_header;
			$header_is_displayed = true;
		      }
		    $href = $item['link'];
		    $title = $this->convert_string_encoding($item['title']);

		    $summary =  $this->convert_string_encoding($item['summary']);	

		    if($realdate!= -1)
		      {
			setlocale(LC_TIME, "fr_CA");
			$display_date= strftime("%x", $realdate);
		      }
		    else
		      {
			$display_date='';
		      }
	      
		    $author='';
		    if(trim($item['dc']['creator'])!='')
		      {
			$author = trim($item['dc']['creator']);
		      }
		    elseif (trim($item['author'])!='')
		      {
			$author = trim($item['author']);
		      }
		    elseif (trim($item['author_name'])!='')
		      {
			$author = trim($item['author_name']);
		      }
		       
		    if(!empty($author))
		      {
			$author = $this->convert_string_encoding($author);
			$author = "par $author";
		      }
		    $dhtml_id = "summary_".mt_rand(1, 10000). "_".$sidx;
		    $rss_html .= "<li>\n";
		    $rss_html .= "<span class='textegrispetit'>$display_date</span><a class='y' onMouseOver=\"changestyle('$dhtml_id','visible');\" onMouseOut=\"changestyle('$dhtml_id','hidden');\"  href='$href'>\n";
		    $rss_html .= "<b>$title</b><br />\n";
		    $rss_html .= "</a>\n";
		    $rss_html .= "<div style='z-index: 1000; position: relative'>\n";
		    $rss_html .= "<div style='z-index: 1000;' id='$dhtml_id' class='introduction'>
	<p>$author ($channel_title) $display_date</p>\n";
		    $rss_html .= "<p>$summary</p></div>\n"; 
		    $rss_html .= "</div>\n";
		    $rss_html .= "</li>\n";

		  }
		$i++;
		$sidx++;
	      }// End foreach items
	    if($header_is_displayed)
	      {
		$rss_html .= "</ul>\n";
	      }
	    return $rss_html;
		  
	  }
      }// End foreach rss feeds
    error_reporting($old_error_level);
  }

}

?>
