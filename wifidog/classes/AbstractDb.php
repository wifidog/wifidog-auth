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
 * @subpackage Database
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */
require_once('classes/Dependency.php');
/**
 * Database Abstraction class, deprecated, this should be transitioned to PDO over time
 *
 * @package    WiFiDogAuthServer
 * @subpackage Database
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2004-2006 Benoit Grégoire, Technologies Coeus inc.
 */
class AbstractDb
{
    /* Properties used for statistics */
    private $construct_start_time;
    private $sql_total_time;
    private $sql_num_select_querys;
    private $sql_select_total_time;
    private $sql_num_select_unique_querys;
    private $sql_select_unique_total_time;
    private $sql_num_update_querys;
    private $sql_update_total_time;
    private $sql_executed_queries_array;

    private static $object;

    /** Note that you should call the first instance of AbstractDb as soon as possible to get reliable SQL vs PHP statistics.*/
    public static function &getObject() {
        if (self::$object==null)
        {
            self::$object=new self();
        }
        return self::$object;
    }
    /** Constructor */
    private function __construct()  {
        $this->construct_start_time = microtime();
    }

    // Connects to PostgreSQL database
    function connect($db_name)
    {
        // Grab default database name from config file
        if ($db_name == NULL)
        $db_name = CONF_DATABASE_NAME;

        // Build connection string
        $conn_string = "host=".CONF_DATABASE_HOST." dbname=$db_name user=".CONF_DATABASE_USER." password=".CONF_DATABASE_PASSWORD."";
        // Try connecting and hide warning, errors
        if ( !dependency::check('pgsql') )
        throw new Exception(_("It appears the postgresql module isn't loaded"));
        
        $ptr_connexion = @ pg_pconnect($conn_string);

        // Throw an exception if anything went wrong
        if ($ptr_connexion == FALSE)
        throw new Exception(sprintf(_("Unable to connect to database on %s"), CONF_DATABASE_HOST));

        return $ptr_connexion;
    }

    /**
     * Execute an SQL query and returns the result set or throws an error
     *
     * @param $sql SQL query to execute
     * @param $resultSet A 2-dimensionnal array containing result rows, NULL if empty
     * @param $debug When set to true the function will spit out debug informations
     * @return TRUE indicated the query went fine, FALSE something went wrong
     */
    function execSql($sql, & $resultSet, $debug = false)
    {
        // Get a connection handle
        $connection = $this->connect(NULL);

        // In debug mode spit out the SQL query
        if ($debug == TRUE)
        {
            // Header
            echo "<hr/><p/>execSql() : "._("SQL Query")."<br/>\n<pre>{$sql}</pre></p>\n";

            // Prepend EXPLAIN statement to the SQL query
            $result = @pg_query($connection, "EXPLAIN ".$sql);
            if($result) {
                echo "<p>"._("Query plan")." :<br/>\n";
                $plan_array = pg_fetch_all($result);

                foreach ($plan_array as $plan_line)
                echo $plan_line['QUERY PLAN']."<br/>\n";
                echo "</p>\n";
            }

        }

        // Start the clockwatch
        $sql_starttime = microtime();
        $result = @pg_query($connection, $sql);
        $sql_endtime = microtime();

        $sql_timetaken = $this->logQueries($sql, 'SELECT', $sql_starttime, $sql_endtime);

        if ($debug == TRUE)
        echo "<p>".sprintf(_("Elapsed time for query execution : %.6f second(s)"), $sql_timetaken)."</p>\n";

        if ($result == FALSE)
        {
            echo "<p>execSql() : "._("An error occured while executing the following SQL query")." :<br>{$sql}</p>";
            echo "<p>"._("Error message")." : <br/>".pg_last_error($connection)."</p>";
            echo "<p>"._("Backtrace:")."</p>";
            echo "<pre>";
            $btArray = debug_backtrace();
            foreach($btArray as $index=>$bt) {
                printf("#%d %s(%d): %s%s%s()\n", $index, $bt['file'], $bt['line'], $bt['class'], $bt['type'], $bt['function']);
            }
            echo "</pre>";
            $resultSet = NULL;
            $return_value = FALSE;
        }
        else
        if (pg_num_rows($result) == 0)
        {
            $resultSet = NULL;
            $return_value = TRUE;
        }
        else
        {
            $resultSet = pg_fetch_all($result);
            $return_value = TRUE;
            if ($debug)
            {
                $num_rows = pg_num_rows($result);
                echo "<p>execSql() : ".sprintf(_("The query returned %d results"), $num_rows)." :<br/>\n<table>";
                if ($resultSet != NULL)
                {
                    // Displaying column names only once
                    echo "<tr>\n";
                    while (list ($col_name, $col_content) = each($resultSet[0]))
                    echo "<th>$col_name</th>\n";
                    echo "</TR>\n";
                }
                while ($resultSet != NULL && list ($key, $value) = each($resultSet))
                {
                    echo "<tr>\n";
                    while ($value != NULL && list ($col_name, $col_content) = each($value))
                    echo "<td>$col_content</td>\n";
                    echo "</tr>\n";
                }
                // Reset the array pointer to the beginning
                reset($resultSet);
                echo "</table></p><hr/>\n";
            }
        }
        return $return_value;
    }

        /**
     * Execute an SQL query and returns the RAW postgresql result handle or throws an error
     * This is NOT for general use, as it breaks abstraction
     * @param $sql SQL query to execute
     * @param $resultSet The postgresql result handle
     * @param $debug When set to true the function will spit out debug informations
     * @return TRUE indicated the query went fine, FALSE something went wrong
     */
    function execSqlRaw($sql, & $resultSet, $debug = false)
    {
        // Get a connection handle
        $connection = $this->connect(NULL);

        // In debug mode spit out the SQL query
        if ($debug == TRUE)
        {
            // Header
            echo "<hr/><p/>execSql() : "._("SQL Query")."<br/>\n<pre>{$sql}</pre></p>\n";

            // Prepend EXPLAIN statement to the SQL query
            $result = @pg_query($connection, "EXPLAIN ".$sql);
            if($result) {
                echo "<p>"._("Query plan")." :<br/>\n";
                $plan_array = pg_fetch_all($result);

                foreach ($plan_array as $plan_line)
                echo $plan_line['QUERY PLAN']."<br/>\n";
                echo "</p>\n";
            }

        }

        // Start the clockwatch
        $sql_starttime = microtime();
        $result = @pg_query($connection, $sql);
        $sql_endtime = microtime();

        $sql_timetaken = $this->logQueries($sql, 'SELECT', $sql_starttime, $sql_endtime);

        if ($debug == TRUE)
        echo "<p>".sprintf(_("Elapsed time for query execution : %.6f second(s)"), $sql_timetaken)."</p>\n";

        if ($result == FALSE)
        {
            echo "<p>execSql() : "._("An error occured while executing the following SQL query")." :<br>{$sql}</p>";
            echo "<p>"._("Error message")." : <br/>".pg_last_error($connection)."</p>";
            echo "<p>"._("Backtrace:")."</p>";
            echo "<pre>";
            $btArray = debug_backtrace();
            foreach($btArray as $index=>$bt) {
                printf("#%d %s(%d): %s%s%s()\n", $index, $bt['file'], $bt['line'], $bt['class'], $bt['type'], $bt['function']);
            }
            echo "</pre>";
            $resultSet = NULL;
            $return_value = FALSE;
        }
        else
        if (pg_num_rows($result) == 0)
        {
            $resultSet = NULL;
            $return_value = TRUE;
        }
        else
        {
            $resultSet = $result;
            $return_value = TRUE;
        }
        return $return_value;
    }
    
    /* Logs a sql query for profiling purposes */
    function logQueries($sql, $type, $sql_starttime, $sql_endtime)
    {
        $parts_of_starttime = explode(' ', $sql_starttime);
        $sql_starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
        $parts_of_endtime = explode(' ', $sql_endtime);
        $sql_endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
        $sql_timetaken = $sql_endtime - $sql_starttime;
        if(defined("LOG_SQL_QUERIES") && LOG_SQL_QUERIES == true) {

            if (!isset ($this->sql_executed_queries_array))
            {
                $this->sql_executed_queries_array = array ();
            }
            if (!array_key_exists($sql, $this->sql_executed_queries_array))
            {
                $this->sql_executed_queries_array[$sql] = array ();
                $this->sql_executed_queries_array[$sql]['num'] = 0;
                $this->sql_executed_queries_array[$sql]['total_time'] = 0;
            }

            $this->sql_executed_queries_array[$sql]['num'] = $this->sql_executed_queries_array[$sql]['num'] + 1;
            $this->sql_executed_queries_array[$sql]['type'] = $type;
            $this->sql_executed_queries_array[$sql]['total_time'] = $this->sql_executed_queries_array[$sql]['total_time'] + $sql_timetaken;

            $this->sql_total_time += $sql_timetaken;

            switch ($type)
            {
                case 'SELECT' :
                    $this->sql_num_select_querys ++;
                    $this->sql_select_total_time += $sql_timetaken;
                    break;
                case 'SELECT_UNIQUE' :
                    $this->sql_num_select_unique_querys ++;
                    $this->sql_select_unique_total_time += $sql_timetaken;
                    break;
                case 'UPDATE' :
                    $this->sql_num_update_querys ++;
                    $this->sql_update_total_time += $sql_timetaken;
                    break;
                default :
                    echo "Error: AbstractDb::SqlLog(): Unknown query type: $type";
            }
        }
        return $sql_timetaken;
    }

    /** Get log results (profiling has to be enabled).*/
    public function getSqlQueriesLog()
    {
        $retval = "";

        /* PHP time */
        $parts_of_starttime = explode(' ', $this->construct_start_time);
        $php_starttime = $parts_of_starttime[0] + $parts_of_starttime[1];
        $parts_of_endtime = explode(' ', microtime());
        $php_endtime = $parts_of_endtime[0] + $parts_of_endtime[1];
        $php_timetaken = $php_endtime - $php_starttime;
        $display_php_total_time = number_format($php_timetaken, 3); // optional
        /* SQL time */
        $display_sql_total_time = number_format($this->sql_total_time, 3); // optional
        $sql_num_querys = $this->sql_num_select_querys + $this->sql_num_select_unique_querys + $this->sql_num_update_querys;

        $select_time_fraction = number_format(100 * ($this->sql_select_total_time / $this->sql_total_time), 0) . "%";
        $select_unique_time_fraction = number_format(100 * ($this->sql_select_unique_total_time / $this->sql_total_time), 0) . "%";
        $update_time_fraction = number_format(100 * ($this->sql_update_total_time / $this->sql_total_time), 0) . "%";

        /* Display */
        $sql_php_time_fraction = number_format(100 * ($this->sql_total_time / $display_php_total_time), 0) . "%";
        $retval .= "<div class='content'>\n";
        $retval .= "<p>$sql_num_querys queries took $display_sql_total_time second(s)\n";
        $retval .= "({$this->sql_num_select_querys} SELECT ($select_time_fraction), {$this->sql_num_select_unique_querys} SELECT UNIQUE ($select_unique_time_fraction), {$this->sql_num_update_querys} UPDATE ($update_time_fraction)) \n";
        $retval .= "representing $sql_php_time_fraction of the $display_php_total_time seconds total execution time</p>";
        $retval .= "</div>\n";

        uasort($this->sql_executed_queries_array, "cmp_query_time");
        $this->sql_executed_queries_array = array_reverse($this->sql_executed_queries_array, true);
        $retval .= "<div class='content'>Sorted by execution time: <pre>\n";
        $retval .= stripslashes(var_export($this->sql_executed_queries_array, true));
        $retval .= "</pre></div>\n";
        return $retval;
    }
    /**
     * Returns a string in a compatible / secure way for storing in the database
     *
     * @param $string The string to clean up
     * @return The cleaned-up string
     */
    function escapeString($string)
    {
        // WARNING : magic quotes must be off
        return pg_escape_string($string);
    }

    /**
     * Returns a cleaned-up binary string BLOG for storing in ByteA fields
     *
     * @param $string The string to clean up
     * @return The cleaned-up string
     */

    function escapeBinaryString($string)
    {
        return pg_escape_bytea($string);
    }

    /**
     * Reverts a ByteA escape to raw binary
     *
     * @param $string The string to clean up
     * @return The cleaned-up string
     */

    function unescapeBinaryString($string)
    {
        return pg_unescape_bytea($string);

    }

    /**
     * Executes an SQL for which, we predict to get a unique match, if that's not the case, this function will throw an error message
     *
     * @param string $sql    SQL query to run
     * @param array  $retRow un array des colonnes de la rangée retournée, NULL si aucun résultats.
     * @param bool   $debug  Si TRUE, affiche les résultats bruts de la requête
     * @param bool   $silent If set to true, no error message will be shown
     *
     * @return TRUE si la requete a été effectuée avec succés, FALSE autrement.
     */
    function execSqlUniqueRes($sql, & $retRow, $debug = false, $silent = false)
    {
        $retval = true;

        if ($debug == true)
        echo "<hr/><p>"._("SQL Query")." : <br/><pre>{$sql}</pre></p>";

        // Get a connection handle
        $connection = $this->connect(NULL);
        $sql_starttime = microtime();
        $result = @ pg_query($connection, $sql);
        $sql_endtime = microtime();

        $sql_timetaken = $this->logQueries($sql, 'SELECT_UNIQUE', $sql_starttime, $sql_endtime);
        if ($debug == TRUE)
        echo "<p>".sprintf(_("Elapsed time for query execution : %.6f second(s)"), $sql_timetaken)."</p>\n";

        if ($result == false) {
            if (!$silent) {
                echo "<p>execSqlUniqueRes() : "._("An error occured while executing the following SQL query")." :<br/>{$sql}</p>";
                echo "<p>"._("Error message")." : <br/>".pg_last_error($connection)."</p>";
            }

            $retval = false;
        } else {
            $resultSet = pg_fetch_all($result);
            $retRow = $resultSet[0];
            if (pg_num_rows($result) > 1)
            {
                echo "<p>execSqlUniqueRes() : "._("An error occured while executing the following SQL query")." : <br/>{$sql}</p>";
                echo "<p>".sprintf(_("The query returned %d results, although there should have been only one."), pg_num_rows($result))."</p>";
                $retval = false;
                $debug = true;
            }

            if ($debug)
            {
                $num_rows = pg_num_rows($result);
                echo "<p>execSqlUniqueRes(): ".sprintf(_("The query returned %d result(s)"), $num_rows)." : <br/>\n<table>\n";
                if ($resultSet != NULL)
                {
                    echo "<tr>\n";
                    while (list ($col_name, $col_content) = each($resultSet[0]))
                    {
                        echo "<th>$col_name</th>\n";
                    }
                    echo "</tr>\n";

                    while ($resultSet != NULL && list ($key, $value) = each($resultSet))
                    {
                        echo "<tr>\n";
                        while ($value != NULL && list ($col_name, $col_content) = each($value))
                        {
                            echo "<td>$col_content</td>\n";
                        }
                        echo "</tr>\n";
                    }
                    reset($resultSet);
                }
                echo "</table></p><hr/>\n";
            }

        }
        return $retval;
    }

    /**
     * Execute an SQL query meant to modify the database content
     *
     * @param $sql SQL update query to run
     * @param $debug Optional display debug output
     * @return false on failure, true otherwise
     */
    function execSqlUpdate($sql, $debug = false)
    {
        // Get a connection handle
        $connection = $this->connect(NULL);
        if ($debug == TRUE)
        echo "<hr/><p>execSqlUpdate(): "._("SQL Query")." : <br/>\n<pre>{$sql}</pre></p>\n";

        $sql_starttime = microtime();
        $result = @pg_query($connection, $sql);
        $sql_endtime = microtime();

        $sql_timetaken = $this->logQueries($sql, 'UPDATE', $sql_starttime, $sql_endtime);
        if ($debug == TRUE)
        {
            echo "<p>".sprintf(_("%d rows affected by the SQL query."), pg_affected_rows($result))."<br/>\n";
            echo sprintf(_("Elapsed time for query execution : %6f second(s)"), $sql_timetaken)."</p>\n";
        }

        if ($result == FALSE)
        {
            echo "<p>execSqlUpdate() : "._("An error occured while executing the following SQL query")." :<br>{$sql}</p>";
            echo "<p>"._("Error message")." : <br/>".pg_last_error($connection)."</p>";
            echo "<p>"._("Backtrace:")."</p>";
            echo "<pre>";
            $btArray = debug_backtrace();
            foreach($btArray as $index=>$bt) {
                @printf("#%d %s(%d): %s%s%s()\n", $index, $bt['file'], $bt['line'], $bt['class'], $bt['type'], $bt['function']);
            }
            echo "</pre>";
        }
        else
        if ($debug == TRUE)
        echo "<p>execSqlUpdate(): ".sprintf(_("%d rows affected by the SQL query."), pg_affected_rows($result))."</p><hr/>\n";
        return $result;
    }

    /**
     * Reads an entire large object and send it to the browser
     */
    function readFlushLargeObject($lo_oid)
    {
        $connection = $this->connect(NULL);

        // Large objects calls MUST be enclosed in transaction block
        // remember, large objects must be obtained from within a transaction
        pg_query($connection, "begin");
        $handle_lo = pg_lo_open($connection, $lo_oid, "r") or die("<h1>Error.. can't get handle</h1>");
        pg_lo_read_all($handle_lo) or die("<h1>Error, can't read large object.</h1>");
        // committing the data transaction
        pg_query($connection, "commit");
    }

    function importLargeObject($path)
    {
        $connection = $this->connect(NULL);

        // Large objects calls MUST be enclosed in transaction block
        // remember, large objects must be obtained from within a transaction
        pg_query($connection, "begin");
        $new_oid = pg_lo_import($connection, $path);
        // committing the data transaction
        pg_query($connection, "commit");

        return $new_oid;
    }

    function unlinkLargeObject($oid)
    {
        return $this->execSqlUpdate("BEGIN; SELECT lo_unlink($oid);  COMMIT;", false);
    }

    /**
     * Builds a string suitable for the databases interval datatype and returns it.
     *
     * @param $duration The source Duration object
     * @return a string suitable for storage in the database's interval datatype
     */
    function GetIntervalStrFromDurationArray($duration)
    {
        $str = '';
        if ($duration['years'] != 0)
        $str .= $duration['years'].' years ';
        if ($duration['months'] != 0)
        $str .= $duration['months'].' months ';
        if ($duration['days'] != 0)
        $str .= $duration['days'].' days ';

        if ($duration['hours'] != 0 || $duration['minutes'] != 0 || $duration['seconds'] != 0)
        {
            $str .= $duration['hours'].':'.$duration['minutes'].':'.$duration['seconds'];
        }
        return $str;
    }

    /**
     * Builds the internal duration Array from a databases interval datatype and returns it.
     *
     * @param $intervalstr A string in the database's interval datatype format
     * @return the internal representration on the Duration object
     */
    function GetDurationArrayFromIntervalStr($intervalstr)
    {
        $retval = null;
        if (empty ($intervalstr))
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
            $this->execSqlUniqueRes($sql, $retval, false);
        }
        return $retval;
    }
}
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
