<?php
  // $Id$
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
  /**@file index.php Displays the portal page
   * @author Copyright (C) 2004 Benoit Grégoire et Philippe April
   */

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/SmartyWifidog.php';
require_once BASEPATH.'classes/Session.php';

if(CONF_USE_CRON_FOR_DB_CLEANUP == false)
  {
    garbage_collect();
  }

$smarty = new SmartyWifidog;
$session = new Session;

include BASEPATH.'include/language.php';

$portal_template = $_REQUEST['gw_id'] . ".html";
$node_id = $db->EscapeString($_REQUEST['gw_id']);
$db->ExecSqlUniqueRes("SELECT * FROM nodes WHERE node_id='$node_id'", $node_info);
if($node_info==null)
  {
    $smarty->assign('hotspot_name', UNKNOWN_HOSTPOT_NAME);
    $hotspot_rss_url = UNKNOWN_HOTSPOT_RSS_URL;
  }
 else
   {
     $smarty->assign('hotspot_name', $node_info['name']);
     $hotspot_rss_url =  $node_info['rss_url'];
   }

/* Find out who is online */
$db->ExecSql("SELECT users.user_id FROM users,connections " .
	     "WHERE connections.token_status='" . TOKEN_INUSE . "' " .
	     "AND users.user_id=connections.user_id AND connections.node_id='$node_id' "
	     ,$users, false);
if ($users != null) {
	$smarty->assign("online_users", $users);
}

if(RSS_SUPPORT)
  {
    //      $old_error_level = error_reporting(E_ERROR);
    define('MAGPIE_DIR', BASEPATH.MAGPIE_REL_PATH);
    //    require_once(MAGPIE_DIR.'rss_fetch.inc');
    //    define('MAGPIE_DEBUG', 0);
    require_once BASEPATH.'classes/RssPressReview.inc';
    $press_review=new RssPressReview;
    $tokens = "/[\s,]+/";
    $network_rss_sources = NETWORK_RSS_URL;
    $network_rss_html = null;
    if(!empty($network_rss_sources))
      {

	$extract_array=null;
	$extract_array = preg_split($tokens, $network_rss_sources);
	//print_r($extract_array);
	foreach($extract_array as $source)
	  {
	    $network_rss_sources_array[] = array('url' => $source, 'default_publication_interval' => 7*24*3600);
	  }
	$network_rss_html=$press_review->get_rss_html($network_rss_sources_array, 5);
      }
		     
    $hotspot_rss_html=null;
    if(!empty($hotspot_rss_url))
      {
	$extract_array=null;
	$extract_array = preg_split($tokens, $hotspot_rss_url);
	//print_r($extract_array);
	foreach($extract_array as $source)
	  {
	    $hotspot_rss_sources_array[] = array('url' => $source, 'default_publication_interval' => 7*24*3600);
	  }
	$hotspot_rss_html=$press_review->get_rss_html($hotspot_rss_sources_array, 5);     
      }
    /**
     @return the generated html or the error message or an empty string if called without a URL.
    */
    function generate_rss_html ( $url ) {
      $rss_html='';
      if(!empty($url))
	{
	  $rss = fetch_rss( $url );
	  $rss_html='';
	  if ( !$rss )
	    {
	      $rss_html .= _("Error: ") . magpie_error() ;
	    }
	  else 
	    {
	      //$rss->show_channel();
	      //$rss->show_list();
	      $rss_html .= "<p>"._('Channel: ') . $rss->channel['title'] . "</p>\n";
	      $rss_html .= "<ul>\n";
	      foreach ($rss->items as $item)
		{
		  //echo '<pre>'; print_r($item); 	echo '</pre>';
		  $href = $item['link'];
		  $title = $item['title'];
		  $summary =  $item['summary'];	
		  $rss_html .= "<li><emp><a href=$href>$title</a></emp> $summary</li>\n";
		}
	      $rss_html .= "</ul>\n";
	    }
	}
      return $rss_html;
    }


    //$network_rss_html=generate_rss_html(NETWORK_RSS_URL);    
    //echo $networkrss_html;
    $smarty->assign("network_rss_html", $network_rss_html);

    
    //$hotspot_rss_html=generate_rss_html($hotspot_rss_url);    
    //echo $hotspot_rss_html;
    $smarty->assign("hotspot_rss_html", $hotspot_rss_html);
    //   error_reporting($old_error_level);
  }

if (isset($session)) {
    $smarty->assign("original_url_requested", $session->get(SESS_ORIGINAL_URL_VAR));
}

$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."header.html");
$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."header_portal.html");
if (is_file(NODE_CONTENT_PHP_RELATIVE_PATH.PORTAL_PAGE_NAME)) {
    $smarty->display(NODE_CONTENT_SMARTY_PATH.PORTAL_PAGE_NAME);
} else {
    $smarty->display(DEFAULT_CONTENT_SMARTY_PATH.PORTAL_PAGE_NAME);
}
$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."footer_portal.html");
$smarty->display(DEFAULT_CONTENT_SMARTY_PATH."footer.html");
?>
