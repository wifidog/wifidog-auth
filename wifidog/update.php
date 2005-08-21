<?php
/*
 * Created on Aug 21, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 
define('BASEPATH', './');
require_once BASEPATH.'include/common.php';

global $db;
$db->ExecSqlUpdate("UPDATE nodes SET country = 'Canada', province = 'Québec', city = 'Montréal';", true);
?>
