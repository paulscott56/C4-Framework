<?php

/**
 * Database management object
 *
 * The dbTableManager class is the main (core) database management object. It can be used to create/drop tables
 * as well as index and manage tables and databases in a variety of RDBM's
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   core
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: dbtablemanager_class_inc.php 19743 2010-11-17 07:21:31Z davidwaf $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global entry point $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check


/**
 * Logging object
 */
require_once "lib/logging.php";

/**
 * database table manager
 *
 * The database manager object can be used to create, drop and manipulate table and database structure,
 * as well as manipulate db schema as a whole
 *
 * @category  Chisimba
 * @package   core
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class dbTableManager extends object {

    /**
     * The current table name that we are working with
     *
     * @access public
     * @var    string - default NULL
     */
    public $_tableName = NULL;
    /**
     * The current database that we are working with
     *
     * @access public
     * @var    string - default NULL
     */
    public $_dbName = NULL;
    /**
     * The global error callback for dbTable errors
     *
     * @access public
     * @var    string
     */
    public $_errorCallback;
    /**
     * The database config object
     *
     * @access public
     * @var    object
     */
    public $objDBConfig;
    /**
     * The db object
     *
     * @access private
     * @var    object
     */
    private $_dbmanager = NULL;
    /**
     * The non Schema DB Object
     * We are instantiating this again, in case of first time install
     * This way, we can create the tables through the magic __call method
     * and get away with it
     *
     * @access private
     * @var    object
     */
    private $_db = NULL;
    public $appid;

    /**
     * Method to initialise the dbTableManager object.
     *
     * @access public
     * @param  string   $tableName     The name of the table this object encapsulates
     * @param  string   $dbName        The database name
     * @param  PEAR     $              ::MDB2_Schema $pearDb The PEAR::MDB2_Schema object to use (defaults to use the global connection)
     * @param  callback $errorCallback The name of a custom error callback function (defaults to the global)
     * @return void
     */
    public function init($dbName = NULL, $pearDbManager = NULL, $errorCallback = "globalPearErrorCallback") {
        $this->_errorCallback = $errorCallback;
        if ($pearDbManager == NULL) {
            $this->_dbmanager = $this->objEngine->getDbManagementObj();
            $this->_db = $this->objEngine->getDbObj();
        } else {
            $this->_dbmanager = $pearDbManager;
            $this->_db = $pearDb;
        }

        // check for PEAR Var_dump and initialise it,
        // otherwise just use regular PHP var_dump();
        if (class_exists('Var_Dump')) {
            $var_dump = array(
                'Var_Dump',
                'display',
            );
        } else {
            $var_dump = 'var_dump';
        }

        // Load up the config object and get the servername
        $this->objDBConfig = $this->getObject('altconfig', 'config');
        $this->_serverName = $this->objDBConfig->serverName();
        $this->appid = $this->objEngine->appid;

        // instantiate the MDB2 Management module
        //$this->_db = $this->_db->loadModule('Manager');
        // call_user_func($var_dump, $this->_dbmanager);
    }

    /**
     * Method to parse a database definition file by creating a Metabase schema format
     * parser object and passing the file contents as parser input data stream.
     *
     * @param  string $input_file            the path of the database schema file.
     * @param  array  $variables             an associative array that the defines the text
     *                                       string values that are meant to be used to replace the variables that are
     *                                       used in the schema description.
     * @param  bool   $fail_on_invalid_names (optional) make function fail on invalid
     *                                       names
     * @return mixed  TRUE on success, or a MDB2 error object
     * @access public
     */
    public function parseDbDefFile($input_file, $variables = array(), $fail_on_invalid_names = TRUE) {
        return $this->_dbmanager->parseDatabaseDefinitionFile($input_file, $variables,
                $fail_on_invalid_names, $structure = FALSE);
    }

    /**
     * Method to attempt to reverse engineer a schema structure from an existing MDB2
     * This method can be used if no xml schema file exists yet.
     * The resulting xml schema file may need some manual adjustments.
     *
     * @return mixed  MDB2_OK or array with all ambiguities on success, or a MDB2 error object
     * @access public
     */
    public function getDefFromDb() {
        return $this->_dbmanager->getDefinitionFromDatabase();
    }

    /**
     * Method to dump the database to a specified schema file
     * There are three options as to how to dump the db to file
     * 1. Structure only
     * 2. Content Only
     * 3. All - both Structure and content
     *
     * @access public
     * @param  string $option
     * @param  string $dumptype
     * @param  string $dumpfile
     * @return bool
     */
    public function dumpDatabaseToFile($option = 'dump', $dumptype = 'all', $dumpfile) {
        // lets set a time limit on this
        set_time_limit(0);

        if ($option == 'dump') {
            switch ($dumptype) {
                case 'structure':
                    $dump_what = MDB2_SCHEMA_DUMP_STRUCTURE;
                    break;

                case 'content':
                    $dump_what = MDB2_SCHEMA_DUMP_CONTENT;
                    break;

                default:
                    $dump_what = MDB2_SCHEMA_DUMP_ALL;
                    break;
            }

            $dump_config = array(
                'output_mode' => 'file',
                'output' => $dumpfile,
            );
            $definition = $this->_dbmanager->getDefinitionFromDatabase();
            $operation = $this->_dbmanager->dumpDatabase($definition, $dump_config, $dump_what);
            if (PEAR::isError($operation)) {
                die($operation->getMessage() . ' ' . $operation->getUserInfo());
            }
            return TRUE;
        }//if

        return FALSE;
    }

//func

    /**
     * Method to get the schema definition of a single table
     *
     * @param  string $table
     * @return array
     */
    public function getTableSchema($table) {
        $dbdef = $this->getDefFromDb();
        if (array_key_exists($table, $dbdef['tables'])) {
            return $dbdef['tables'][$table];
        } else {
            return FALSE;
        }
    }

    /**
     * Method to get a list of the fields in a table
     *
     * @param $table string Required
     */
    public function listTblFields($table) {
        return $this->_db->mgListTableFields($table);
    }

    /**
     * Method to get the debug strings from queries if neccessary
     *
     * @access private
     * @param  reference to       the management object $db
     * @param  string    $scope
     * @param  string    $message
     * @return string    message
     */
    private function printQueries($db, $scope, $message) {
        if ($scope == 'query') {
            return $message . $db->getOption('log_line_break');
        }
        MDB2_defaultDebugOutput($db, $scope, $message);
    }

    /**
     * Method to create a table
     * <pre>
     * $fields = array(
     *   'id' => array(
     *   'type'     => 'char',
     *   'length'   => 32
     *   'unsigned' => TRUE,
     *   'autoincrement'  => FALSE,
     *  ),
     *  'somename' => array(
     *   'type'     => 'text',
     *   'length'   => 12,
     *  ),
     * 'somedate'  => array(
     *   'type'     => 'date',
     *  ),
     * );
     * $table = 'sometable';
     * </pre>
     *
     * since we are on php5 we can use the magic __call() method to:
     * - load the manager module: $_db->loadModule('Manager', NULL, TRUE);
     * - redirect the method call to the manager module: $_db->manager->createTable('sometable', $fields);
     *
     * @param string $tableName
     * @param array  $fields
     */
    public function createTable($tableName, $fields, $options) {
        $puid = array(
            'puid' => array(
                'type' => 'integer',
                'length' => 50,
                'autoincrement' => TRUE,
            ),
        );
        $fields = array_merge($fields, $puid);
        if ($this->_db->phptype == 'mysql' || $this->_db->phptype == 'mysqli') {
            $this->_db->setOption('default_table_type', 'INNODB');
            $toptions = array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci',);
            $options = array_merge($toptions, $options);
            // do the table create.
            // we call on the actual MDB object, NOT the MDB::Schema object to do this.
            //try {
            $test = $this->_db->mgCreateTable($tableName, $fields, $options);
            //if($test === true)
            //{
            // create the "primary" index
            $this->createPK($tableName);
            // return a TRUE, simply because MDB::CreateTable returns void (wtf?)
            return TRUE;
            //}
            //else {
            //	echo $test; die();
            //}
            //}
            //catch(customException $e) {
            //	customException::cleanUp();
            //}
        } else {
            $this->_db->mgCreateTable($tableName, $fields, $options);
            // create the "primary" index
            $this->createPK($tableName);
            return TRUE;
        }
    }

    /**
     * drop an existing table
     *
     * @param  string $name name of the table that should be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropTable($name) {
        $ret = $this->_db->mgDropTable($name);
        return $ret;
    }

    /**
     * Method to create an index on the table
     *
     * @access public
     * @param  string $tableName
     * @param  string $keyname
     * @param  array  $index
     * @return bool   TRUE on success | FALSE on failure
     */
    public function createTableIndex($tableName, $keyname, $index, $trunc = FALSE) {
        if ($trunc != FALSE) {
            $this->_db->mgCreateIndex($tableName, $keyname, $index);
            return TRUE;
        } else {
            $keyname = substr($keyname, 1, 3) . rand(0, 999);
            $this->_db->mgCreateIndex($tableName, $keyname, $index);
            return TRUE;
        }
    }

    /**
     * Method to describe a pseudo PK id
     * Most RDBMS's besides MySQL do not have support for PK's so we fake it.
     *
     * @access public
     * @param  mixed  $tableName
     * @return bool   TRUE
     */
    public function createPK($tableName) {
        $primindex = array(
            'fields' => array(
                'id' => array('sorting' => 'ascending'),
            ),
            'primary' => TRUE,
        );

        $pname = 'pk' . rand(0, 99999);

        $this->createTableIndex($tableName, $pname, $primindex, TRUE);
        return TRUE;
    }

    /**
     * create a new database
     *
     * @param  string $db name of the database that should be created
     * @return bool   TRUE on success
     * @access public
     */
    public function createDb($db) {
        $ret = $this->_db->mgCreateDatabase($db);
        return $ret;
    }

    /**
     * drop an existing database
     *
     * @param  string $db of the database that should be dropped
     * @return bool   TRUE on success
     * @access public
     */
    public function dropDb($db) {
        $ret = $this->_db->mgDropDatabase($db);
        return $ret;
    }

    /**
     * list all databases
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDatabases() {
        $ret = $this->_db->mgListDatabases();
        return $ret;
    }

    /**
     * list all db users
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDbUsers() {
        $ret = $this->_db->mgListUsers();
        return $ret;
    }

    /**
     * list all views in the current database
     *
     * @return mixed  data array on success
     * @access public
     */
    public function listDbViews() {
        $ret = $this->_db->mgListViews();
        return $ret;
    }

    /**
     * list all functions in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listDbFunctions() {
        $ret = $this->_db->mgListFunctions();
        return $ret;
    }

    /**
     * list all tables in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listDbTables() {
        $ret = $this->_db->mgListTables();
        return $ret;
    }

    /**
     * list all fields in a tables in the current database
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableFields($table) {
        $ret = $this->_db->mgListTableFields($table);
        return $ret;
    }

    /**
     * get the stucture of a field into an array
     *
     * @param  string $table      name of the table on which the index is to be created
     * @param  string $name       name of the index to be created
     * @param  array  $definition associative array that defines properties of the index to be created.
     *                            Currently, only one property named FIELDS is supported. This property
     *                            is also an associative with the names of the index fields as array
     *                            indexes. Each entry of this array is set to another type of associative
     *                            array that specifies properties of the index that are specific to
     *                            each field.
     *
     *                            Currently, only the sorting property is supported. It should be used
     *                            to define the sorting direction of the index. It may be set to either
     *                            ascending or descending.
     *
     *                            Not all DBMS support index sorting direction configuration. The DBMS
     *                            drivers of those that do not support it ignore this property. Use the
     *                            function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                            Example
     *                            array(
     *                            'fields' => array(
     *                            'user_name' => array(
     *                            'sorting' => 'ascending'
     *                            ),
     *                            'last_login' => array()
     *                            )
     *                            )
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createIndex($table, $name, $definition) {
        $ret = $this->_db->mgCreateIndex($table, $name, $definition);
        return $ret;
    }

    /**
     * drop existing index
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the index to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropIndex($table, $name) {
        $ret = $this->_db->mgDropIndex($table, $name);
        return $ret;
    }

    /**
     * list all indexes in a table
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableIndexes($table) {
        $ret = $this->_db->mgListTableIndexes($table);
        return $ret;
    }

    /**
     * create a constraint on a table
     *
     * @param  string $table      name of the table on which the constraint is to be created
     * @param  string $name       name of the constraint to be created
     * @param  array  $definition associative array that defines properties of the constraint to be created.
     *                            Currently, only one property named FIELDS is supported. This property
     *                            is also an associative with the names of the constraint fields as array
     *                            constraints. Each entry of this array is set to another type of associative
     *                            array that specifies properties of the constraint that are specific to
     *                            each field.
     *
     *                            Example
     *                            array(
     *                            'fields' => array(
     *                            'user_name' => array(),
     *                            'last_login' => array()
     *                            )
     *                            )
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createConstraint($table, $name, $definition) {
        $ret = $this->_db->mgCreateConstraint($table, $name, $definition);
        return $ret;
    }

    /**
     * drop existing constraint
     *
     * @param  string $table name of table that should be used in method
     * @param  string $name  name of the constraint to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropConstraint($table, $name) {
        $ret = $this->_db->mgDropConstraint($table, $name);
        return $ret;
    }

    /**
     * list all constraints in a table
     *
     * @param  string $table name of table that should be used in method
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listTableConstraints($table) {
        $ret = $this->_db->mgListTableConstraints($table);
        return $ret;
    }

    /**
     * create sequence
     *
     * @param  string $seq_name name of the sequence to be created
     * @param  string $start    start value of the sequence; default is 1
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createSequence($seq_name, $start = 1) {
        $ret = $this->_db->mgCreateSequence($seq_name, $start);
        return $ret;
    }

    /**
     * drop existing sequence
     *
     * @param  string $seq_name name of the sequence to be dropped
     * @return mixed  MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropSequence($name) {
        $ret = $this->_db->mgDropSequence($name);
        return $ret;
    }

    /**
     * list all sequences in the current database
     *
     * @return mixed  data array on success, a MDB2 error on failure
     * @access public
     */
    public function listSequences() {
        $ret = $this->_db->mgListSequences();
        return $ret;
    }

    /**
     * Log out and disconnect from the database.
     *
     * @return mixed  TRUE on success, FALSE if not connected and error
     *                object on error
     * @access public
     */
    public function disconnectDb() {
        return $this->_db->disconnect();
    }

    /**
     * Select a different database
     *
     * @param  string $name name of the database that should be selected
     * @return string name of the database previously connected to
     * @access public
     */
    public function setDatabase($name) {
        return $this->_db->setDatabase($name);
    }

    /**
     * get the current database
     *
     * @return string name of the database
     * @access public
     */
    public function getDatabase() {
        return $this->_db->getDatabase();
    }

    /**
     * return version information about the server
     *
     * @param  string $native determines if the raw version string should be returned
     * @return mixed  array with versoin information or row string
     * @access public
     */
    public function getServerVersion($native = FALSE) {
        return $this->_db->getServerVersion($native);
    }

    /**
     * this function helps in checking if a key exists before adding it to the
     * table
     * @param <type> $change
     * @param <type> $fields
     * @param <type> $changes
     * @return <type>
     */
    private function returnIfExistingKey($change, $fields, $changes) {

        foreach ($change as $c => $b) {
            foreach ($fields as $field) {

                if ($field == $c) {
                    return TRUE;
                }
            }
        }
    }

    /**
     * alter an existing table
     *
     * @param  string  $name    name of the table that is intended to be changed.
     * @param  array   $changes associative array that contains the details of each type
     *                          of change that is intended to be performed. The types of
     *                          changes that are currently supported are defined as follows:
     *
     *                          name
     *
     *                          New name for the table.
     *
     *                          add
     *
     *                          Associative array with the names of fields to be added as
     *                          indexes of the array. The value of each entry of the array
     *                          should be set to another associative array with the properties
     *                          of the fields to be added. The properties of the fields should
     *                          be the same as defined by the Metabase parser.
     *
     *
     *                          remove
     *
     *                          Associative array with the names of fields to be removed as indexes
     *                          of the array. Currently the values assigned to each entry are ignored.
     *                          An empty array should be used for future compatibility.
     *
     *                          rename
     *
     *                          Associative array with the names of fields to be renamed as indexes
     *                          of the array. The value of each entry of the array should be set to
     *                          another associative array with the entry named name with the new
     *                          field name and the entry named Declaration that is expected to contain
     *                          the portion of the field declaration already in DBMS specific SQL code
     *                          as it is used in the CREATE TABLE statement.
     *
     *                          change
     *
     *                          Associative array with the names of the fields to be changed as indexes
     *                          of the array. Keep in mind that if it is intended to change either the
     *                          name of a field and any other properties, the change array entries
     *                          should have the new names of the fields as array indexes.
     *
     *                          The value of each entry of the array should be set to another associative
     *                          array with the properties of the fields to that are meant to be changed as
     *                          array entries. These entries should be assigned to the new values of the
     *                          respective properties. The properties of the fields should be the same
     *                          as defined by the Metabase parser.
     *
     *                          Example
     *                          array(
     *                          'name' => 'userlist',
     *                          'add' => array(
     *                          'quota' => array(
     *                          'type' => 'integer',
     *                          'unsigned' => 1
     *                          )
     *                          ),
     *                          'remove' => array(
     *                          'file_limit' => array(),
     *                          'time_limit' => array()
     *                          ),
     *                          'change' => array(
     *                          'name' => array(
     *                          'length' => '20',
     *                          'definition' => array(
     *                          'type' => 'text',
     *                          'length' => 20,
     *                          ),
     *                          )
     *                          ),
     *                          'rename' => array(
     *                          'sex' => array(
     *                          'name' => 'gender',
     *                          'definition' => array(
     *                          'type' => 'text',
     *                          'length' => 1,
     *                          'default' => 'M',
     *                          ),
     *                          )
     *                          )
     *                          )
     *
     * @param  boolean $check   indicates whether the function should just check if the DBMS driver
     *                          can perform the requested table alterations if the value is TRUE or
     *                          actually perform them otherwise.
     * @access public
     *
     * @return mixed   MDB2_OK on success, a MDB2 error on failure
     */
    function alterTable($name, $changes, $check) {
        /**
         * check if there is already field with same name
         * If true, simply return
         */
    
        $fields = $this->listTableFields($name);
 
        foreach ($changes as $change) {
            if ($this->returnIfExistingKey($change, $fields, $changes)) {
                return TRUE;
            }
        }
        // If the change is an insert - insert the data, otherwise alter the table
        if (isset($changes['insert'])) {
            // insert data through db table
            $fields = $changes['insert'];
            if ($check) {
                $ret = TRUE;
            } else {
                dbtable::init($name, $this->_db);
                $ret = dbtable::insert($fields, $name);
            }
        } else {
            $ret = $this->_db->mgAlterTable($name, $changes, $check);
        }
        if (PEAR::isError($ret)) {
            return $ret->getMessage();
        } else {
            // var_dump($ret);
            return $ret;
        }
    }

}

?>
