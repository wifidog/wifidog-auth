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
/**@file GenericObject.php
 * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>,
 * Technologies Coeus inc.
 */
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'classes/Content.php';
/*function __autoload($class_name) {
   require_once BASEPATH.'classes/'.$class_name . '.php';
}*/

/** Any object that implement this interface can be administered in a generic way. */
interface GenericObject
{
	/** Get an instance of the object
	 * @see GenericObject
	 * @param $id The object id
	 * @return the Content object, or null if there was an error (an exception is also thrown)
	 */
	static public function getObject($id);
	/** Create a new Content object in the database 
	 * @see GenericObject
	 * @return the newly created object, or null if there was an error
	 */
	static function createNewObject();

	/** Retreives the id of the object 
	 * @return The id, a string */
	public function getId();
	
	/** Retreives the admin interface of this object.
	 * @return The HTML fragment for this interface */
	public function getAdminUI();

	/** Process admin interface of this object.
	*/
	public function processAdminUI();

	/** Delete this Object form it's storage mechanism 
	 * @param &$errmsg Returns an explanation of the error on failure
	 * @return true on success, false on failure or access denied */
	public function delete(& $errmsg);

} // End interface
?>