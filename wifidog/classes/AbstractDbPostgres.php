<?php
/********************************************************************\
 * This program is free software; you can redistribute it and/or    *
 * modify it under the terms of the GNU General Public License as   *
 * published by the Free Software Foundation; either version 2 of   *
 * the License, or (at your option) any later version.              *
 *                                                             	    *
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
/**@file AbstractBd.php
 * @author Copyright (C) 2004 Technologies Coeus inc.
*/
error_reporting(E_ALL);
/** Classe statique, permet d'abstraire la connexion à la base de donnée
 */
class AbstractDb
{
	function connexionDb($db_name)
	{
		if ($db_name == NULL)
		{
			$db_name = CONF_DATABASE_NAME;
		}

		$conn_string = "host=".CONF_DATABASE_HOST." dbname=$db_name user=".CONF_DATABASE_USER." password=".CONF_DATABASE_PASSWORD."";
		$ptr_connexion = pg_connect($conn_string);

		if ($ptr_connexion == FALSE)
		{
			echo "<p class=warning>Unable to connect to database on ".CONF_DATABASE_HOST."</p>\n";
			return FALSE;
		}

		return $ptr_connexion;
	}

	/**Exécute la requête, et retourne le résultat.  Affiche l'erreur s'il y a lieu.
	 @param $sql Requête SELECT à exécuter
	 @param $returnResults un array à deux dimensions des rangées de résultats, NULL si aucun résultats.
	 @param $debug Si TRUE, affiche les résultats bruts de la requête
	 @return TRUE si la requete a été effectuée avec succès, FALSE autrement.
	*/
	function ExecSql($sql, & $returnResults, $debug=false)
	{
		$connection = $this -> connexionDb(NULL);
		if ($debug == TRUE)
		{
			echo "<hr /><p>ExecuterSql(): DEBUG: Requête:<br>\n<pre>$sql</pre></p>\n<p>Plan:<br />\n";
			$result = pg_query($connection, "EXPLAIN ".$sql);

			$plan_array = pg_fetch_all($result);
			foreach ($plan_array as $plan_line)
			{
				echo $plan_line['QUERY PLAN']."<br />\n";
			}
			echo "</p>\n";
		}

		$sql_starttime = microtime();
		$result = pg_query($connection, $sql);
		$sql_endtime = microtime();

		global $sql_total_time;
		global $sql_num_select_querys;
		$sql_num_select_querys ++;
		$parts_of_starttime = explode(' ', $sql_starttime);
		$sql_starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
		//echo "sql_starttime: $sql_starttime <br />\n";

		$parts_of_endtime = explode(' ', $sql_endtime);
		$sql_endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
		//echo "sql_endtime: $sql_endtime <br />\n";
		$sql_timetaken = $sql_endtime - $sql_starttime;
		//echo "sql_timetaken: $sql_timetaken <br />\n";

		$sql_total_time = $sql_total_time + $sql_timetaken;

		if ($debug == TRUE)
		{
			echo "<P>Temps écoulé pour la requête SQL: $sql_timetaken seconde(s)</P>\n";
		}

		if ($result == FALSE)
		{
			echo "<p>ExecuterSql(): ERREUR: Lors de l'exécution de la requête SQL:<br>$sql</p>";
			echo "<p>L'erreur est:<br>".pg_last_error($connection)."</p>";
			$returnResults = NULL;
			$return_value = FALSE;
		}
		else
			if (pg_num_rows($result) == 0)
			{
				$returnResults = NULL;
				$return_value = TRUE;
			}
			else
			{
				$returnResults = pg_fetch_all($result);
				$return_value = TRUE;
				if ($debug)
				{
					$num_rows = pg_num_rows($result);
					echo "<p>ExecuterSql(): DEBUG: Il y a $num_rows résultats:<br><TABLE class='spreadsheet'>";
					if ($returnResults != NULL)
					{
						//On affiche l'en-tête des colonnes une seule fois*/
						echo "<TR class='spreadsheet'>";
						while (list ($col_name, $col_content) = each($returnResults[0]))
						{
							echo "<TH class='spreadsheet'>$col_name</TH>";
						}
						echo "</TR>\n";
					}
					while ($returnResults != NULL && list ($key, $value) = each($returnResults))
					{
						echo "<TR class='spreadsheet'>";
						while ($value != NULL && list ($col_name, $col_content) = each($value))
						{
							echo "<TD class='spreadsheet'>$col_content</TD>";
						}
						echo "</TR>\n";
					}
					reset($returnResults);
					echo "</TABLE></p><hr />\n";
				}
			}
		return $return_value;
	}

	/**Retourne une chaine de caractère dans un format compatible pour stockage dans la bd
	 @param $chaine La chaîne de caractère à nettoyer
	 @return La chaîne nettoyée
	 */
	function EscapeString($chaine)
	{
		if (true) //if (!get_magic_quotes_gpc())
		{
			return pg_escape_string($chaine);
		}
		else
		{
			return ($chaine);
		}
	}

	/** Nettoye une chaine de caractère dans un format compatible bytea.
	 @param $chaine La chaîne de caractère à nettoyer
	 @return La chaîne nettoyée (escaped string)
	 */

	function EscapeBinaryString($chaine)
	{
		return pg_escape_bytea($chaine);

	}

	/** Reconverti une chaine de caractère en format bytea pur.
	 @param $chaine La chaîne de caractère 
	 @return La chaîne reconvertie  en format original (unescaped string)
	 */

	function UnescapeBinaryString($chaine)
	{
		return pg_unescape_bytea($chaine);

	}

	/**Exécute une requête pour laquelle on prévoit un résultat UNIQUE.  Si le résultat n'est pas unique, un avertissement est affiché
	 @param $sql Requête SELECT à exécuter
	 @param $retVal un array des colonnes de la rangée retournée, NULL si aucun résultats.
	 @param $debug Si TRUE, affiche les résultats bruts de la requête
	 @return TRUE si la requete a été effectuée avec succès, FALSE autrement.
	 */
	function ExecSqlUniqueRes($sql, & $retVal, $debug=false)
	{
		$retval = TRUE;
		if ($debug == TRUE)
		{
			echo "<hr /><p>Requête: <br><pre>$sql</pre></p>";
		}
		$connection = $this -> connexionDb(NULL);

		$sql_starttime = microtime();
		$result = pg_query($connection, $sql);
		$sql_endtime = microtime();

		global $sql_total_time;
		global $sql_num_select_unique_querys;
		$sql_num_select_unique_querys ++;

		$parts_of_starttime = explode(' ', $sql_starttime);
		$sql_starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
		//echo "sql_starttime: $sql_starttime <br />\n";

		$parts_of_endtime = explode(' ', $sql_endtime);
		$sql_endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
		//echo "sql_endtime: $sql_endtime <br />\n";
		$sql_timetaken = $sql_endtime - $sql_starttime;
		//echo "sql_timetaken: $sql_timetaken <br />\n";

		$sql_total_time = $sql_total_time + $sql_timetaken;

		if ($debug == TRUE)
		{
			echo "<P>Temps écoulé pour la requête SQL: $sql_timetaken seconde(s)</P>\n";
		}

		if ($result == FALSE)
		{
			echo "<p>ExecuterSqlResUnique(): ERREUR: Lors de l'exécution de la requête SQL:<br>$sql</p>";
			echo "<p>L'erreur est:<br>".pg_last_error($connection)."</p>";
			$retval = FALSE;
		}
		else
		{
			$returnResults = pg_fetch_all($result);
			$retVal = $returnResults[0];
			if (pg_num_rows($result) > 1)
			{
				echo "<p>ExecuterSqlResUnique(): ERREUR: Lors de l'exécution de la requête SQL:<br>$sql</p>";
				echo "<p>Il y a ".pg_num_rows($result)." résultats alors qu'il ne devrait y en avoir qu'un seul.</p>";
				$retval = FALSE;
				$debug = true;
			}


			if ($debug)
			{
				$num_rows = pg_num_rows($result);
				echo "<p>ExecuterSqlResUnique(): DEBUG: Il y a $num_rows résultats:<br><TABLE class='spreadsheet'>";
				if ($returnResults != NULL)
				{
					//On affiche l'en-tête des colonnes une seule fois*/
					echo "<TR class='spreadsheet'>";
					while (list ($col_name, $col_content) = each($returnResults[0]))
					{
						echo "<TH class='spreadsheet'>$col_name</TH>";
					}
					echo "</TR>\n";
				
				while ($returnResults != NULL && list ($key, $value) = each($returnResults))
				{
					echo "<TR class='spreadsheet'>";
					while ($value != NULL && list ($col_name, $col_content) = each($value))
					{
						echo "<TD class='spreadsheet'>$col_content</TD>";
					}
					echo "</TR>\n";
				}
				reset($returnResults);
				}
				echo "</TABLE></p><hr />\n";
			}

		}
		return $retval;
	}

	/**Exécute une requête visant à modifier la base de donnée, et donc ne retournant aucun résultat.
	 @param $sql Requête SELECT à exécuter
	 @param $debug Si TRUE, affiche la requête brute
	 */
	function ExecSqlUpdate($sql, $debug=false)
	{
		$connection = $this -> connexionDb(NULL);
		if ($debug == TRUE)
		{
			echo "<hr /><p>ExecuterSqlUpdate(): DEBUG: Requête:<br>\n<pre>$sql</pre></p>\n";
		}

		global $sql_num_update_querys;
		$sql_num_update_querys ++;

		$sql_starttime = microtime();
		$result = pg_query($connection, $sql);
		$sql_endtime = microtime();

		global $sql_total_time;
		$parts_of_starttime = explode(' ', $sql_starttime);
		$sql_starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
		//echo "sql_starttime: $sql_starttime <br />\n";

		$parts_of_endtime = explode(' ', $sql_endtime);
		$sql_endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
		//echo "sql_endtime: $sql_endtime <br />\n";
		$sql_timetaken = $sql_endtime - $sql_starttime;
		//echo "sql_timetaken: $sql_timetaken <br />\n";

		$sql_total_time = $sql_total_time + $sql_timetaken;

		if ($debug == TRUE)
		{
			echo "<P>".pg_affected_rows($result)." rangées affectées par la requête SQL<br>\n";
			echo "Temps écoulé: $sql_timetaken seconde(s)</P>\n";
		}

		if ($result == FALSE)
		{
			echo "<p>ExecuterSqlResUnique(): ERREUR: Lors de l'exécution de la requête SQL:<br><pre>$sql</pre></p>";
			echo "<p>L'erreur est:<br>".pg_last_error()."<br>".pg_result_error($result)."</p>";
		}
		else
		{
			if ($debug == TRUE)
			{
				echo "<p>ExecuterSqlUpdate(): DEBUG: ".pg_affected_rows($result)." rangée(s) affectée(s)</p><hr />\n";
			}
			return $result;
		}
	}

	/** Builds a string suitable for the databases interval datatype and returns it.
	 @param $duration The source Duration object
	 @return a string suitable for storage in the database's interval datatype
	 */
	function GetIntervalStrFromDuration($duration)
	{
		$str = '';
		if ($duration -> GetYears() != 0)
			$str.= $duration -> GetYears().' years ';
		if ($duration -> GetMonths() != 0)
			$str.= $duration -> GetMonths().' months ';
		if ($duration -> GetDays() != 0)
			$str.= $duration -> GetDays().' days ';

		if ($duration -> GetHours() != 0 || $duration -> GetMinutes() != 0 || $duration -> GetSeconds() != 0)
		{
			$str.= $duration -> GetHours().':'.$duration -> GetMinutes().':'.$duration -> GetSeconds();
		}
		return $str;
	}

	/** Builds the internal duration Array from a databases interval datatype and returns it.
	 @param $intervalstr A string in the database's interval datatype format
	 @return the internal representration on the Duration object
	 */
	function GetDurationArrayFromIntervalStr($intervalstr)
	{
		if (empty($intervalstr))
		{
			$retval['years'] = 0;
			$retval['months'] = 0;
			$retval['days'] = 0;
			$retval['hours'] = 0;
			$retval['minutes'] = 0;
			$retval['seconds'] = 0;
		}
		else
		{
			$sql = "SELECT EXTRACT (year FROM INTERVAL '$intervalstr') AS years, EXTRACT (month FROM INTERVAL '$intervalstr') AS months, EXTRACT (day FROM INTERVAL '$intervalstr') AS days, EXTRACT (hour FROM INTERVAL '$intervalstr') AS hours, EXTRACT (minutes FROM INTERVAL '$intervalstr') AS minutes, EXTRACT (seconds FROM INTERVAL '$intervalstr') AS seconds";
			$this -> ExecuterSqlResUnique($sql, $retval, false);
		}
		return $retval;
	}

} /* end class AbstractDb */
?>