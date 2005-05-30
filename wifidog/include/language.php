<?php
require_once BASEPATH.'classes/Session.php';
require_once BASEPATH.'classes/Locale.php';
$session = new Session();

if (!empty ($_REQUEST['wifidog_language']))
{
	Locale::setCurrentLocale(Locale::getObject($_REQUEST['wifidog_language']));
}

$locale = Locale::getCurrentLocale();
$locale_id = $locale->getId();
if (isset ($smarty))
{
	$smarty->assign("lang_id", $locale_id);
}

?>