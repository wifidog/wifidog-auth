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
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

error_reporting(E_ALL);

// Detect Gettext support.
if (!function_exists('gettext')) {
    /**
     * Define Gettext has NOT been found on the system
     */
    define('GETTEXT_AVAILABLE', false);

    // Redefine the gettext functions if gettext isn't installed.

    function gettext($string) {
        return $string;
    }

    function _($string) {
        return $string;
    }
} else {
    /**
     * Define Gettext has been found on the system
     *
     * @ignore
     */
    define('GETTEXT_AVAILABLE', true);
}

/**
 * Designates a human language, possibly localized ie fr_CA
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Gregoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Gregoire, Technologies Coeus inc.
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
			// region
            $locale .= '_'.$matches[3];
			// $this->mRegion = $matches[3];
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
        global $AVAIL_LOCALE_ARRAY;
        $object = null;
        $locale_id = $session->get(SESS_LANGUAGE_VAR);
        //echo sprintf("Debug in /classes/Locale.php getCurrentLocale(): session->get(SESS_LANGUAGE_VAR)=%s", $session->get(SESS_LANGUAGE_VAR))."<br/>";

        /* Try to guess the lang */
        if (empty($locale_id) || empty($AVAIL_LOCALE_ARRAY[$locale_id])) {
            $locale_id = self :: getBestLanguage();
        }

        /* If we still don't have it, fill in default */
        if (empty ($locale_id)) {
            $object = self :: getObject(DEFAULT_LANG);
        } else {
            $object = self :: getObject($locale_id);
        }

        return $object;
    }

    /**
      * Try to find best language according to HTTP_ACCEPT_LANGUAGE passed
      * by the browser.
      * @return string Best language from list of available languages, otherwise
      * empty.
      */
	public static function getBestLanguage($availableLanguages=false) {
		global $AVAIL_LOCALE_ARRAY;
		if (empty($availableLanguages)) $availableLanguages=$AVAIL_LOCALE_ARRAY;

		// the HTTP_ACCEPT_LANGUAGE server string comes from the browser in the
		// Accept-Language: header.  It is a list of browser language preferences separated by commas.
		// the language preference is a 2 part field separated by semicolons.  the first part is the language.
		// the languages are the iso codes.  the language may have a hyphen and a country code appended to
		// it, like "fr-CA" or "en-gb".
		// the second part of the language preference may be missing.  if it's not there it's assumed to be
		// "q=1.0".  This part gives the preference rating and is an float between 0.0 and 1.0.  0.8 corresponds
		// to 80%.

		// $AVAIL_LOCALE_ARRAY, set in config.php.  this is a list of available locales.
		// The format is different from that of HTTP_ACCEPT_LANGUAGE.  It is:
		// LANGUAGE [ _COUNTRY [ .ENCODING ] ]
		// where LANGUAGE and COUNTRY are 2 letter codes (usually), and encoding is something like iso88591 or utf8.
		// for example:
		// english or en or en_CA or en_CA.utf8 or en_CA.iso88591 or en_US.iso885915
		// french or fr or fr_CA or fr_CA.utf8 or fr_CA.iso88591

		$browser_preferences = array();
		foreach(explode(',', empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? DEFAULT_LANG : $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
			//echo $lang."\n";
			if (preg_match('/^\s*([a-z_-]+).*?(?:;\s*q=([0-9.]+))?/i', $lang.';q=1.0', $split)) {
				$browser_preferences[sprintf('%f%d', $split[2], rand(0,9999))] = strtolower($split[1]);
			}
		}

		// sort preferences by key in reverse order, from high to low
		// best is first, worst is last
		krsort($browser_preferences);

		foreach($browser_preferences as $score => $language_spec) {
			//echo "$score => $language_spec\n";
			@list($prefered_language, $prefered_country) = preg_split('/[-_]/', $language_spec);
			// better to use explode('-', $language_spec) except that $language_spec may come from
			// config.php DEFAULT_LANG, which may be given as a locale, with an underscore
			// between the language and country.

			$prefered_locale = empty($prefered_country) ? $prefered_language :
				$prefered_language . '_' . strtoupper($prefered_country);

			// if the browser's preference is matched exactly in $availableLanguages, great!
			if (!empty($availableLanguages[$prefered_locale])) return $prefered_locale;

			if (empty($prefered_country)) {
				// browser doesn't care what country
				// try to find a match in $availableLanguages ignoring the country
				foreach($availableLanguages as $my_locale => $language_name) {
					@list($my_language, $my_country, $my_encoding) = preg_split('/[_.]/', $my_locale);
					if ($my_language === $prefered_language) return $my_locale;
				}
			}
		}

		return false;

		// return array_shift(array_merge(array_intersect($browser_preferences, $availableLanguages), $availableLanguages));
	}

    /** Initialise the system locale (gettext, setlocale, etc.)
     * @return boolean true on success, false on failure.
     */
    public static function setCurrentLocale($locale) {
        global $session;
         global $AVAIL_LOCALE_ARRAY;
        $retval = false;
 
        // Get new locale ID, assume default if null
        if ($locale != null) {
            $locale_id = $locale->getId();
            $retval = true;
            $q = "parameter";
        } else {
            $locale_id = DEFAULT_LANG;
            $retval = false;
            $q = "default";
        }
        //pretty_print_r($locale);
        //echo sprintf("Debug in /classes/Locale.php setCurentLocale(): locale_id=%s", $locale_id)."<br/>";

        if (GETTEXT_AVAILABLE) {
            $lang_only_locale_id = substr ($locale_id, 0 , 2);
                   if(!isset($AVAIL_LOCALE_ARRAY[$locale_id]) && !isset($AVAIL_LOCALE_ARRAY[$lang_only_locale_id]))
                   {
                     echo srintf("Warning in /classes/Locale.php setCurentLocale: Neither %s or %s are available in AVAIL_LOCALE_ARRAY", $locale_id, $lang_only_locale_id)."<br/>";
                   }
            // Try to set locale
            $candidate_locale_array[] = str_ireplace('.UTF8', '', $locale_id).'.UTF-8';
            $candidate_locale_array[] = str_ireplace('.UTF8', '', $locale_id);
            $candidate_locale_array[] = $lang_only_locale_id.'.UTF-8';
            $candidate_locale_array[] = $lang_only_locale_id;

             
            $current_locale = setlocale(LC_ALL, $candidate_locale_array);
               //echo sprintf("Warning in /classes/Locale.php setCurentLocale: Unable to setlocale() to %s: %s.  I tried %s, %s, %s, %s, and got return value: %s, current locale is: %s",$q, $locale_id, $candidate_locale_array[0], $candidate_locale_array[1], $candidate_locale_array[2], $candidate_locale_array[3], $current_locale, setlocale(LC_ALL, 0))."<br/>";

            // Test it against current PHP locale
            if (substr ($current_locale, 0 , 2) != $lang_only_locale_id) {
                echo sprintf("Warning in /classes/Locale.php setCurentLocale: Unable to setlocale() to %s: %s.  I tried %s, %s, %s, %s, and got return value: %s, current locale is: %s",$q, $locale_id, $candidate_locale_array[0], $candidate_locale_array[1], $candidate_locale_array[2], $candidate_locale_array[3], $current_locale, setlocale(LC_ALL, 0))."<br/>";
                $retval = false;
            } else {
                bindtextdomain('messages', WIFIDOG_ABS_FILE_PATH . 'locale');
                bind_textdomain_codeset('messages', 'UTF-8');
                textDomain('messages');

                putenv("LC_ALL=".$current_locale);
                putenv("LANGUAGE=".$current_locale);
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
            case ('pt') :
                {
                    $str .= "$resultats[portuguese_name], $resultats[country_portuguese_name]";
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


