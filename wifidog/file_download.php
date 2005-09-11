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
require_once BASEPATH.'include/common.php';

if (!empty ($_REQUEST['file_id']))
{
	global $db;
    $file_id = $db->EscapeString($_REQUEST['file_id']);
    $sql = "SELECT * FROM files WHERE files_id = '$file_id'";
    $db->ExecSqlUniqueRes($sql, $file_row, false);

    if ($file_row && $file_row['data_blob'])
    {
        //headers to send to the browser before beginning the binary download
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-Type: '.$file_row['mime_type']);
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        if(strpos($file_row['mime_type'], "image") === false)
            header('Content-Length: '.$file_row['local_binary_size']); //this is the size of the zipped file
        header('Keep-Alive: timeout=15, max=100');
        header('Content-Disposition: inline; filename="'.$file_row['filename'].'"');
        
        $db->ReadAndFlushLargeObject($file_row['data_blob']);
    }
}
?>