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
  /**@file
   * @author Copyright (C) 2004 Alexandre Carmel-Veilleux <acv@acv.ca>
   */
define('BASEPATH','../');
require_once BASEPATH.'include/common.php';

/* I still put the includes because hopefully, if the database is down or
   something like that, they might fail... Wouldn't want the wifidog to
   not notice a failure! */

/* TODO: Perhaps, we can update something in the DB to signify that the
   Gateway checked in and then the DB can be later queried and use those
   timestamps to know which hotspots are up. */

echo "Pong";
?>
