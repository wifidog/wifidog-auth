<?php
require_once BASEPATH.'classes/Session.php';
require_once BASEPATH.'classes/Locale.php';
$session = new Session();

if (!empty ($_REQUEST['lang']))
{
	Locale::setCurrentLocale(Locale::getObject($_REQUEST['lang']));
}

$locale = Locale::getCurrentLocale();
$locale_id = $locale->getId();
		if (isset ($smarty))
		{
			$smarty->assign("lang_id", $locale_id);
		}
?>