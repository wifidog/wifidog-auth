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
  /**@file sendfile.php
   * Node owner upload file
   * @author Copyright (C) 2005 Pascal Leclerc
   */

/*
  Notes/TODO :
    Valider les donnees passees en arguments
    Utiliser smarty
    Permettre la recherche de user directement dans l'interface owner
    156.486 / 5120.0 MB Used
	
*/

define('BASEPATH','../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Style.php';
require_once BASEPATH.'classes/Security.php';
require_once BASEPATH.'classes/Session.php';

$session  = new Session();
$security = new Security();
$smarty   = new SmartyWifidog;

include BASEPATH.'include/language.php';

$user_id = $session->get(SESS_USERNAME_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

empty($_REQUEST['action'])  ? $action  = '' : $action  = $_REQUEST['action'];
empty($_REQUEST['node_id']) ? $node_id = '' : $node_id = $_REQUEST['node_id'];
empty($_REQUEST['delfile']) ? $delfile = '' : $delfile = $_REQUEST['delfile'];

$user_id = $session->get(SESS_USERNAME_VAR);
$smarty->assign("user_id", $user_id); // DEBUG

// TODO: Remplacer les constantes definit dans config.php pour $filesArray
$filesArray = array (
   "0" => array('filename' => 'hotspot_logo_banner.jpg', 'file_exists' => 0),
   "1" => array('filename' => 'hotspot_logo.jpg',        'file_exists' => 0),
   "2" => array('filename' => 'login.html',              'file_exists' => 0),
   "3" => array('filename' => 'portal.html',             'file_exists' => 0),
   "4" => array('filename' => 'stylesheet.css',          'file_exists' => 0)
);

// Error checking before user can upload files
if(!is_writable(BASEPATH.LOCAL_CONTENT_REL_PATH)) {
     /* TODO Detailler l'erreur :
          -Print absolute PATH directory
          -Print current uid/gid
          -Print needed uid/gid
      */
    $uid = posix_getuid();
    $smarty->assign("error_message", "Ecriture impossible dans le repertoire ".BASEPATH.LOCAL_CONTENT_REL_PATH." (UID=$uid)");
    $smarty->display("admin/templates/owner_display.html");
    exit();
}

if ("$delfile" == "submit") { // Submit all files
    // Create node directory in local_content
    if (!file_exists(BASEPATH.LOCAL_CONTENT_REL_PATH."$node_id")) {
        mkdir(BASEPATH.LOCAL_CONTENT_REL_PATH."$node_id");  // TODO : Add error checking
    }
    
    foreach($filesArray as $fileArray) {
        $filename = $fileArray['filename'];
        $filename_underscore = str_replace('.', '_', $filename);

        // Source and destination file (with PATH) and name (in tmp directory). @ is use to remove useless PHP notice message.
        $source              = @$_FILES["$filename_underscore"]['tmp_name'];        
        $destination         = BASEPATH.LOCAL_CONTENT_REL_PATH."$node_id/$filename";  // Destination file PATH and name (local_content)
        //echo "S=$source D=$destination<BR>";
        if (empty($source)) // Skip empty input file submission
            continue;

        // TODO : Display file upload success or error.
        if (move_uploaded_file($source, $destination)) {
            //echo "File is valid, and was successfully uploaded.<BR>";
        } 
        else {
            $smarty->assign("error_message", 'Possible file upload attack!');
        }
    }
}
else { // Delete only if the filename is defined and include in $filesArray
    foreach($filesArray as $fileArray) {
        if ($fileArray['filename'] == "$delfile") {
            $filename = $fileArray['filename'];
            $source = BASEPATH.LOCAL_CONTENT_REL_PATH."$node_id/$filename";
            //echo "DELETE SOURCE=$source<BR>";
            unlink($source);    
        }
    }
}

if ("$action" == "uploadform") {
    $security->requireOwner($node_id);
    $inc = 0;
    foreach($filesArray as $fileArray) {
        $filename = $fileArray['filename'];
        if (file_exists(BASEPATH.LOCAL_CONTENT_REL_PATH."$node_id/$filename")) {
            $filesArray[$inc]['file_exists'] = 1;
        }
        ++$inc;
    }

    $smarty->assign("file_list", $filesArray);
    $smarty->assign("node_id", $node_id);
    $smarty->display("admin/templates/owner_upload.html");
}    
else {
    $db->ExecSql("SELECT nodes.node_id,name FROM nodes NATURAL JOIN node_owners WHERE node_owners.user_id='$user_id'", $node_results, false);

    if (is_array($node_results)) {
        foreach($node_results as $node_row) {
            $smarty->append("node_list", $node_row);
        }
    }
    else {
        $smarty->assign("error_message", 'You are not a hotspot owner');
    }
    $smarty->assign("node_id", $node_id);
    $smarty->display("admin/templates/owner_display.html");
}
  


?>
