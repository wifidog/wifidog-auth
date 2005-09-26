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
/**@file GenerateurFormSelect.php
 * @author Copyright (C) 2004 Technologies Coeus inc.
*/


class FormSelectGenerator
{
	// Attributes
	private $mAbstractBd;

	// Associations

	// Operations

	/** Contructeur
	 */
	function __construct()
	{
		global $db;
		$this -> mAbstractBd = $db;
	}

	/**Permet de générer un élément HTML select et de récupérer le résultat, à partir d'un resultset.
	 *
	 * @param $table Table de la base de donnée à utiliser; le format est bd.table
	 * @param $champClefPrimaire La colonne à utiliser comme valeur de clef primaire (qui sera retournée si l'élément est sélectionné)
	 * @param $champNom La colonne à utiliser comme nom à afficher à l'usager.
	 * @param $selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
	 * @param $prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
	 * @param $prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
	 * @param $champNomIsLangStringId
	 * @param $permetValeurNulle, TRUE ou FALSE
	 * @param $nullValueCaption, string displayed for the null value
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 * @return string L'élément select généré
	 */
	function genererDeResults($resultats, $champClefPrimaire, $champNom, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $champNomIsLangStringId, $permetValeurNulle, $nullValueCaption = ' - - - ', $additionalSelectAttribute)
	{
		$retval = "";
		$retval.= "<select id='$prefixeNomSelectUsager$prefixeNomSelectObjet' name='$prefixeNomSelectUsager$prefixeNomSelectObjet' $additionalSelectAttribute>\n";
		if ($permetValeurNulle == true)
		{
			$retval.= "<option value=''>$nullValueCaption</option>\n";
		}

		if (!empty($resultats))
		{
			foreach ($resultats as $key => $value)
			{
				$retval.= "<option "; //echo "$value[$champClefPrimaire],$selectedClefPrimaire<br>";
				//echo "value[champClefPrimaire]=$value[$champClefPrimaire] VS selectedClefPrimaire = $selectedClefPrimaire<br>\n";
				if ($value[$champClefPrimaire] == $selectedClefPrimaire)
				{
					$retval.= "SELECTED ";
				}
				if ($champNomIsLangStringId == true)
				{
					if (!empty($value[$champNom]))
					{
						$langstring = new Langstring($value[$champNom]);
						if ($langstring -> IsEmpty())
						{
							$nom = $value[$champClefPrimaire].' (ID affiché car Langstring vide)';
						}
						else
						{
							$nom = $langstring -> GetString();
						}
					}
					else
					{
						$nom = $value[$champClefPrimaire]." (ID affiché car Langstring n'existe pas)";
					}
				}
				else
				{
					$nom = $value[$champNom];
				}
				$nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
				$primary_key = htmlentities($value[$champClefPrimaire], ENT_QUOTES, 'UTF-8');
				$retval.= "value='$primary_key'>$nom</option>\n";
			}
		}
		else
			if ($permetValeurNulle == false)
			{
				echo "<h1>GenerateurFormSelect::genererDeResults(): Erreur: Aucun résultats à sélectionner mais valeur nulle non permise!</h1>\n";
			}
		$retval.= "</select>\n";
		return $retval;
	}

	/**Permet de générer un élément HTML select et de récupérer le résultat, à partir de tous les enregistrements d'une table de la base de donnée.
	*
	* @param $table Table de la base de donnée à utiliser; le format est bd.table
	* @param $champClefPrimaire La colonne à utiliser comme valeur de clef
	* primaire (qui sera retournée si l'élément est sélectionné)
	* @param $champNom La colonne à utiliser comme nom à afficher à l'usager.
	* @param $selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
	* @param $prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
	* @param $prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
	* @param $champNomIsLangStringId
	* @param $permetValeurNulle, TRUE ou FALSE
	* @param $nullValueCaption, string displayed for the null value
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 	* @return string L'élément select généré
	*/
	static function generateFromTable($table, $champClefPrimaire, $champNom, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $champNomIsLangStringId, $permetValeurNulle, $nullValueCaption = ' - - - ', $additionalSelectAttribute = null)
	{
		global $db;
		$db -> ExecuterSql("SELECT $champClefPrimaire,  $champNom FROM $table", $resultats, false);
		return self::genererDeResults($resultats, $champClefPrimaire, $champNom, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $champNomIsLangStringId, $permetValeurNulle, $nullValueCaption, $additionalSelectAttribute);
	}

	/**Permet de générer un élément HTML select et de récupérer le résultat à partir d'une requête SQL.
			* Il est essentiel que la selelection inclue la colonne $champNom.
			*
			* @param $table Table de la base de donnée à utiliser; le format est bd.table
			* @param $champClefPrimaire La colonne à utiliser comme valeur de clef primaire (qui sera retournée si l'élément est sélectionné)
			* @param $champNom La colonne à utiliser comme nom à afficher à l'usager.
			* @param $selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
			* @param $prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
			* @param $prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
			* @param $champNomIsLangStringId
			* @param $permetValeurNulle, TRUE ou FALSE
			* @param $nullValueCaption, string displayed for the null value
				 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
			* @return L'élément select généré
			*/
	function genererDeSelect($select, $champClefPrimaire, $champNom, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $champNomIsLangStringId, $permetValeurNulle, $nullValueCaption = ' - - - ', $additionalSelectAttribute = null)
	{
		$this -> mAbstractBd -> ExecuterSql($select, $resultats, false);
		return $this -> genererDeResults($resultats, $champClefPrimaire, $champNom, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $champNomIsLangStringId, $permetValeurNulle, $nullValueCaption, $additionalSelectAttribute);
	}

	/**Permet de générer l'interface à partir de tableaux contenant les valeurs.
			  *
			 * Les tableaux sont à deux dimensions, selont le format suivant: tab[row_num][field_num]
			 * field_num: [0] = La valeur de la clef primaire (qui sera retournée si l'élément est sélectionné)
			 * field_num: [1] = Le nom de la valeur, sera affiché à l'usager.
			 *
			 * @param $tab Le tableau
				* @param $selectedClefPrimaire Optionnel.  Quelle entrée doit-on sélectionner par défaut.  Entrer "null" pour que le choix vide soit choisi.
				* @param $prefixeNomSelectUsager Un préfixe arbitraire choisi par l'usager pour assurer l'unicité
				* @param $prefixeNomSelectObjet Un préfixe arbitraire choisi par l'objet ayant appelé la fonction pour assurer l'unicité
				* @param $permetValeurNulle, TRUE ou FALSE
				* @param $nullValueCaption, string displayed for the null value
				* @param $additionalSelectAttribute will be appended inside the
				* select tag.  For example: "onclick='submit();'"

			 */
	public static function generateFromArray($tab, $selectedClefPrimaire, $prefixeNomSelectUsager, $prefixeNomSelectObjet, $permetValeurNulle, $nullValueCaption = ' - - - ', $additionalSelectAttribute = "")
	{
		$retval = "";
		$retval.= "<select id='$prefixeNomSelectUsager$prefixeNomSelectObjet' name='$prefixeNomSelectUsager$prefixeNomSelectObjet' $additionalSelectAttribute>\n";
		if ($permetValeurNulle == true)
		{
			$retval.= "<option value=''>$nullValueCaption</option>\n";
		}

		foreach ($tab as $value)
		{
			$retval.= "<option "; //echo "$value[$champClefPrimaire],$selectedClefPrimaire<br>";
			if ($value[0] == $selectedClefPrimaire)
			{
				$retval.= "SELECTED ";
			}

			$nom = $value[1];
			$nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
			$primary_key = htmlentities($value[0], ENT_QUOTES, 'UTF-8');
			$retval.= "value='$primary_key'>$nom</option>\n";
		}
		$retval.= "</select>\n";
		return $retval;
	}

	/**retourne la réponse au select généré par  genererDeBD(
			 * @param $prefixeNomSelectUsager @see  genererDeBD
			 * @param $prefixeNomSelectObjet @see genererDeBD
			 * @return Le résultat, retourne une chaîne vide si aucune entrée est sélectionnée.  Assurez vous de suivre cette convention lorsque vous écrivez des générateurs.
			 */
	public static function getResult($prefixeNomSelectUsager, $prefixeNomSelectObjet)
	{ //echo"<h1>GenerateurFormSelect::getResultat()".$prefixeNomSelectUsager . $prefixeNomSelectObjet."</h1>";
		//   print_r($_REQUEST);
		return $_REQUEST[self::getRequestIndex($prefixeNomSelectUsager, $prefixeNomSelectObjet)];
	}

	/**retourne l'index de l'array $_REQUEST où se trouve la réponse réponse au select généré par les autres fonctions de la classe
		* @param $prefixeNomSelectUsager @see  genererDeBD
		* @param $prefixeNomSelectObjet @see genererDeBD
		* @return Le résultat
		*/
	public static function getRequestIndex($prefixeNomSelectUsager, $prefixeNomSelectObjet)
	{ //echo"<h1>GenerateurFormSelect::getResultat()".$prefixeNomSelectUsager . $prefixeNomSelectObjet."</h1>";
		//   print_r($_REQUEST);
		return $prefixeNomSelectUsager.$prefixeNomSelectObjet;
	}

	/**retourne si les résultats d'un elect sont présents dans la variable $_REQUEST 
		 * @param $prefixeNomSelectUsager @see  genererDeBD
		 * @param $prefixeNomSelectObjet @see genererDeBD
		 * @return true ou false
		 */
	function isPresent($prefixeNomSelectUsager, $prefixeNomSelectObjet)
	{ //echo"<h1>GenerateurFormSelect::getResultat()".$prefixeNomSelectUsager . $prefixeNomSelectObjet."</h1>";
		//   print_r($_REQUEST);
		return isset($_REQUEST[$this -> getRequestIndex($prefixeNomSelectUsager, $prefixeNomSelectObjet)]);
	}
} /* end class GenerateurFormSelect */
?>