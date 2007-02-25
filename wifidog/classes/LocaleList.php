<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Représente une liste de tous les languages humains en usage dans le système,
 * selon les différentes normes internationales.
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class LocaleList {

    function __construct() {
        $db = AbstractDb::getObject();
        $this->mBd = & $db; //for backward compatibility
    }

    /**
     * Returns language of languages codes supported by WiFiDOG.
     *
	 * @author Max Horváth <max.horvath@freenet.de>
	 * @copyright 2005-2006 Max Horváth, Horvath Web Consulting
     * @param string $locale Language code.
     * @return string Language representing the language code.
     */
    private function getHumanLanguage($locale) {
        // Init values.
        $_retvalue = null;

        $_humanLanguages = array('fr' => _("French"),
                                 'en' => _("English"),
                                 'de' => _("German"),
                                 'es' => _("Spanish"),
                                 'pt' => _("Portuguese"),
                                 'ja' => _("Japanese"));

        if (array_key_exists($locale, $_humanLanguages)) {
            $_retvalue = $_humanLanguages[$locale];
        } else {
            $_retvalue = $locale;
        }

        return $_retvalue;
    }

    /**
     * Permet de générer un élément HTML select et de récupérer le résultat.
     *
     * @param string selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
     * @param string prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
     * @param string prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
     * @param boolean permetValeurNulle, TRUE ou FALSE
     * @return string L'élément select généré
     */
    function GenererFormSelect($selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $permetValeurNulle, $exclude_array = null) {
        $retval = "";
        $resultats = "";
if($exclude_array==null) {
    $exclude_array = array();
}
        $sql = "SELECT * FROM locales ORDER BY locales_id";
        $this->mBd->execSql($sql, $resultats, FALSE);

        $retval = "";
        $retval .= "<select name='$prefixeNomSelectUsager$prefixeNomSelectObjet'>\n";

        if ($permetValeurNulle == true && !in_array ('', $exclude_array)) {
            $retval .= "<option value=''>---</option>\n";
        }

        while (list ($key, $value) = each($resultats)) {
            if(!in_array ($value['locales_id'], $exclude_array)) {
            $retval .= "<option ";

            if ($value['locales_id'] == $selectedClefPrimaire || $selectedClefPrimaire == null && $selectedClefPrimaire == $this->GetDefault()) {
                $retval .= 'selected="selected" ';
            }

            $retval .= "value='$value[locales_id]'>" . $this->getHumanLanguage($value["locales_id"]);
            $retval .= "</option>\n";
            }
        }

        $retval .= "</select>\n";

        return $retval;
    }

    /**
     * Retourne le language par défaut, selon les préférences de l'usager
     */
    function GetDefault() {
        $session = Session::getObject();

        if ($user = User :: getCurrentUser()) {
            $locale = $user->getPreferedLocale();
        } else {
            $locale = $session->get(SESS_LANGUAGE_VAR);

            if (empty ($locale)) {
                $locale = DEFAULT_LANG;
            }
        }

        return $locale;
    }

    /**
     * Retourne la liste de toutes les clef primairess
     */
    function GetListeClefsPrimaires() {
        // Init values.
        $resultats = "";

        $this->mBd->ExecuterSql("SELECT locales_id FROM locales", $resultats, FALSE);

        foreach ($resultats as $resultat) {
            $retval[] = $resultat['locales_id'];
        }

        return $retval;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

