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
/**@file LocaleList.php
 * @author Copyright (C) 2004 Benoit Grégoire, Technologies Coeus inc.
*/
require_once BASEPATH.'include/common.php';
error_reporting(E_ALL);

/** Représente une liste de tous les languages humains en usage dans le système, selon les différentes normes internationales.
 */
class LocaleList
{
	// Attributes

	//prive:

	// Associations

	// Operations

	function __construct()
	{
		//parent::__construct();
		global $db;
		$this->mBd = & $db; //for backward compatibility
	}

	/**Indique si la clef primaire de l'objet est une chaîne de caractère.
	*/
	function PrimaryKeyIsString()
	{
		return true;
	}

	/**Permet de générer un élément HTML select et de récupérer le résultat.
	 *
	 * @param string selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
	 * @param string prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
	 * @param string prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
	 * @param boolean permetValeurNulle, TRUE ou FALSE
	 * @return string L'élément select généré
	 */
	function GenererFormSelect($selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $permetValeurNulle)
	{
		$retval = "";
		$sql = "SELECT * FROM locales ORDER BY locales_id";
		$this->mBd->ExecSql($sql, $resultats, FALSE);

		$retval = "";
		$retval .= "<select name='$prefixeNomSelectUsager$prefixeNomSelectObjet'>\n";
		if ($permetValeurNulle == true)
		{
			$retval .= "<option value=''>---</option>\n";
		}
		//echo "$selectedClefPrimaire";
		while (list ($key, $value) = each($resultats))
		{
			$retval .= "<option ";

			//echo "$value[$champClefPrimaire],$selectedClefPrimaire<br>";
			if ($value['locales_id'] == $selectedClefPrimaire || $selectedClefPrimaire == null && $selectedClefPrimaire == $this->GetDefault())
			{
				$retval .= "SELECTED ";
			}
			$retval .= "value='$value[locales_id]'>$value[locales_id]";
			$retval .= "</option>\n";
		}
		$retval .= "</select>\n";
		return $retval;
	}

	/**Retourne le language par défaut, selon les préférences de l'usager
	*/
	function GetDefault()
	{
		global $session;

		if ($user = User :: getCurrentUser())
		{
			$locale = $user->getPreferedLocale();
		}
		else
		{
			$locale = $session->get('SESS_LANGUAGE_VAR');
			if (empty ($locale))
			{
				$locale = DEFAULT_LANG;
			}
		}
		return $locale;
	}

	/**Retourne la liste de toutes les clef primairess
	*/
	function GetListeClefsPrimaires()
	{
		$this->mBd->ExecuterSql("SELECT locales_id FROM locales", $resultats, FALSE);

		foreach ($resultats as $resultat)
		{
			$retval[] = $resultat['locales_id'];
		}
		return $retval;
	}

	/**Exporte l'élément dans un format d'échange
	@param $export_format format de la sortie
	@param $document Le document auquel la sortie doit être ajouté.  Le type peut varier
	@param $parent Le parent de l'élément à ajouter.  Le type peut varier	
	@param $entree ID de l'entree de vocabulaire
	*/
	function Export($export_format, & $document, $parent, $entree = null)
	{
		if ($entree != null)
		{
			$langue = new Locale($entree);
			$langue->Export($export_format, $document, $parent);
		}
	}

	function isEmpty()
	{
		return false;
	}

	/**
	 * By definition it cannot be considerend empty, so it's always compliant'
	 * @return boolean
	 */
	function isCompliant($profile, $lom_element)
	{
		return COMPLIANT_MASK;
	}

} /* end class LocaleList */
?>