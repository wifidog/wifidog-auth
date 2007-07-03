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
    /**
     * Returns language of languages codes supported by WiFiDOG.
     *
     * @todo Setting an array of only one entry should disable the language select
     *       box.
     * @param string $locale TOW letter language code.
     * @return two dimension array.
     * Index is the language code (ex: fr_CA)
     * $return[0] is the name of the language in that language (ex:  Français)
     * $return[1] is the translated name of the language (ex:  French)
     */
    static public function getAvailableLanguageArray() {
        require_once ('config_available_locales.php');//Do not put this in the header, or it will be included before translations are set!
        $retval = getAvailableLanguageArray();
        return $retval;
    }

        /**
         * Returns language of languages codes supported by WiFiDOG.
         * @author Max Horváth <max.horvath@freenet.de>, Benoit Grégoire
         * @copyright 2005-2006 Max Horváth, Horvath Web Consulting, 2007 Technologies Coeus inc.
         * @param string $locale TOW letter language code.
         * @return string Language representing the language code.
         */
        static private function getHumanLanguage($locale) {
            //pretty_print_r($locale);
            // Init values.
            $_retvalue = null;
            $AVAIL_LOCALE_ARRAY = self::getAvailableLanguageArray();
            $_humanLanguages = array();
            foreach ($AVAIL_LOCALE_ARRAY as $fullLocale=>$names) {
                $_humanLanguages[substr($fullLocale,0,2)] = $names[1];
            }
            //pretty_print_r($_humanLanguages);
            if (array_key_exists($locale, $_humanLanguages)) {
                $_retvalue = $_humanLanguages[$locale];
            } else {
                $_retvalue = $locale;
            }
            //pretty_print_r($_retvalue);
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
        static function GenererFormSelect($selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $permetValeurNulle, $exclude_array = null) {
            $db = AbstractDb::getObject();
            $retval = "";
            $resultats = "";
            if($exclude_array==null) {
                $exclude_array = array();
            }
            $sql = "SELECT * FROM locales ORDER BY locales_id";
            $db->execSql($sql, $resultats, FALSE);

            $retval = "";
            $retval .= "<select name='$prefixeNomSelectUsager$prefixeNomSelectObjet'>\n";

            if ($permetValeurNulle == true && !in_array ('', $exclude_array)) {
                $retval .= "<option value=''>---</option>\n";
            }

            while (list ($key, $value) = each($resultats)) {
                if(!in_array ($value['locales_id'], $exclude_array)) {
                    $retval .= "<option ";

                    if ($value['locales_id'] == $selectedClefPrimaire || $selectedClefPrimaire == null && $selectedClefPrimaire == self::GetDefault()) {
                        $retval .= 'selected="selected" ';
                    }

                    $retval .= "value='$value[locales_id]'>" . self::getHumanLanguage($value["locales_id"]);
                    $retval .= "</option>\n";
                }
            }

            $retval .= "</select>\n";

            return $retval;
        }

        /**
         * Retourne le language par défaut, selon les préférences de l'usager
         */
        static public function GetDefault() {
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
    }

    /*
     * Local variables:
     * tab-width: 4
     * c-basic-offset: 4
     * c-hanging-comment-ender-p: nil
     * End:
     */

