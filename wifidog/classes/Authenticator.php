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
  /**@file Authenticator.php
   * @author Copyright (C) 2005 Benoit Grégoire <bock@step.polymtl.ca>, Technologies Coeus inc.
   */

/** Abstract class to represent an authentication source */
abstract class Authenticator {
private mAccountOrigin;

 function __construct($account_orgin)
 {
   $this->mAccountOrigin=$account_orgin;
 }
 
/** Attempts to login a user against the authentication source.  If successfull, returns a User object */
  function login()
  {
  }

/** Logs out the user */
  function logout()
  {
  }

/** Start accounting traffic for the user */
  function acctStart()
  {
  }

/** Update traffic counters */
  function acctUpdate()
  {
  }

/** Final update and stop accounting */
  function acctStop()
  {
  }

}// End class
?>
