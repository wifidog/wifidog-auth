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
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2005 Benoit Grégoire, Technologies Coeus inc.
 */
class FormSelectGenerator
{
	// Private class attributes
	private $mAbstractBd;

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $db;
		$this->mAbstractBd = $db;
	}

	/**
	 * Generates an HTML SELECT element from an SQL result set
	 *
	 * @param $resultSet : The SQL result set
	 * @param $primaryKeyField : The column to use the primary key
	 * @param $displayField : The column to display to the user
	 * @param $selectedPrimaryKey : Optional.  Which element should be selected by default, use null to select the first one
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @param $displayFieldIsLangstring
	 * @param $allowNullValues, TRUE or FALSE
	 * @param $nullCaptionString, string displayed in place of null values
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 * @return string The HTML SELECT element definition string
	 */
	static function generateFromResultSet($resultSet, $primaryKeyField, $displayField, $selectedPrimaryKey, $userPrefix, $objectPrefix, $displayFieldIsLangstring, $allowNullValues, $nullCaptionString = ' - - - ', $additionalSelectAttribute)
	{
		$retval = "";
		$retval .= "<select id='{$userPrefix}{$objectPrefix}' name='{$userPrefix}{$objectPrefix}' {$additionalSelectAttribute}>\n";
		if ($allowNullValues === true)
		{
			$retval .= "<option value=''>{$nullCaptionString}</option>\n";
		}

		if (!empty ($resultSet))
		{
			foreach ($resultSet as $key => $value)
			{
				$retval .= "<option ";
				if ($value[$primaryKeyField] == $selectedPrimaryKey)
				{
					$retval .= 'selected="selected" ';
				}
				if ($displayFieldIsLangstring === true)
				{
					if (!empty ($value[$displayField]))
					{
						$langstring = new Langstring($value[$displayField]);
						if ($langstring->IsEmpty())
						{
							$nom = $value[$primaryKeyField]._(" (Empty langstring, ID is displayed)");
						}
						else
						{
							$nom = $langstring->GetString();
						}
					}
					else
					{
						$nom = $value[$primaryKeyField]._(" (Empty langstring, ID is displayed)");
					}
				}
				else
				{
					$nom = $value[$displayField];
				}
				$nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
				$primary_key = htmlentities($value[$primaryKeyField], ENT_QUOTES, 'UTF-8');
				$retval .= "value='$primary_key'>$nom</option>\n";
			}
		}
		else
			if ($allowNullValues === false)
			{
				echo "<h1>FormSelectGenerator::generateFromResultSet(): Error: No results found, NULL value not allowed</h1>\n";
			}
		$retval .= "</select>\n";
		return $retval;
	}

	/**
	 * Generates an HTML SELECT element from an SQL results set of a single database table dump
	 *
	 * @param $table : The database table
	 * @param $primaryKeyField : The column to use the primary key
	 * @param $displayField : The column to display to the user
	 * @param $selectedPrimaryKey : Optional.  Which element should be selected by default, use null to select the first one
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @param $displayFieldIsLangstring
	 * @param $allowNullValues, TRUE or FALSE
	 * @param $nullCaptionString, string displayed in place of null values
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 * @return string The HTML SELECT element definition string
	 */
	static function generateFromTable($table, $primaryKeyField, $displayField, $selectedPrimaryKey, $userPrefix, $objectPrefix, $displayFieldIsLangstring=false, $allowNullValues=false, $nullCaptionString = ' - - - ', $additionalSelectAttribute = null)
	{
		global $db;
		$results = null;
		$db->execSql("SELECT $primaryKeyField,  $displayField FROM $table", $results, false);
		return self :: generateFromResultSet($results, $primaryKeyField, $displayField, $selectedPrimaryKey, $userPrefix, $objectPrefix, $displayFieldIsLangstring, $allowNullValues, $nullCaptionString, $additionalSelectAttribute);
	}

	/**
	 * Generates an HTML SELECT element from an SQL call
	 *
	 * @param $sql : The SQL query to run
	 * @param $primaryKeyField : The column to use the primary key
	 * @param $displayField : The column to display to the user
	 * @param $selectedPrimaryKey : Optional.  Which element should be selected by default, use null to select the first one
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @param $displayFieldIsLangstring
	 * @param $allowNullValues, TRUE or FALSE
	 * @param $nullCaptionString, string displayed in place of null values
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 * @return string The HTML SELECT element definition string
	 */
	function genererDeSelect($sql, $primaryKeyField, $displayField, $selectedPrimaryKey, $userPrefix, $objectPrefix, $displayFieldIsLangstring, $allowNullValues, $nullCaptionString = ' - - - ', $additionalSelectAttribute = null)
	{
		$results = null;
		$this->mAbstractBd->ExecuterSql($sql, $results, false);
		return $this->generateFromResultSet($results, $primaryKeyField, $displayField, $selectedPrimaryKey, $userPrefix, $objectPrefix, $displayFieldIsLangstring, $allowNullValues, $nullCaptionString, $additionalSelectAttribute);
	}

	/**
	 * Generates an HTML SELECT element from an array containing the data
	 *
	 * You must provide a 2-dimensionnal array such as tab[row_num][field_num]
	 * field_num: [0] = The value of the primary key (that will be returned if the element is selected)
	 * field_num: [1] = The name of the value, displayed to the user
	 *
	 * @param $array : The SQL query to run
	 * @param $selectedPrimaryKey : Optional.  Which element should be selected by default, use null to select the first one
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @param $allowNullValues, TRUE or FALSE
	 * @param $nullCaptionString, string displayed in place of null values
	 * @param $additionalSelectAttribute will be appended inside the select tag.  For example: "onclick='submit();'"
	 * @return string The HTML SELECT element definition string For example: "onclick='submit();'
	 */
	public static function generateFromArray($array, $selectedPrimaryKey, $userPrefix, $objectPrefix, $allowNullValues, $nullCaptionString = ' - - - ', $additionalSelectAttribute = "", $max_length = -1)
	{
		$retval = "";
		$retval .= "<select id='{$userPrefix}{$objectPrefix}' name='{$userPrefix}{$objectPrefix}' {$additionalSelectAttribute}>\n";
		if ($allowNullValues == true)
		{
			$retval .= "<option value=''>{$nullCaptionString}</option>\n";
		}

		foreach ($array as $value)
		{
			$retval .= "<option ";
			if ($value[0] == $selectedPrimaryKey)
			{
				$retval .= 'selected="selected" ';
			}

			$name = $value[1];
			// Restrict to max length and append "..."
			if($max_length == -1)
				$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
			else if(strlen($name) > $max_length)
				$name = substr($name, 0, $max_length)."...";

			//$name = htmlspecialchars($max_length != null ? $name : strlen($name) > $max_length ? substr($name, 0, $max_length)."..." : $name, ENT_QUOTES, 'UTF-8');
			$primary_key = htmlspecialchars($value[0], ENT_QUOTES, 'UTF-8');
			$retval .= "value='{$primary_key}'>{$name}</option>\n";
		}
		$retval .= "</select>\n";
		return $retval;
	}

	/**
	 * Returns the element selected
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @return The result, returns an empty string if not found
	 */
	public static function getResult($userPrefix, $objectPrefix)
	{
		return $_REQUEST[self :: getRequestIndex($userPrefix, $objectPrefix)];
	}

	/**
	 * Returns the array index in $_REQUEST where the response is found
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @return The index
	 */
	public static function getRequestIndex($userPrefix, $objectPrefix)
	{
		return $userPrefix.$objectPrefix;
	}

	/**
	 * Tells if a value exists in the HTTP response
	 * @param $userPrefix : An arbitrary prefix, chosen by the user, to guarantee unicity
	 * @param $objectPrefix : An arbitrary prefix, chosen by the calling object, to guarantee unicity
	 * @return true or false
	 */
	function isPresent($userPrefix, $objectPrefix)
	{
		return isset ($_REQUEST[$this->getRequestIndex($userPrefix, $objectPrefix)]);
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

