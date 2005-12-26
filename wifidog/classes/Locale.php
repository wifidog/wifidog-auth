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
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Gregoire <bock@step.polymtl.ca> - Technologies Coeus
 * inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

error_reporting(E_ALL);

// Detect Gettext support.
if (!function_exists('gettext')) {
    define('GETTEXT_AVAILABLE', false);

    // Redefine the gettext functions if gettext isn't installed.

    function gettext($string) {
        return $string;
    }

    function _($string) {
        return $string;
    }
} else {
    define('GETTEXT_AVAILABLE', true);
}

/** 
 * Designates a human language, possibly localized ie fr_CA
 */
class Locale {
    // Private attributes
    private $mLang;
    private $mCountry;

    /**
     * Constructor
     * @param string $p_locale Locale in POSIX format (excluding charset), such
     * as fr ou fr_CA: "xx(x)_YY_(n*z)".  Both '_' and '-' are acceptable as
     * separator.
     */
    function __construct($p_locale) {
        $matches = self :: decomposeLocaleId($p_locale);
        $locale = $matches[1];
        $this->mLang = $matches[1];

        if ($this->mLang == null) {
            throw new Exception(_("Locale(): Could not a locale matching $locale"), EXCEPTION_CREATE_OBJECT_FAILED);
        }

        if (empty ($matches[2])) {
            $this->mCountry = null;
        } else {
            $locale .= '_'.$matches[2];
            $this->mCountry = $matches[2];
        }

        if (empty ($matches[3])) {
            // TODO: Optionally support subcode ?
        } else {
            $locale .= '_'.$matches[3];
        }

        $this->mId = $locale;
    }

    /**
     * Get the Locale object
     * @param string $content_id The content id
     * @return The Content object, or null if there was an error (an exception is also thrown)
     */
    static function getObject($locale_id) {
        return new self($locale_id);
    }

    public static function getCurrentLocale() {
        global $session;
        $object = null;
        $locale_id = $session->get(SESS_LANGUAGE_VAR);

        /* Try to guess the lang */
        if (empty($locale_id)) {
            $locale_id = self :: getBestLanguage();
        }

        /* If we still don't have it, fill in default */
        if (empty ($locale_id)) {
            $object = self :: getObject(DEFAULT_LANG);
            self :: setCurrentLocale($object);
        } else {
            $object = self :: getObject($locale_id);
            self :: setCurrentLocale($object);
        }

        return $object;
    }

    /**
      * Try to find best language according to HTTP_ACCEPT_LANGUAGE passed
      * by the browser.
      * @return string Best language from list of available languages, otherwise
      * empty.
      */
    public static function getBestLanguage() {
        global $AVAIL_LOCALE_ARRAY;

        if (defined($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            foreach(split(';', $_SERVER["HTTP_ACCEPT_LANGUAGE"]) as $lang) {
                foreach($AVAIL_LOCALE_ARRAY as $avail_lang => $lang_description) {
                    $lang = ereg_replace("[_-].*$", "", $lang);
                    $avail_lang_trimmed = ereg_replace("[_-].*$", "", $avail_lang);

                    if ($lang == $avail_lang_trimmed) {
                        return $avail_lang;
                    }
                }
            }
        }
    }

    /**
     * @todo Don't trust the value in the cookie, verify that the value is in
     * the AVAILABLE locales set in the config.
     * @return boolean true on success, false on failure.
     */
    public static function setCurrentLocale($locale) {
        global $session;
        $retval = false;

        // Get new locale ID, assume default if null
        if ($locale != null) {
            $locale_id = $locale->getId();
            $session->set(SESS_LANGUAGE_VAR, $locale_id);
            $retval = true;
        } else {
            $locale_id = DEFAULT_LANG;
            $session->set(SESS_LANGUAGE_VAR, $locale_id);
            $retval = false;
        }

        if (GETTEXT_AVAILABLE) {
            // Try to set locale
            $current_locale = setlocale(LC_ALL, $locale_id);

            // Test it against current PHP locale
            if ($current_locale != $locale_id) {
                echo "Warning in /classes/Locale.php : Unable to setlocale() to ".$locale_id.", return value: $current_locale, current locale: ".setlocale(LC_ALL, 0);
                $retval = false;
            } else {
                bindtextdomain('messages', BASEPATH.'/locale');
                bind_textdomain_codeset('messages', 'UTF-8');
                textDomain('messages');

                putenv("LC_ALL=".$locale_id);
                putenv("LANGUAGE=".$locale_id);
                $session->set(SESS_LANGUAGE_VAR, $locale_id);
                $retval = true;
            }
        }

        return $retval;
    }

    /**
     * Example: 'fr_CA_montreal' will give
     * $matches[0]=fr_CA_montreal
     * $matches[1]=fr
     * $matches[2]=CA
     * $matches[3]=montreal
     * Note:  Off course, matches 2 and 3 could be empty if the information
     * wasn't present.
     */
    public static function decomposeLocaleId($locale_id) {
        // Init values
        $_matches = "";

        $_regex = '/^([^-_]*)(?:[-_]([^-_]*))?(?:[-_]([^-_]*))?$/';
        $_match_retval = preg_match($_regex, $locale_id, $_matches);
        return $_matches;
    }

    /**
     * Used by Langstring::GetString() (and other functions) to help select the
     * best langstring_entry to display to the user.
     * @return A sql fragment
     */
    public static function getSqlCaseStringSelect($locale_id) {
        $decomposed_locale = Locale :: decomposeLocaleId($locale_id);
        
        // The case will rate locales and choose the best one.
        
        $sql = " (CASE\n";
        // Look for part of the string or the full-length locale
        $sql .= " WHEN locales_id='$decomposed_locale[0]' THEN 1\n";
        // Look for a string or the language part of the locale (match generic language first)
        $sql .= " WHEN locales_id='{$decomposed_locale[1]}' THEN 2\n";
        // Look for the full string or any possible combination
        $sql .= " WHEN locales_id LIKE '{$decomposed_locale[1]}%' THEN 3\n";
        
        // Look for a string matching the language or the country of the user
        if (!empty ($decomposed_locale[2])) {
            $sql .= " WHEN locales_id LIKE '%{$decomposed_locale[2]}' THEN 4\n";
        }
        
        // Look for a string with no locale associated, it's more likely to be readable than a random string
        $sql .= " WHEN locales_id IS NULL THEN 5\n";

        $sql .= "      ELSE 20 ";
        $sql .= "  END)\n";

        return $sql;
    }

    public function GetId() {
        return $this->mId;
    }

    /**
     * Returns the locale in POSIX format, such as fr ou fr_CA "xx_YY".
     * @return string Locale
     */
    function GetLocale() {
        $retval = $this->mLang->GetShort();

        if ($this->mCountry != null) {
            $retval .= '_'.$this->mCountry->GetShort();
        }

        return $retval;
    }

    /**
     * Returns the locale in W3C XML format (xs:language), such as fr ou fr-CA "xx-YY".
     * @return string Locale
     */
    function GetXMLLanguage() {
        return $this->mId;
    }

    /**
     * Returns the language
     * @return Lang.
     */
    function GetLang() {
        return $this->mLang;
    }

    /**
     * Returns the country, if available
     * @return Country or null
     */
    function GetCountry() {
        return $this->mCountry;
    }

    /**
     * Returns a HTML formatted string for output to string (with an image)
     */
    function GetString() {
        // Init values.
        $str = "";
        $resultats = "";
        $preflang_result = "";

        $tmp_loc = $this->GetLocale();

        // Look for the country in the database and match the preferred locale
        $sql = "SELECT * FROM languages_iso_639_1, locales LEFT JOIN countries ON (locales.countries_id = countries.countries_id) ";
        $sql .= "WHERE locales.languages_iso_639_1_id = languages_iso_639_1.iso639_1_id ";
        $sql .= "AND locales.locales_id = '$tmp_loc' ";
        $this->mBd->ExecuterSqlResUnique($sql, $resultats, FALSE);

        $tmp_pref_lang = $this->mSession->GetPrefLocale();
        $sql = "SELECT * FROM locales WHERE locales.locales_id = '$tmp_pref_lang'";
        $this->mBd->ExecuterSqlResUnique($sql, $preflang_result, FALSE);

        switch ($preflang_result['languages_iso_639_1_id']) {
            case ('fr') :
                {
                    $str .= "$resultats[french_name], $resultats[country_french_name]";
                    break;
                }
            case ('en') :
                {
                    $str .= "$resultats[english_name], $resultats[country_english_name]";
                    break;
                }
            case ('de') :
                {
                    $str .= "$resultats[german_name], $resultats[country_german_name]";
                    break;
                }
            default :
                {
                    $str .= "$resultats[french_name], $resultats[country_french_name]";
                    break;
                }
        }

        return $str;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>