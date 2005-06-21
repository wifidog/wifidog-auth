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
/**@file index.php Displayed when the user tries to use the start button but for some reason the original url is unavailable
 * @author Copyright (C) 2004 Benoit Gr�goire et Philippe April
 */

define('BASEPATH', '../');
require_once BASEPATH.'include/common.php';
require_once BASEPATH.'include/common_interface.php';
require_once BASEPATH.'classes/MainUI.php';

$ui = new MainUI();

//$ui->setToolContent($tool_html);

$html = '';

// While in validation period, alert user that he should validate his account ASAP
if($current_user && $current_user->getAccountStatus() == ACCOUNT_STATUS_VALIDATION)
	$html .= "<div id='warning_message_area'>\n";
	$html .= _('For some reason, we were unable to determine the web site you initially wanted to see.  You should now enter a web address in your URL bar.');
	$html .= "</div>";

$ui->setMainContent($html);
$ui->display();
?>