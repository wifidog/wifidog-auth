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
  /**@file Style.php
   * @author Copyright (C) 2004 Technologies Coeus inc.
   */
  /*Prevent caching*/
Header("Cache-control: private, no-cache, must-revalidate");
Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
Header("Pragma: no-cache");
Header("Content-Type: text/html; charset=iso-8859-1");
require_once BASEPATH.'include/common.php';

/** Style contains functions managing headers, footers, stylesheet, etc.
 */
class Style
{
  /**Display HTML headers
   * @param $titre Title of the page
   * @param $stylesheet stylesheet to include.
   * @param $prevent_cache Should the browsers and proxies be prevented from caching this content?
   * @return string to display in the page.
   */
  function GetHeader($titre)
  {
    $retval = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n";
    $retval.= "<html>\n";
    $retval.= "<head>\n";
    $retval.= "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>\n";
    $retval.= "<meta http-equiv='Pragma' CONTENT='no-cache'>\n";
    $retval.= "<meta http-equiv='Expires' CONTENT='-1'>\n";
    $retval.= "<link rel='stylesheet' href='".STYLESHEET_URL."' type='text/css'>\n";
    $retval.= "<title>$titre</title>\n";
    $retval.= "</head>\n";
    $retval.= "<body>\n";
    global $starttime;
    $starttime = microtime();

    return $retval;
  }

  /**Affiche le pied de page HTML
   * @return string chaine à afficher dans la page.
   */
  function GetFooter()
  {
     $retval = "";
    /*
     global $starttime;
     global $sql_total_time;
     global $sql_num_select_querys;
     global $sql_num_select_unique_querys;
     global $sql_num_update_querys;
     $parts_of_starttime = explode(' ', $starttime);
     $starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
     //echo "starttime: $starttime <br />\n";
     $endtime = microtime();
     $parts_of_endtime = explode(' ', $endtime);
     $endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
     //echo "endtime: $endtime <br />\n";
     $timetaken = $endtime - $starttime;
     //echo "timetaken: $timetaken <br />\n";

     $display_sql_percent = number_format(100 * ($sql_total_time / $timetaken), 0)."%";
     $display_timetaken = number_format($timetaken, 3); // optional
     $display_sql_total_time = number_format($sql_total_time, 3); // optional
     $sql_num_querys = $sql_num_select_querys + $sql_num_select_unique_querys + $sql_num_update_querys;


     $retval.= "<div class='content'>\n";
     $retval.= "<P>Temps écoulé: $display_timetaken seconde(s) dont $display_sql_percent ($display_sql_total_time seconde(s)) pour les $sql_num_querys requêtes SQL ($sql_num_select_querys select, $sql_num_select_unique_querys select valeur unique, $sql_num_update_querys modifications)</P>\n";
     $retval.= "</div>\n";
    */
    $retval.= "</body>\n";
    //Work around IE cache 64k buffer bug
    //$retval.= '<head><meta http-equiv="Pragma" CONTENT="no-cache"><meta http-equiv="Expires" CONTENT="-1"></HEAD>';
    $retval.= "</html>";

    return $retval;
  }

} /* end class Style */
?>
