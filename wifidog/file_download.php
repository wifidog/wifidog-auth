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
/**@file file_download.php
 * @author Copyright (C) 2005 François Proulx, Technologies Coeus inc.
*/

define('BASEPATH', './');
require_once 'include/common.php';

if (!empty ($_REQUEST['file_id']))
{
	global $db;
	$sql = "SELECT * FROM files WHERE files_id = '".$_REQUEST['file_id']."'";
	$db->execSqlUniqueRes($sql, $file_row, false);
	ob_clean();
	if ($file_row && $file_row['binary_data'])
	{
		header('Content-Type: '.$file_row['mime_type']);
		header('Content-Disposition: attachment; filename="'.$file_row['filename'].'"');
		echo $db->UnescapeBinaryString($file_row['binary_data']);
	}
}
?>