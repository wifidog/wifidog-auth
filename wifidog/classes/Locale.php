<?php


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
/**@file Locale.php
 * @author Copyright (C) 2004 Benoit Grégoire, Technologies Coeus inc.
*/

error_reporting(E_ALL);

/* Gettext support */
if (!function_exists('gettext'))
{
	define('GETTEXT_AVAILABLE', false);
	/* Redefine the gettext functions if gettext isn't installed */
	function gettext($string)
	{
		return $string;
	}
	function _($string)
	{
		return $string;
	}
}
else
{
	define('GETTEXT_AVAILABLE', true);
}

/** Représente un langage humain, possiblement localisé, tel que fr_CA
 */
class Locale
{
	// Attributes

	//prive:
	private $mLang;
	private $mPays;

	/** Get the Locale object
	 * @param $content_id The content id
	 * @return the Content object, or null if there was an error (an exception is also thrown)
	 */
	static function getObject($locale_id)
	{

		return new self($locale_id);
	}

	public static function getCurrentLocale()
	{
		global $session;
		$object = null;
		$locale_id = $session->get(SESS_LANGUAGE_VAR);
		if (empty ($locale_id))
		{
			//$object = new self(DEFAULT_LANG);
			$object = self :: getObject(DEFAULT_LANG);
			self :: setCurrentLocale($object);
		}
		else
		{
			//$object = new self($locale_id);
			$object = self :: getObject($locale_id);
			self :: setCurrentLocale($object);
		}
		return $object;
	}

	/**
	 * @return true on success, false on failure
	 */
	public static function setCurrentLocale($locale)
	{
		global $session;
		$retval = false;

		// Get new locale ID, assume default if null
		if ($locale != null)
		{
			$locale_id = $locale->getId();
			$session->set(SESS_LANGUAGE_VAR, $locale_id);
			$retval = true;
		}
		else
		{
			$locale_id = DEFAULT_LANG;
			$session->set(SESS_LANGUAGE_VAR, $locale_id);
			$retval = false;
		}
				
		if (GETTEXT_AVAILABLE)
		{					
			// Try to set locale
			$current_locale = setlocale(LC_ALL, $locale_id);
			// Test it against current PHP locale
			if ($current_locale != $locale_id)
			{
				echo "Warning: language.php: Unable to setlocale() to ".$locale_id.", return value: $current_locale, current locale: ".setlocale(LC_ALL, 0);
				$retval = false;
			}
			else
			{
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

	/** Example: 'fr_CA_montreal' will give
	$matches[0]=fr_CA_montreal
	$matches[1]=fr
	$matches[2]=CA
	$matches[3]=montreal
	Note:  Off course, matches 2 and 3 could be empty if the information wasn't present.
	*/
	public static function decomposeLocaleId($locale_id)
	{
		//echo "<p>: ";
		//print_r($p_locale);
		$regex = '/^([^-_]*)(?:[-_]([^-_]*))?(?:[-_]([^-_]*))?$/';
		/*
		echo "<h1>Locale: $p_locale</h1>";
		$str1 = 'fr';
		$str2 = 'fr_CA';
		$str3 = 'fr_CA_Montreal';
					$match_retval = preg_match($regex, $str1, $matches);
		echo "<pre>".print_r($matches)."</pre>\n";
					$match_retval = preg_match($regex, $str2, $matches);
		echo "<pre>".print_r($matches)."</pre>\n";
					$match_retval = preg_match($regex, $str3, $matches);
		echo "<pre>".print_r($matches)."</pre>\n";
		*/
		$match_retval = preg_match($regex, $locale_id, $matches);
		//print_r($matches[1]);
		return $matches;
	}
	/** Used by Langstring::GetString() (and other functions) to help select the best langstring_entry to display to the user.
	 * @return A sql fragment
	 */
	public static function getSqlCaseStringSelect($locale_id)
	{
		$decomposed_locale = Locale :: decomposeLocaleId($locale_id);

		$sql = " (CASE\n";
		//On cherche une chaine ou le locale complet correspond au locale par défaut de l'usager

		$sql .= " WHEN locales_id='$decomposed_locale[0]' THEN 1\n";
		//On cherche une chaine ou la >langue< du locale correspond a la langue du locale de l'usager (langue générique en premier)
		$sql .= " WHEN locales_id='{$decomposed_locale[1]}' THEN 2\n";
		//On cherche une chaine ou la >langue< du locale correspond a la langue du locale de l'uager (autres locales de même langue)
		$sql .= " WHEN locales_id LIKE '{$decomposed_locale[1]}%' THEN 3\n";
		//On cherche une chaine ou le pays du locale correspond au pays du locale de l'uager
		if (!empty ($decomposed_locale[2]))
		{
			$sql .= " WHEN locales_id LIKE '%{$decomposed_locale[2]}' THEN 4\n";
		}
		//On cherche une chaine n'ayant pas de locale associée, elle a plue de chance d'être lisible qu'une chaîne prise au hasard
		$sql .= " WHEN locales_id IS NULL THEN 5\n";

		$sql .= "      ELSE 20 ";
		$sql .= "  END)\n";
		return $sql;
	}

	/**Constructeur
	@param string locale Locale in POSIX format (excluding charset), such as fr ou fr_CA: "xx(x)_YY_(n*z)".  Both '_' and '-' are acceptable as separator.
	*/
	function __construct($p_locale)
	{
		//parent :: __construct();
		$matches = self :: decomposeLocaleId($p_locale);
		$locale = $matches[1];
		$this->mLang = $matches[1];
		if ($this->mLang == null)
		{
			throw new Exception(_("Locale(): Impossible de trouver une langue correspondant à $locale"), EXCEPTION_CREATE_OBJECT_FAILED);
		}
		if (empty ($matches[2]))
		{
			$this->mPays = null;
		}
		else
		{
			$locale .= '_'.$matches[2];
			$this->mPays = $matches[2];
		}
		if (empty ($matches[3]))
		{
			; /* TODO:  SUPPORT subcode */
		}
		else
		{
			$locale .= '_'.$matches[3];
		}
		$this->mId = $locale;
	}

	public function GetId()
	{
		return $this->mId;
	}

	/**Retourne le Locale en format POSIX, tel que fr ou fr_CA "xx_YY".
	 * @return string Locale
	 */
	function GetLocale()
	{
		$retval = $this->mLang->GetShort();
		if ($this->mPays != null) //$mPays != null)
		{
			$retval .= '_'.$this->mPays->GetShort();
		}
		return $retval;
	}

	/**Retourne le Locale en format XML (xs:language), tel que fr ou fr-CA "xx-YY".
	 * @return string Locale
	 */
	function GetXMLLanguage()
	{
		return $this->mId;
	}
	/**Retourne la langue.
	 * @return Lang.
	 */
	function GetLang()
	{
		return $this->mLang;
	}

	/**Retourne le pays, s'il existe.
	 * @return Pays ou null
	 */
	function GetPays()
	{
		return $this->mPays;
	}

	/*
	 * Returns a HTML formatted string for output to string ( with an image )
	 */
	function GetString()
	{
		/**
		 * HTML for visual displaying (country flag)
		 */
		//return "<img class='icon' src='images/locale_icons/".$this->GetLocale().".gif' WIDTH='29' HEIGHT='19'>\n";
		$str = "";

		$tmp_loc = $this->GetLocale();

		//Recherche dans la BD du pays et de la langue choisie et affichée selon la langue de préférence de la session
		$sql = "SELECT * FROM languages_iso_639_1, locales LEFT JOIN countries ON (locales.countries_id = countries.countries_id) ";
		$sql .= "WHERE locales.languages_iso_639_1_id = languages_iso_639_1.iso639_1_id ";
		$sql .= "AND locales.locales_id = '$tmp_loc' ";
		$this->mBd->ExecuterSqlResUnique($sql, $resultats, FALSE);

		$tmp_pref_lang = $this->mSession->GetPrefLocale();
		$sql = "SELECT * FROM locales WHERE locales.locales_id = '$tmp_pref_lang'";
		$this->mBd->ExecuterSqlResUnique($sql, $preflang_result, FALSE);

		switch ($preflang_result['languages_iso_639_1_id'])
		{
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
			default :
				{
					$str .= "$resultats[french_name], $resultats[country_french_name]";
					break;
				}
		};
		return $str;
	}

} /* end class Locale */
?>