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

/** Représente un Langstring en particulier, ne créez pas un objet langstrings si vous n'en avez pas spécifiquement besoin 
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
		$this->mBd = & $db;
        
		/* A langstring is NEVER persistent */
		parent::setIsPersistent(false);
	}

	/**Retourne la première chaîne disponible dans la langue par défaut de l'usager (si disponible), sinon dans la même langue majeure, sinon la première chaîne disponible
			 * @return string Chaîne UTF-8 retournée
			 */
	function getString()
	{
		$retval = null;
		//Get user's prefered language

		$sql = "SELECT value, locales_id, \n";
		$sql .= Locale :: getSqlCaseStringSelect(Locale::getCurrentLocale()->getId());
		$sql .= " as score FROM langstring_entries WHERE langstring_entries.langstrings_id = '{$this->id}' AND value!='' ORDER BY score LIMIT 1";
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
	function addString($string, $locale, $allow_empty_string = false)
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
			if ($row != null)
				$this->mBd->ExecSqlUpdate("UPDATE langstring_entries SET value = '$string' WHERE langstrings_id = '$this->id' AND locales_id = $id", false);
			else
				$this->addString($string, $locale);
			$retval = true;
		}
		return $retval;
	}

	/**Affiche l'interface d'administration de l'objet
				@param type_interface SIMPLE pour éditer un seul champ, COMPLETE pour voir toutes les chaînes, LARGE pour avoir un textarea.
				@param num_nouveau Nombre de champ à afficher pour entrer de nouvelles chaîne en une seule opération
				*/
	function getAdminUI($type_interface = 'LARGE', $num_nouveau = 1)
	{
		$html = '';
				$html .= "<div class='admin_class'>Langstring (".get_class($this)." instance)</div>\n";
				$html .= "<div class='admin_section_container'>\n";

		

		$liste_languages = new LocaleList();
		$sql = "SELECT * FROM langstring_entries WHERE langstring_entries.langstrings_id = '$this->id' ORDER BY locales_id";
		$this->mBd->ExecSql($sql, $result, FALSE); //echo "type_interface: $type_interface\n";

		/*		if ($type_interface == 'COMPLETE')
				{
					$html .=  "<TR class='spreadsheet'>\n";
					$html .=  "<TH class='spreadsheet'>Language</TH>\n";
					$html .=  "<TH class='spreadsheet'>Chaîne</TH>\n";
					$html .=  "<TH class='spreadsheet'>Effacer sous-chaîne #</TH>\n";
					$html .=  "</TR>\n";
				}*/
		$html .= "<ul class='admin_section_list'>\n";
		if ($result != null)
		{
			while (list ($key, $value) = each($result))
			{
				$html .= "<li class='admin_section_list_item'>\n";
								$html .= "<div class='admin_section_data'>\n";
								$html .= $liste_languages->GenererFormSelect("$value[locales_id]", "langstrings_".$this->id."_substring_$value[langstring_entries_id]_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
				if ($type_interface == 'LARGE')
				{
					$html .= "<textarea name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' cols='60' rows='3'>".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."</textarea>\n";
				}
				else
				{
					$html .= "<input type='text' name='langstrings_".$this->id."_substring_$value[langstring_entries_id]_string' size='44' value='".htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')."'>\n";
				}
								$html .= "</div>\n";
								$html .= "<div class='admin_section_tools'>\n";
				$name = "langstrings_".$this->id."_substring_$value[langstring_entries_id]_erase";
				$html .= "<input type='submit' name='$name' value='"._("Delete string")."'>";
								$html .= "</div>\n";
								$html .= "</li>\n";
			}
		}

		//Nouvelles chaîne
		$locale =  LocaleList::GetDefault();
		$html .= "<li class='admin_section_list_item'>\n";
										$html .= "<div class='admin_section_data'>\n";
		
		$html .= $liste_languages->GenererFormSelect($locale, "langstrings_".$this->id."_substring_new_language", 'Langstring::AfficherInterfaceAdmin', TRUE);
		$new_substring_name = "langstrings_".$this->id."_substring_new_string";
		if ($type_interface == 'LARGE')
		{
			$html .= "<textarea name='$new_substring_name' cols='60' rows='3'></textarea>\n";
		}
		else
		{
			$html .= "<input type='text' name='$new_substring_name' size='44' value=''>\n";
		}
										$html .= "</div>\n";
								$html .= "<div class='admin_section_tools'>\n";
		
		$new_substring_submit_name = "langstrings_".$this->id."_add_new_entry";
		$html .= "<input type='submit' name='$new_substring_submit_name' value='"._("Add new string")."'>";
		$html .= "</div>\n";
		$html .= "</li>\n";

		$html .= "</ul>\n";
		$html .= "</div>\n";
		return parent :: getAdminUI($html);
	}

	function processAdminUI()
	{
        if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin())
        {
    		parent :: processAdminUI();
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
    		}
    		//Ajouter nouvelles chaîne(s) si champ non vide ou si l'usager a appuyé sur le bouton ajouter
    		$new_substring_name = "langstrings_".$this->id."_substring_new_string";
    		$new_substring_submit_name = "langstrings_".$this->id."_add_new_entry";
    		if ((isset ($_REQUEST[$new_substring_submit_name]) && $_REQUEST[$new_substring_submit_name] == true) || !empty ($_REQUEST[$new_substring_name]))
    		{
    
    			$language = $generateur_form_select->getResult("langstrings_".$this->id."_substring_new_language", 'Langstring::AfficherInterfaceAdmin');
    			if (empty ($language))
    			{
    				$language = null;
    			}
    			$this->addString($_REQUEST[$new_substring_name], $language, true);
    		}
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
		return parent :: getUserUI($html);
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

		if ($row != null && $row['count'] > 0)
		{
			$retval = false;
		}

		return $retval;
	}

} /* end class Langstring */
?>