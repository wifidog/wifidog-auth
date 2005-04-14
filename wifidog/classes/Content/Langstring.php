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
/**@file Langstring.php
 * @author Copyright (C) 2004-2005 Benoit Grégoire, Technologies Coeus inc.
*/

require_once BASEPATH.'classes/FormSelectGenerator.php';
require_once BASEPATH.'classes/Content.php';
require_once BASEPATH.'classes/LocaleList.php';
require_once BASEPATH.'classes/Locale.php';

error_reporting(E_ALL);
define('LANGSTRING_BUTTON_ADD_NEW_VALUE', 'Ajouter une chaîne supplémentaire');

/** Représente un Langstring en particulier, ne créez pas un objet langstrings si wous n'en avez pas spécifiquement besoin 
 */
class Langstring extends Content
{
	/**Constructeur
	@param $content_id Content id
	*/
	function __construct($content_id)
	{
		parent :: __construct($content_id);
		global $db;
		$this->mBd=&$db;
	}

	/**Retourne la première chaîne disponible dans la langue par défaut de l'usager (si disponible), sinon dans la même langue majeure, sinon la première chaîne disponible
			 * @return string Chaîne UTF-8 retournée
			 */
	function getString()
	{
		$retval = null;
		//Get user's prefered language

		$sql = "SELECT value, locales_id, \n";
		$sql .= Locale :: getSqlCaseStringSelect(User::getCurrentUser()->getPreferedLocale());
		$sql .= " as score FROM langstring_entries WHERE langstring_entries.langstrings_id = '{$this->id}' ORDER BY score LIMIT 1";
		$this->mBd->ExecSqlUniqueRes($sql, $row, false);
		if ($row == null)
		{
			$retval = "(Langstring vide)";
		}
		else
		{
			$retval = $row['value'];
		}
		return $retval;
	}

	/**Ajoute une chaîne de caractère au Langstring
				 * @param $string La chaîne de caractère à ajouter.  Si la chaîne est vide ('') ou null, la fonction retourne sans toucher à la base de donnée
				 * @param $locale La langue régionale de la chaîne ajoutée, exemple: 'fr_CA', peut être NULL
				 * @return bollean, true si une chaîne a été ajoutée à la base de donnée, false autrement.
				 */
	function AddString($string, $locale, $allow_empty_string=false)
	{
		$retval = false;
		$id = 'NULL';
		if ($locale)
		{
			$language = new Locale($locale);
			$id = "'".$language->GetId()."'";
		}
		if ($allow_empty_string || ($string != null && $string != ''))
		{
			$string = $this->mBd->EscapeString($string);
			$this->mBd->ExecSqlUpdate("INSERT INTO langstring_entries (langstring_entries_id, langstrings_id, locales_id, value) VALUES ('".get_guid()."', '$this->id', $id , '$string')", FALSE);
			$retval = true;
		}
		return $retval;
	}
	
	/** Updates the string associated with the locale
	 * @param $string La chaîne de caractère à ajouter.  Si la chaîne est vide ('') ou null, la fonction retourne sans toucher à la base de donnée
	 * @param $locale La langue régionale de la chaîne ajoutée, exemple: 'fr_CA', peut être NULL
	 * @return bollean, true si une chaîne a été ajoutée à la base de donnée, false autrement.
	 */
	function UpdateString($string, $locale)
	{
		$retval = false;
		$id = 'NULL';
		if ($locale)
		{
			$language = new Locale($locale);
			$id = "'".$language->GetId()."'";
		}
		if ($string != null && $string != '')
		{
			$string = $this->mBd->EscapeString($string);
			// If the update returns 0 ( no update ), try inserting the record
			$this->mBd->ExecSqlResUnique("SELECT * FROM langstring_entries WHERE locales_id = $id AND langstrings_id = '$this->id'", $row, false);
			if($row != null)
				$this->mBd->ExecSqlUpdate("UPDATE langstring_entries SET value = '$string' WHERE langstrings_id = '$this->id' AND locales_id = $id", false);
			else
				$this->AddString($string, $locale);
			$retval = true;
		}
		return $retval;
	}

	/**Affiche l'interface d'administration de l'objet
				@param type_interface SIMPLE pour éditer un seul champ, COMPLETE pour voir toutes les chaînes, LARGE pour avoir un textarea.
				@param num_nouveau Nombre de champ à afficher pour entrer de nouvelles chaîne en une seule opération
				*/
	function getAdminInterface($type_interface = 'SIMPLE', $num_nouveau = 1)
	{
		$html='';
		$html .= "<div class='admin_container'>\n";
		$html .= "<div class='admin_class'>Langstring (".get_class($this)." instance)</div>\n";
		
		$liste_languages = new LocaleList();
		$sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
		$this->mBd->ExecSql($sql, $result, FALSE); //echo "type_interface: $type_interface\n";
		
		$html .= "<TABLE class='spreadsheet'>";
		if ($type_interface == 'COMPLETE')
		{
			$html .=  "<TR class='spreadsheet'>\n";
			$html .=  "<TH class='spreadsheet'>Language</TH>\n";
			$html .=  "<TH class='spreadsheet'>Chaîne</TH>\n";
			$html .=  "<TH class='spreadsheet'>Effacer sous-chaîne #</TH>\n";
			$html .=  "</TR>\n";
		}
		
		if ($result != null)
		{
			while (list ($key, $value) = each($result))
			{
				$select = $liste_languages->GenererFormSelect("$value[locales_id]", "langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
				if ($type_interface == 'LARGE')
				{
					$html .=  "<TR class='spreadsheet' ><TD class='spreadsheet' colspan=2>$select<br>\n";
					$html .=  "<textarea name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' cols='50' rows='3'>".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."</textarea>\n";
				}
				else
				{
					$html .=  "<TR class='spreadsheet' ><TD class='spreadsheet'>$select</TD><TD class='spreadsheet'>\n";
					$html .=  "<input type='text' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' size='44' value='".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."'>\n";
				}
				$html .=  "</TD>\n";
				$html .=  "<TD class='spreadsheet'>";
						//"<input type='checkbox' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase' value='true'>\n";
				if ($type_interface != 'COMPLETE')
				{
					$name = "langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase";
					$html .=  "<input type='submit' name='$name' value='"._("Delete")."' onclick='submit();'>";
				}
				else
				{
					$html .=  "$value[langstring_entries_id]\n";
				}
				$html .=  "</TD></TR>\n";
			}
		}
		
		$html .=  "<TR class='spreadsheet'><TD class='spreadsheet' colspan=3>\n";
		$name = "langstrings_".$this->id."_num_new_entry"; //echo "<TD class='spreadsheet'><input type='text' name='$name' size='30' value='0'></TD></TR>\n";
		$html .=  "<input type='hidden'  value=''>\n";
		$html .=  "<input type='submit' name='$name' value='".LANGSTRING_BUTTON_ADD_NEW_VALUE."' onclick='submit();'>";
		$html .=  "</td></tr>";

		if ($result == null)
		{ //Nouvelles chaîne(s)
			for ($i = 1; $i <= $num_nouveau; $i ++)
			{
				global $session;
				$locale = $session->get('SESS_LANGUAGE_VAR');
				$select = $liste_languages->GenererFormSelect($locale, "langstrings_".$this->id."_substring_new".$i."_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
				if ($type_interface == 'LARGE')
				{
					$html .=  "<TR class='spreadsheet' ><TD class='spreadsheet' colspan=2>$select\n";
					$html .=  "<textarea name='langstrings_".$this->id."_substring_new".$i."_string' cols='60' rows='3'></textarea>\n";
				}
				else
				{
					$html .=  "<TR class='spreadsheet' ><TD class='spreadsheet'>$select</TD><TD class='spreadsheet'>\n";
					$html .=  "<input type='text' name='langstrings_".$this->id."_substring_new".$i."_string' size='44' value=''>\n";
				}
				$html .=  "</TD>\n";
				$html .=  "<TD class='spreadsheet'>(Chaîne sera ajoutée)</TD>\n";
				$html .=  "</TR>\n";
			}
		}

		$html .=  "</TABLE>";
				$html .=  "</div>";
		return parent::getAdminInterface($html);
	}

	function processAdminInterface()
	{
		parent::processAdminInterface();
		$generateur_form_select = new FormSelectGenerator();
		$sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id'";
		$this->mBd->ExecSql($sql, $result, FALSE);
		if ($result != null)
		{
			while (list ($key, $value) = each($result))
			{ //	print_r($value);
				if (!empty ($_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase"]) && $_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase"] == true)
				{
					$this->mBd->ExecSqlUpdate("DELETE FROM langstring_entries WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);
				}
				else
				{
					$language = $generateur_form_select->getResult("langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin');
					if (empty ($language))
					{
						$language = 'NULL';
					}
					else
					{
						$language = "'".$language."'";
					}
					$string = $this->mBd->EscapeString($_REQUEST["langstrings_".$this->id."_substring_$value[langstring_entries_id]_string"]);
					$this->mBd->ExecSqlUpdate("UPDATE langstring_entries SET locales_id = $language , value = '$string' WHERE langstrings_id = '$this->id' AND langstring_entries_id='$value[langstring_entries_id]'", FALSE);
				}
			}
		} //Nouvelles chaîne(s) déja tapées
		for ($i = 1; isSet ($_REQUEST["langstrings_".$this->id."_substring_new".$i."_string"]); $i ++)
		{
			if ($_REQUEST["langstrings_".$this->id."_substring_new".$i."_string"] != '') //Seulement si il y a effectivement du texte dans la nouvelle chaîne
			{
				$language = $generateur_form_select->getResult("langstrings_".$this->id."_substring_new".$i."_language", 'Langstring::AfficherInterfaceAdmin');
				if (empty ($language))
				{
					$language = null;
				}
				$string = $this->mBd->EscapeString($_REQUEST["langstrings_".$this->id."_substring_new".$i."_string"]);
				$this->AddString($string, $language);
			}
		} //Nouvelles chaînes vides
		$name = "langstrings_".$this->id."_num_new_entry";
		if (isset ($_REQUEST[$name]) && $_REQUEST[$name] == LANGSTRING_BUTTON_ADD_NEW_VALUE)
		{
			$num_new_entry = 1;
		}
		else
			if (isset ($_REQUEST[$name]))
			{
				$num_new_entry = $_REQUEST[$name];
			}
			else
			{
				$num_new_entry = 0;
			}
		for ($i = 1; $i <= $num_new_entry; $i ++)
		{
			$string = '';
			$language = User::getCurrentUser()->getPreferedLocale();
			$this->AddString($string, $language, true);
		}
	}

	/**Affiche l'interface usager de l'objet
			*/
			
	/** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
	 * @param $subclass_admin_interface Html content of the interface element of a children
	 * @return The HTML fragment for this interface */
	public function getUserUI($subclass_user_interface = null)
	{
		$html = '';
		$html .= "<div class='user_ui_container'>\n";
		$html .= "<div class='user_ui_object_class'>Langstring (".get_class($this)." instance)</div>\n";
		$html .= $this->getString();
		$html .= $subclass_user_interface;
		$html .= "</div>\n";
		return parent::getUserUI($html);
	}

	/**Exporte l'élément dans un format d'échange
			@param $export_format format de la sortie
			@param $document Le document auquel la sortie doit être ajouté.  Le type peut varier
			@param $parent Le parent de l'élément à ajouter.  Le type peut varier	
			*/
	function Export($export_format, & $document, $parent)
	{

		$sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id'";
		$this->mBd->ExecSql($sql, $result, FALSE);
		if ($result != null)
		{
			if ($export_format == 'LOM')
			{
				while (list ($key, $value) = each($result))
				{
					$langstring = $document->createElementNS(LOM_EXPORT_NS,"string");
					$langstring = $parent->appendChild($langstring);
					if (!empty ($value['locales_id']))
					{
						$locale=new Locale($value['locales_id']);
						$langstring->setAttribute('language', $locale->GetId());
					}
					$textnode = $document->createTextNode($value['value']);
					$langstring->appendChild($textnode);
				}
			}
			elseif ($export_format == 'RSS')
			{
				while (list ($key, $value) = each($result))
				{
					$textnode = $document->createTextNode($value['value']);
					$parent->appendChild($textnode);
				}
			}
			elseif ($export_format == 'VDEX')
			{
				while (list ($key, $value) = each($result))
				{
					$langstring = $document->createElementNS(VDEX_EXPORT_NS, "langstring");
					$langstring = $parent->appendChild($langstring);
					if (!empty ($value['locales_id']))
					{
						$locale=new Locale($value['locales_id']);
						$langstring->setAttribute('language', $locale->GetId());
					}
					$textnode = $document->createTextNode($value['value']);
					$langstring->appendChild($textnode);
				}
			}
			else
			{
				echo "<h1> Langstring :: Export() : Format d'exportation inconnu!</h1>\n";
			}
		}
	}

	/**Retourne le nombre de sous-chaînes du langstring
		@return Le nombre de sous-chaine.  0 signifie que la chaîne est vide
		*/
	function GetNumStrings()
	{
		$sql = "SELECT count(langstring_entries_id) FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id'";
		$this->mBd->ExecSqlResUnique($sql, $row, false);
		return $row['count'];
	}

	/**La chaîne est-elle vide?
	@return true or false
	*/
	function IsEmpty()
	{
		$retval = true;
		$sql = "SELECT count(langstring_entries_id) FROM langstring_entries WHERE langstring_entries.langstrings_id = $this->id AND value IS NOT NULL AND value!=''";
		$this->mBd->ExecSqlResUnique($sql, $row, false);
		
		if ($row!=null && $row['count'] > 0)
		{
			$retval = false;
		}
		
		return $retval;
	}

	/** Import
	 * @param Langstring ID, "NEW" for new entry
	 * @param import_format LOM or VDEX
	 * @param langstrings the DOMNode object
	 * @param document : the DOMDocument object
	 */
	public static function Import($id, $import_format, $langstrings, $document)
	{
		if ($import_format == 'LOM')
		{
			$xpath = new DOMXPath($document);
			$xpath->registerNamespace("dns", "http://ltsc.ieee.org/xsd/LOM");
			
			$node_list = $xpath->query("dns:string", $langstrings);
			$str = new Langstring($id);
			foreach ($node_list as $node)
			{
				$attr_list = $xpath->query("@language", $node);
				$langstring_lang = "";
				foreach ($attr_list as $attr)
					$langstring_lang = $str->mBd->EscapeString($attr->nodeValue);
				$str->AddString($node->nodeValue, $langstring_lang);
			}
			return $str;
		}
		else if ($import_format == 'VDEX')
		{
			$xpath = new DOMXPath($document);
			$xpath->registerNamespace("dns", "http://www.imsglobal.org/xsd/imsvdex_v1p0");
			
			$node_list = $xpath->query("dns:langstring", $langstrings);
			$str = new Langstring($id);
			$langstring_lang = "";
			foreach ($node_list as $node)
			{
				$attr_list = $xpath->query("@language", $node);
				foreach ($attr_list as $attr)
					$langstring_lang = $str->mBd->EscapeString($attr->nodeValue);
				$str->AddString($node->nodeValue, $langstring_lang);
			}
			return $str;
		}
		else
		{
			return "Import Identifier :: Uknown format : $import_format ";
		}
	}


} /* end class Langstring */
?>