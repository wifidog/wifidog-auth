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
  /**@file graph_registrations_cumulative.php
   * parameters:
   * $_REQUEST['graph_class']: The name of the class for which we want the graph
   * displayed.
   * @author Copyright (C) 2005 Philippe April
   */

define('BASEPATH',"../");

if(empty($_REQUEST['graph_class']))
{
	echo "You must specify the class of the graph you want";
}
else
{	
	$classname = $_REQUEST['graph_class'];
require_once BASEPATH.'classes/StatisticGraph/'.$classname.'.php';
$graph = call_user_func(array ($classname, 'getObject'), $classname);
$graph->showImageData();

}



?>