<?php
if (!empty($_REQUEST['lang'])) {
    $session->set('SESS_LANGUAGE_VAR', $_REQUEST['lang']);
}

if ($session->get('SESS_LANGUAGE_VAR')) {
    setlocale(LC_ALL, $session->get('SESS_LANGUAGE_VAR'));
}

if (GETTEXT_AVAILABLE) {
	$current_locale = setlocale(LC_ALL, DEFAULT_LANG);
	if (setlocale(LC_ALL, DEFAULT_LANG) != DEFAULT_LANG) {
		echo "Warning: language.php: Unable to setlocale() to ".DEFAULT_LANG.", return value: $current_locale, current locale: ".  setlocale(LC_ALL, 0);
	}

	bindtextdomain('messages', BASEPATH.'/locale');
	bind_textdomain_codeset('messages', 'UTF-8');
	textDomain('messages');

	if (!empty($_REQUEST['lang']) && isset($session)) {
	    $session->set(SESS_LANGUAGE_VAR, $_REQUEST['lang']);
	}

	if (isset($session) && $session->get(SESS_LANGUAGE_VAR)) {
	    putenv("LC_ALL=".$session->get(SESS_LANGUAGE_VAR));
	    putenv("LANGUAGE=".$session->get(SESS_LANGUAGE_VAR));
	    setlocale(LC_ALL, $session->get(SESS_LANGUAGE_VAR));
	    if (isset($smarty)) {
	        $smarty->assign("lang_id", $session->get(SESS_LANGUAGE_VAR));
	    }
	} else {
	    putenv("LC_ALL=" . DEFAULT_LANG);
	    putenv("LANGUAGE=" . DEFAULT_LANG);
	    setlocale(LC_ALL, DEFAULT_LANG);
	    $smarty->assign("lang_id", DEFAULT_LANG);
	}

	if (isset($smarty)) {
	    $smarty->assign("lang_ids", $lang_ids);
	    $smarty->assign("lang_names", $lang_names);
	}
}
?>
