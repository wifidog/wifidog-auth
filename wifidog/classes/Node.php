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
  /**@file Node.php
   * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>
   */
   
require_once BASEPATH.'include/common.php';

/** Abstract a Node.  A Node is an actual physical transmitter. */
class Node{
  private $mRow;
  private $mId;
  
  /** Instantiate a node object 
   * @param $id The id of the requested node
   * @return a Node object, or null if there was an error
   */
  static function GetObject($id)
    {
      $object = null;
      $object = new self($id);
      return $object;
    }
  
  /** Create a new Node in the database 
   * @param $id The id to be given to the new node
   * @return the newly created Node object, or null if there was an error
   */
  static function CreateObject($id)
    {
      $object = null;
      $id_str = $db->EscapeString($id);
      $sql = "INSERT INTO nodes values (node_id) VALUES ('$id_str')";
      $db->ExecSqlUpdate($sql, false);
      $object = new self($id);
      return $object;
    }
  
/** @param $node_id The id of the node */
  function __construct($node_id)
  {
    $node_id_str = $db->EscapeString($node_id);
    $sql = "SELECT * from nodes WHERE node_id='$node_id_str'";
    $db->ExecSqlUniqueRes($sql, $row, false);
    if ($row==null)
      {
	throw new Exception(_("The id $node_id_str could not be found in the database"), "EXCEPTION_CREATE_OBJECT_FAILED");
      }
    $this -> mRow=$row;  
    $this -> mId=$row['node_id'];
  }//End class
  
/** Return the name of the node 
*/
  function GetName()
  {
    return $this -> mRow['name'];
  }
}// End class
?>
