<?php
if (!empty($_REQUEST['lang']) && isset($session)) {
    $session->set(SESS_LANGUAGE_VAR, $_REQUEST['lang']);
}

if (isset($session) && $session->get(SESS_LANGUAGE_VAR)) {
    putenv("LC_ALL=".$session->get(SESS_LANGUAGE_VAR));
    setlocale(LC_ALL, $session->get(SESS_LANGUAGE_VAR));
    if (isset($smarty)) {
        $smarty->assign("lang_id", $session->get(SESS_LANGUAGE_VAR));
    }
}

if (isset($smarty)) {
    $smarty->assign("lang_ids", $lang_ids);
    $smarty->assign("lang_names", $lang_names);
}
?>
