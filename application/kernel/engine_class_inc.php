<?php
use Doctrine\ORM\Tools\Setup;

/**
 * Engine object
 *
 * The engine object is the main class of the Chisimba framework. It kicks off all other operations in the
 * framework and controls all of the other classes
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
 * @version   $Id: engine_class_inc.php 19742 2010-11-17 06:09:50Z davidwaf $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */

/* --------------------------- engine class ------------------------*/

// security check - must be included in all scripts
if (! /**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['kewl_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS ['kewl_entry_point_run']) {
    die ( "You cannot view this page directly" );
}
// end security check


/**
 * The Object class
 */
require_once 'classes/core/object_class_inc.php';

/**
 * Access (permissions system) class
 */
require_once 'classes/core/access_class_inc.php';

/**
 * database abstraction object
 */
require_once 'classes/core/dbtable_class_inc.php';

/**
 * database management object
 */
require_once 'classes/core/dbtablemanager_class_inc.php';

/**
 * front end controller object
 */
require_once 'classes/core/controller_class_inc.php';

/**
 * log layer
 */
require_once 'lib/logging.php';

/**
 * error handler
 */
require_once 'classes/core/errorhandler_class_inc.php';

/**
 * the exception handler
 */
require_once 'classes/core/customexception_class_inc.php';

/**
 * the config base class
 */
require_once 'classes/core/altconfig_class_inc.php';

/**
 * the config base class
 */
require_once 'services/core/dispatcher_class_inc.php';

/**
 * include the dbdetails file
 */
include ('config/dbdetails_inc.php');

/**
 * set up all the files needed to effectively run lucene
 */
// include ('lucene.php');

/**
 * config object
 *
 * @deprecated now moved to constructor to avoid userland installation of Config
 */
// require_once ('Config.php');

/**
 * Error callback
 *
 * function to enable the pear error callback method (global)
 *
 * @param  string $error The error messages
 * @return void
 */
function globalPearErrorCallback($error) {
    log_debug ( $error );
}

/**
 * Engine class
 *
 * Engine class to handle and kick off the Chisimba framework. All transactions go through this class at some stage
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
class engine {
    /**
     * Version Number of the software. (engine)
     *
     */
    public $version = '3.2.3';

    /**
     * Template variable
     *
     * @var    string
     * @access public
     */
    public $_templateVars = NULL;

    /**
     * Template reference variable
     *
     * @var    unknown_type
     * @access public
     */
    public $_templateRefs = NULL;

    /**
     * Database abstraction method - can be MDB2 or PDO
     *
     * @var    string
     * @access public
     */
    public $_dbabs;

    /**
     * database object (global)
     *
     * @var    object
     * @access private
     */
    public $_objDb;

    /**
     * database manager object (global)
     *
     * @var    object
     * @access private
     */
    public $_objDbManager;

    /**
     * The User object
     *
     * @access public
     * @var    object
     */
    public $_objUser;

    /**
     * The logged in users object
     *
     * @access public
     * @var    object
     */
    public $_objLoggedInUsers;

    /**
     * The config object (config/* and /modules/config)
     *
     * @access private
     * @var    object
     */
    public $_objConfig;

    /**
     * The language object(s)
     *
     * @access private
     * @var    object
     */
    private $_objLanguage;

    /**
     * The DB config object
     *
     * @access private
     * @var    object
     */
    private $_objDbConfig;

    /**
     * The layout template default
     *
     * @access private
     * @var    string
     */
    public $_layoutTemplate;

    /**
     * The default page template
     *
     * @access private
     * @var    string
     */
    public $_pageTemplate = null;

    /**
     * Has an error been generated?
     *
     * @access private
     * @var    string
     */
    private $_hasError = FALSE;

    /**
     * Where was the error generated?
     *
     * @access private
     * @var    string
     */
    private $_errorField = '';

    /**
     * The page content
     *
     * @access private
     * @var    string
     */
    public $_content = '';

    /**
     * The layout content string
     *
     * @access private
     * @var    string
     */
    public $_layoutContent = '';

    /**
     * The module name currently in use
     *
     * @access private
     * @var    string
     */
    public $_moduleName = NULL;

    /**
     * The currently active controller
     *
     * @access private
     * @var    object
     */
    private $_objActiveController = NULL;

    /**
     * The global error message
     *
     * @access private
     * @var    string
     */
    private $_errorMessage = '';

    /**
     * The messages generated by the classes
     *
     * @access private
     * @var    string
     */
    private $_messages = NULL;

    /**
     * Has the session started?
     *
     * @access private
     * @var    bool
     */
    private $_sessionStarted = FALSE;

    /**
     * Property for cached objects
     *
     * @access private
     * @var    object
     */
    private $_cachedObjects = NULL;

    /**
     * Whether to enable access control
     *
     * @access private
     * @var    object
     */
    private $_enableAccessControl = TRUE;

    /**
     * Configuration Object
     *
     * @var object
     */
    private $_altconfig = null;

    /**
     * DSN - Data Source Name for the database connection object
     *
     * @var string
     */
    protected $dsn = KEWL_DB_DSN;

    /**
     * DSN - Data Source Name for the database connection object
     *
     * @var string
     */
    public $pdsn;

    /**
     * DSN - Data Source Name for the database management object
     *
     * @var string
     */
    protected $mdsn = KEWL_DB_DSN;

    /**
     * Core modules array
     * This is a dynamically generated array of the absolute core modules. They cannot be deleted or removed
     * The core modules will live in a directory called services core in the app root
     *
     * @var array
     */
    public $coremods;

    /**
     * MemcacheD object
     *
     * @var boolean
     */
    public $objMemcache = FALSE;

    /**
     * APC object
     *
     * @var boolean
     */
    public $objAPC = FALSE;

    /**
     * Cache Time to live (TTL)
     *
     * @var integer
     */
    protected $cacheTTL = 3600;
    
    /**
     * LiveUser configuration object
     *
     * @var void
     */
    protected $luConfig;

    /**
     * Event dispatcher object for events based framework
     *
     * @var void
     */
    public $eventDispatcher;

    /**
     * Log temp storage property
     *
     * @var void
     */
    public $enableLogging;

    /**
     * Global servername
     *
     * @var string
     */
    public $_servername;

    /**
     * Global application ID (for this application)
     *
     * @var string
     */
    public $appid;
    
    public $lu;
    public $luAdmin;
    public $serviceData;
    public $dispatcher;
    public $session;
    
    // The current user agent
  	public static $user_agent;
    
    // Protected key names (cannot be set by the user)
    protected $sessprotect = array('session_id', 'user_agent', 'last_activity', 'ip_address', 'total_hits');

    // Configuration and driver
    protected $sessconfig;

    
    /**
     * Constructor.
     * For use by application entry point script (usually /index.php)
     *
     * @param  void
     * @return void
     * @access public
     */
    public function __construct() {
        $this->_objDbConfig = $this->getObject ( 'altconfig', '_core' );
        //and we need a general system config too
        $this->_objConfig = clone $this->_objDbConfig;
        
        // Set the user agent
   	    self::$user_agent = ( ! empty($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '');
        $this->sessprotect = array_combine($this->sessprotect, $this->sessprotect);
        
        /*
		 * we only initiate session handling here if a session already exists;
		 * the session is only created once a successful login has taken place.
		 * this has the small security benefit (albeit an obscurity based one)
		 * of concealing any information about the session id generator from
		 * unauthenticated users. (see Engine->do_login for session creation)
		 */
        //if (isset ( $_REQUEST [session_name ()] )) {
        //    $this->sessionStart ();
        //}
        // Populate the core modules array with the contents of the services directory.
        $this->coremods = array_map('basename', glob('services/core/*', GLOB_ONLYDIR));

        /*
		 * initialise member objects that *this object* is dependent on, and thus
		 * must be created on every request
		 * the config objects
		 * all configs now live in one place, referencing the config.xml file in the config directory
		 */

		 $this->sessconfig = array( 'name' => $this->_objConfig->getValue('sess_name', 'security', 'CHISIMBASESSION'),
        'gc_probability' => 2,
        'expiration' => $this->_objConfig->getValue('auth_cont_expiretime', 'security', 7200 ),
        'regenerate' => 3,
        // 'validate' => array(self::$user_agent),
        'cookiepath' =>   $this->_objConfig->getValue ( 'auth_cookiepath', 'security', NULL ),
        'cookiedomain' =>   $this->_objConfig->getValue ( 'auth_cookiedomain', 'security', NULL ),
        'cookiesecure' =>   $this->_objConfig->getValue ( 'auth_cookiesecure', 'security', true ));
        
        // do we enable logging?
        $this->enableLogging = $this->_objDbConfig->getenable_logging ();
        // check for which db abstraction to use - MDB2 or PDO
        $this->_dbabs = $this->_objDbConfig->getenable_dbabs ();
        // Ensure the site is being accessed at the correct location.
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $base = 'http://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?');
            $query = strtok('?');

            if ($query === FALSE) {
                if ($base != $this->_objDbConfig->getsiteRoot()) {
                    header('Location: '.$this->_objDbConfig->getsiteRoot());
                }
            } elseif ($base != $this->_objDbConfig->getsiteRoot().'index.php') {
                header('Location: '.$this->_objDbConfig->getsiteRoot().'index.php?'.$query, TRUE, 301);
            }
        }

        // check for memcache
        if (extension_loaded ( 'memcache' )) {
            require_once 'classes/core/chisimbacache_class_inc.php';
            if ($this->_objDbConfig->getenable_memcache () == 'TRUE') {
                $this->objMemcache = TRUE;
            } else {
                $this->objMemcache = FALSE;
            }
            $this->cacheTTL = $this->_objDbConfig->getcache_ttl ();
        }

        // check for APC
        if (extension_loaded ( 'apc' )) {
            if ($this->_objDbConfig->getenable_apc () == 'TRUE') {
                $this->objAPC = TRUE;

            } else {
                $this->objAPC = FALSE;
            }
            $this->cacheTTL = $this->_objDbConfig->getcache_ttl ();
        }

        
        ini_set ( 'include_path', ini_get ( 'include_path' ) . PATH_SEPARATOR . $this->_objConfig->getsiteRootPath () . 'lib/pear/' );
        // Configure garbage collection
        ini_set('session.gc_probability', (int) $this->sessconfig['gc_probability']);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', ($this->sessconfig['expiration'] == 0) ? 86400 : $this->sessconfig['expiration']);
        
        // authentication systems have a naming standard: pem_*_class_inc.php
        // for now, we just use liveuser, so instantiate pem_liveuser_class_inc.php
        $lu = $this->getObject('pem_liveuser', 'security');
        // need a global engine copy of the lu framework
        $this->luAdmin = $lu->luAdmin;
        $this->lu = $lu->lu;
         
        //initialise the event messages framework
        $this->eventDispatcher =& Event_Dispatcher::getInstance();
        
        //initialise the db factory method of MDB2
         $this->getDbObj ();
        //initialise the db factory method of MDB2_Schema
         $this->getDbManagementObj ();

        //the user security module
        $this->_objUser = $this->getObject ( 'user', 'security' );
        //the language elements module
        $this->_objLanguage = $this->getObject ( 'language', 'language' );

        // other fields
        //set the messages array
        $this->_messages = array ();
        //array for the template vars
        $this->_templateVars = array ();
        //the template references
        $this->_templateRefs = array ();
        //bust up the cached objects
        $this->_cachedObjects = array ();

        //Load the Skin Object
        $this->_objSkin = $this->getObject ( 'skin', 'skin' );
        

        //Get default page template
        $this->_pageTemplate = $this->_objSkin->getPageTemplate ();
        // Get Layout Template from Config files
        $this->_layoutTemplate = $this->_objSkin->getLayoutTemplate ();
        
        //$this->dispatcher = new dispatcher($this);
    }

    /**
     * This method is for use by the application entry point. It dispatches the
     * request to the appropriate module controller, and then renders the returned template
     * inside of the appropriate layout template.
     *
     * @param  string $presetModuleName default NULL
     * @param  string $presetAction     default NULL
     * @access public
     * @return void
     */
    public function run($presetModuleName = NULL, $presetAction = NULL) {
        if (empty ( $presetModuleName )) {
            $requestedModule = strtolower ( $this->getParam ( 'module', '_default' ) );
        } else {
            $requestedModule = $presetModuleName;
        }
        if (empty ( $presetAction )) {
            $requestedAction = strtolower ( $this->getParam ( 'action', '' ) );
        } else {
            $requestedAction = $presetAction;
        }
        // Pass the JSON encoded template and module name to the services layer for handling
        //var_dump($requestedModule);
        $this->serviceData = json_encode(array($requestedAction, $requestedModule));
        
        $this->_finish ();
        //return $this->serviceData;
    }

    /**
     * Method to return the db object. Evaluates lazily,
     * so class file is not included nor object instantiated
     * until needed.
     *
     * @param  void
     * @access public
     * @return kngConfig The config object
     */
    public function getDbObj() {

        global $_globalObjDb;
        /*
		* do the checks that the db object gets instantiated once, then let MDB2 take over for the on-demand * *construction
		*/
        if ($this->_objDb == NULL || $_globalObjDb == NULL) {
            $this->_objDbConfig = $this->getObject ( 'altconfig', '_core' );
            /*
			 * set up the DSN. Some RDBM's do not operate with the string style DSN (most noticeably Oracle)
			 * so we parse the DSN to an array and then send that to the object instantiation to be safe
			 */
            $dsn = KEWL_DB_DSN;
            $this->dsn = $this->parseDSN ( $dsn );
            $this->pdsn = $this->dsn;

            // now check whether to use PDO or MDB2
            if ($this->_dbabs === 'MDB2') {
                // Connect to the database
                require_once ('MDB2.php');
                $_globalObjDb = &MDB2::singleton ( $this->dsn );

                //Check for errors on the factory method
                if (PEAR::isError ( $_globalObjDb )) {
                    $this->_pearErrorCallback ( $_globalObjDb );
                    //return the db object for use globally
                    return $_globalObjDb;
                }
                // a much nicer mode than the default MDB2_FETCHMODE_ORDERED
                $_globalObjDb->setFetchMode ( MDB2_FETCHMODE_ASSOC );
                //set the options for portability!
                $_globalObjDb->setOption ( 'portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ALL );
                $_globalObjDb->setOption ('quote_identifier', true);
                $_globalObjDb->setCharset('utf8');

                //Check for errors
                if (PEAR::isError ( $_globalObjDb )) {
                    /*
					* manually call the callback function here, as we haven't had a chance to install it as
					* the error handler
					*/
                    $this->_pearErrorCallback ( $_globalObjDb );
                    //return the db object for use globally
                    return $_globalObjDb;
                }
                // Load the MDB2 Functions module
                $_globalObjDb->loadModule ( 'Function' );
                // keep a copy as a field as well
                $this->_objDb = $_globalObjDb;

                //Load up some of the extra MDB2 modules:
                MDB2::loadFile ( 'Date' );
                MDB2::loadFile ( 'Iterator' );

                // install the error handler with our custom callback on error
                $this->_objDb->setErrorHandling ( PEAR_ERROR_CALLBACK, array ($this, '_pearErrorCallback' ) );
                /* set the default fetch mode for the DB to assoc, as that's a much nicer mode than the default  * MDB2_FETCHMODE_ORDERED
				 */
                $this->_objDb->setFetchMode ( MDB2_FETCHMODE_ASSOC );
                if ($this->_objDb->phptype == 'oci8') {
                    $this->_objDb->setOption ( 'field_case', CASE_LOWER );
                    //oracle numRows() hack plus some extras
                    $this->_objDb->setOption ( 'portability', MDB2_PORTABILITY_NUMROWS | MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_RTRIM | MDB2_PORTABILITY_ALL );
                    $this->_objDb->setCharset('utf8');
                } else {
                    $this->_objDb->setOption ( 'portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ALL );
                    $this->_objDb->setCharset('utf8');
                }
                // include the dbtable base class for future use
            } elseif ($this->_dbabs === 'PDO') {
                // PDO stuff
                if (! extension_loaded ( 'PDO' )) {
                    die ( "You must install the PDO extension before trying to use it!" );
                }
                // dsn is in the form of 'mysql:host=localhost;dbname=test', $user, $pass
                if ($this->_objDb === NULL) {
                    try {
                        $this->_objDb = new PDO ( $this->dsn ['phptype'] . ":" . "host=" . $this->dsn ['hostspec'] . ";dbname=" . $this->dsn ['database'], $this->dsn ['username'], $this->dsn ['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") );
                        $this->_objDb->setAttribute ( PDO::ATTR_EMULATE_PREPARES, true );
                        $this->_objDb->setAttribute ( PDO::ATTR_CASE, PDO::CASE_LOWER );
                        $this->_objDb->setAttribute ( PDO::ATTR_PERSISTENT, true );
                        

                        if ($this->dsn ['phptype'] == 'pgsql') {
                            $this->_objDb->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                        }
                    } catch ( PDOException $e ) {
                        echo $e->getMessage ();
                        exit ();
                    }
                }
            }
            //return the local copy
            return $this->_objDb;
            
        }

    }

    /**
     * Method to return the db management object. Evaluates lazily,
     * so class file is not included nor object instantiated
     * until needed.
     *
     * @param  void
     * @access public
     * @return kngConfig The config object
     */
    public function getDbManagementObj() {
        //global for the management object
        global $_globalObjDbManager;
        /*
		*do the checks that the db object gets instantiated once, then
		*let MDB2 take over for the on-demand construction
		*/
        if ($this->_objDbManager == NULL || $_globalObjDbManager == NULL) {
            //load the config object (same as the db Object)
            $this->_objDbConfig = $this->getObject ( 'altconfig', '_core' );
            $mdsn = KEWL_DB_DSN; //$this->_objDbConfig->getDsn();
            $this->mdsn = $this->parseDSN ( $mdsn );
            // Connect to the database
            //require_once $this->getPearResource ( 'MDB2/Schema.php' );
            //MDB2 has a factory method, so lets use it now...
            $_globalObjDbManager = &MDB2::connect ( $this->dsn );

            //Check for errors
            if (PEAR::isError ( $_globalObjDbManager )) {
                /*
				 * manually call the callback function here,
				 * as we haven't had a chance to install it as
				 * the error handler
				 */
                $this->_pearErrorCallback ( $_globalObjDbManager );
                //return the db object for use globally
                return $_globalObjDbManager;
            }
            // keep a copy as a field as well
            $this->_objDbManager = $_globalObjDbManager;
            // install the error handler with our custom callback on error
            $this->_objDbManager->setErrorHandling ( PEAR_ERROR_CALLBACK, array ($this, '_pearErrorCallback' ) );

        }
        //return the local copy
        return $this->_objDbManager;
    }

    /**
     * Method to parse the DSN from a string style DSN to an array for portability reasons
     *
     * @access public
     * @param  string $dsn
     * @return void
     */
    public function parseDSN($dsn) {
        $parsed = NULL;
        $arr = NULL;
        if (is_array ( $dsn )) {
            $dsn = array_merge ( $parsed, $dsn );
            return $dsn;
        }
        //find the protocol
        if (($pos = strpos ( $dsn, '://' )) !== false) {
            $str = substr ( $dsn, 0, $pos );
            $dsn = substr ( $dsn, $pos + 3 );
        } else {
            $str = $dsn;
            $dsn = null;
        }
        if (preg_match ( '|^(.+?)\((.*?)\)$|', $str, $arr )) {
            $parsed ['phptype'] = $arr [1];
            $parsed ['phptype'] = ! $arr [2] ? $arr [1] : $arr [2];
        } else {
            $parsed ['phptype'] = $str;
            $parsed ['phptype'] = $str;
        }

        if (! count ( $dsn )) {
            return $parsed;
        }
        // Get (if found): username and password
        if (($at = strrpos ( $dsn, '@' )) !== false) {
            $str = substr ( $dsn, 0, $at );
            $dsn = substr ( $dsn, $at + 1 );
            if (($pos = strpos ( $str, ':' )) !== false) {
                $parsed ['username'] = rawurldecode ( substr ( $str, 0, $pos ) );
                $parsed ['password'] = rawurldecode ( substr ( $str, $pos + 1 ) );
            } else {
                $parsed ['username'] = rawurldecode ( $str );
            }
        }
        //server
        if (($col = strrpos ( $dsn, ':' )) !== false) {
            $strcol = substr ( $dsn, 0, $col );
            $dsn = substr ( $dsn, $col + 1 );
            if (($pos = strpos ( $strcol, '+' )) !== false) {
                $parsed ['hostspec'] = rawurldecode ( substr ( $strcol, 0, $pos ) );
            } else {
                $parsed ['hostspec'] = rawurldecode ( $strcol );
            }
        }
        /*
		 * now we are left with the port and databsource so we can just explode the string
		 * and clobber the arrays together
		 */
        $pm = explode ( "/", $dsn );
        $parsed ['hostspec'] = $pm [0];
        $parsed ['database'] = $pm [1];
        $dsn = NULL;

        $parsed ['hostspec'] = str_replace ( "+", "/", $parsed ['hostspec'] );

        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem ()->get ( 'dsn' )) {
                $parsed = chisimbacache::getMem ()->get ( 'dsn' );
                $parsed = unserialize ( $parsed );
                return $parsed;
            } else {
                chisimbacache::getMem ()->set ( 'dsn', serialize ( $parsed ), FALSE, $this->cacheTTL );
                return $parsed;
            }
        }
        return $parsed;
    }

    
    public function getPatchObject($name, $moduleName = '') {
        $engine = $this;
        $objname = $name . "_installscripts";
        if(!in_array($name, $this->coremods)) {
            $filename = $this->_objConfig->getModulePath () . $name . "/patches/installscripts_class_inc.php";
        }
        else {
            $filename = $this->_objConfig->getSiteRootPath().'services/core/'.$name.'/patches/installscripts_class_inc.php';
        }
        if (file_exists ( $filename )) {
            require_once ($filename);
            if (is_subclass_of ( $objname, 'object' )) {
                // Class inherits from class 'object', so pass it the expected parameters
                $objNew = new $objname ( $this, $objname );
            } else {
                // Class does not inherit from class 'object', so don't pass it any parameters
                $objNew = new $objname ( );
            }
            if (is_null ( $objNew )) {
                throw new customException ( "Could not instantiate patch class $name from module $moduleName " . __FILE__ . __CLASS__ . __FUNCTION__ . __METHOD__ );
            }
            return $objNew;
        } else {
            return NULL;
        }
    }

    /**
     * Method to load a class definition from the given module.
     * Used when you wish to instantiate objects of the class yourself.
     *
     * @access public
     * @param  $name       string The name of the class to load
     * @param  $moduleName string The name of the module to load the class from (optional)
     * @return a           reference to the loaded object in engine ($this)
     */
    public function loadClass($name, $moduleName = '') {
        if ($name == 'config' && $moduleName == '_core' && $this->_objConfig) {
            // special case: skip if config and objConfig exists, this means config
            // class is already loaded using relative path, and an attempt to load with absolute
            // path will fail because the require_once feature matches filenames exactly.
            return;
        }
        if ($name == 'altconfig' && $moduleName == '_core' && $this->_objConfig) {
            // special case: skip if config and objConfig exists, this means config
            // class is already loaded using relative path, and an attempt to load with absolute
            // path will fail because the require_once feature matches filenames exactly.
            return;
        }
        if ($name == 'altconfig' && $moduleName == '_core' && ! $this->_objConfig) {
            $filename = "classes/core/" . strtolower ( $name ) . "_class_inc.php";
            $engine = $this;
            if (! ($this->_objConfig instanceof altconfig)) {
                require_once ($filename);
                $this->_objConfig = new altconfig ( );
                if ($this->objMemcache == TRUE) {
                    if (chisimbacache::getMem ()->get ( 'altconfig' )) {
                        $this->_objConfig = chisimbacache::getMem ()->get ( 'altconfig' );
                        return $this->_objConfig;
                    } else {
                        require_once ($filename);
                        $this->_objConfig = new altconfig ( );
                        chisimbacache::getMem ()->set ( 'altconfig', $this->_objConfig, MEMCACHE_COMPRESSED, $this->cacheTTL );
                        return $this->_objConfig;
                    }
                } elseif ($this->objAPC == TRUE) {
                    $this->_objConfig = apc_fetch ( 'altconfig' );
                    if ($this->_objConfig == FALSE) {
                        $this->_objConfig = new altconfig ( );
                        apc_store ( 'altconfig', $this->_objConfig, $this->cacheTTL );
                    }
                } else {
                    require_once ($filename);
                    $this->_objConfig = new altconfig ( );
                    return $this->_objConfig;
                }
            } else {
                return;
            }
        }
        //if(!$this->_objConfig) {
        //    $this->_objConfig = $this->getObject ( 'altconfig', '_core' );
        //}
        if(empty($this->coremods)) {
            $this->coremods = array_map('basename', glob('services/core/*', GLOB_ONLYDIR));
        }
        
        if (in_array ( $moduleName, $this->coremods )) {
            $filename = $this->_objConfig->getSiteRootPath () . "services/core/" . $moduleName . "/classes/" . strtolower ( $name ) . "_class_inc.php";
        } elseif ($moduleName == '_core') {
            $filename = "classes/core/" . strtolower ( $name ) . "_class_inc.php";
        } else {
            $filename = $this->_objConfig->getModulePath () . $moduleName . "/classes/" . strtolower ( $name ) . "_class_inc.php";
        }
        // add the site root path to make an absolute path if the config object has been loaded
        if (! file_exists ( $filename )) {
            if ($this->_objConfig->geterror_reporting () == "developer") {
                if (extension_loaded ( "xdebug" )) {
                    throw new customException ( "Could not load class $name from module $moduleName: filename $filename " );
                } else {
                    throw new customException ( "Could not load class $name from module $moduleName: filename $filename " );
                }

                die ();
            }
            throw new customException ( "Could not load class $name from module $moduleName: filename $filename " );
        }
        $engine = $this;
        $this->__autoload ( $filename );
    }

    public function __autoload($class_name) {
        require_once $class_name;
    }

    /**
     * Method to get a new instance of a class from the given module.
     * Note that this relies on the naming convention for class files
     * being adhered to, e.g. class moduleAdmin should live in file:
     * 'moduleadmin_class_inc.php'.
     * This engine object is offered to the constructor as a parameter
     * when creating a new object although it need not be used.
     *
     * @access public
     * @see    loadclass
     * @param  $name       string The name of the class to load
     * @param  $moduleName string The name of the module to load the class from
     * @return mixed       The object asked for
     */
    public function newObject($name, $moduleName) {
        $this->loadClass ( $name, $moduleName );
        if ($this->objMemcache == TRUE) {
            if (chisimbacache::getMem ()->get ( md5 ( $name ) )) {
                //log_debug("retrieve $name from cache...new object");
                $objNew = chisimbacache::getMem ()->get ( md5 ( $name ) );

                return $objNew;
            } else {
                if (is_subclass_of ( $name, 'object' )) {
                    $objNew = new $name ( $this, $moduleName );
                    return $objNew;
                } else {
                    $objNew = new $name ( );
                    //log_debug("setting newObject $name from cache...");
                    chisimbacache::getMem ()->set ( md5 ( $name ), $objNew, MEMCACHE_COMPRESSED, $this->cacheTTL );
                }
            }
        } elseif ($this->objAPC == TRUE) {
            $objNew = apc_fetch ( $name );
            if ($objNew == FALSE) {
                if (is_subclass_of ( $name, 'object' )) {
                    $objNew = new $name ( $this, $moduleName );
                    return $objNew;
                } else {
                    $objNew = new $name ( );
                    apc_store ( $name, $objNew, $this->cacheTTL );
                }
            }
        } else {
            // Fix to allow developers to load htmlelements which do not inherit from class 'object'
            if (is_subclass_of ( $name, 'object' )) {
                // Class inherits from class 'object', so pass it the expected parameters
                $objNew = new $name ( $this, $moduleName );

            } else {
                // Class does not inherit from class 'object', so don't pass it any parameters
                $objNew = new $name ( );
            }
            if (is_null ( $objNew )) {
                throw new customException ( "Could not instantiate class $name from module $moduleName " . __FILE__ . __CLASS__ . __FUNCTION__ . __METHOD__ );
            }
        }
        return $objNew;
    }

    /**
     * Method to get an instance of a class from the given module.
     * If this is the first call for that class a new instance will be created,
     * otherwise the existing instance will be returned.
     * Note that this relies on the naming convention for class files
     * being adhered to, e.g. class moduleAdmin should live in file:
     * 'moduleadmin_class_inc.php'.
     * This engine object is offered to the constructor as a parameter
     * when creating a new object although it need not be used.
     *
     * @access public
     * @see    loadclass
     * @param  $name       string The name of the class to load
     * @param  $moduleName string The name of the module to load the class from
     * @return mixed       The object asked for
     */
    public function getObject($name, $moduleName) {
        $instance = NULL; 
        if($moduleName == 'config') {
            $moduleName = '_core';
        }
        if (isset ( $this->_cachedObjects [$moduleName] [$name] )) {
            $instance = $this->_cachedObjects [$moduleName] [$name];
        } else {
            $this->loadClass ( $name, $moduleName );
            if (is_subclass_of ( $name, 'object' )) {
                $instance = new $name ( $this, $moduleName );
            } else {
                $instance = new $name ( );
            }
            if (is_null ( $instance )) {
                throw new customException ( "Could not instantiate class $name from module $moduleName " . __FILE__ . __CLASS__ . __FUNCTION__ . __METHOD__ );
            }
            // first check that the map for the given module exists
            if (! isset ( $this->_cachedObjects [$moduleName] )) {
                $this->_cachedObjects [$moduleName] = array ();
            }
            // now store the instance in the map
            $this->_cachedObjects [$moduleName] [$name] = $instance;
        }
        return $instance;
    }
    
    /**
     * Method to return current page content. For use within layout templates.
     *
     * @access public
     * @param  void
     * @return string Content of rendered content script
     */
    public function getContent() {
    // var_dump($this->dispatcher->_content);
        return $this->_content;
    }
    
    /**
     * Method to set the name of the layout template to use.
     *
     * @access public
     * @param  string $templateName The name of the layout template to use
     * @return string Name of the layout template
     */
    public function setLayoutTemplate($templateName) {
        $this->_layoutTemplate = $templateName;
    }

    /**
     * Method to return the content of the rendered layout template.
     *
     * @access public
     * @param  void
     * @return string Content of rendered layout script
     */
    public function getLayoutContent() {
        return $this->_layoutContent;
    }

    /**
     * Method to return the currently selected layout template name.
     *
     * @access public
     * @param  void
     * @return string Name of layout template
     */
    public function getPageTemplate() {
        return $this->_pageTemplate;
    }

    /**
     * Method to set the name of the page template to use.
     *
     * @access public
     * @param  string $templateName The name of the page template to use
     * @return string $templateName The name of the page template to use
     */
    public function setPageTemplate($templateName) {
        $this->_pageTemplate = $templateName;
    }

    /**
     * Method to return a template variable. These are used to pass
     * information from module to template.
     *
     * @access public
     * @param  $name    string The name of the variable
     * @param  $default mixed  The value to return if the variable is unset (optional)
     * @return mixed    The value of the variable, or $default if unset
     */
    public function getVar($name, $default = NULL) {
        return isset ( $this->_templateVars [$name] ) ? $this->_templateVars [$name] : $default;
    }

    /**
     * Method to set a template variable. These are used to pass
     * information from module to template.
     *
     * @access public
     * @param  $name  string The name of the variable
     * @param  $val   mixed  The value to set the variable to
     * @return string as associative array of template name
     */
    public function setVar($name, $val) {
        $this->_templateVars [$name] = $val;
    }

    /**
     * Method to return a template reference variable. These are used to pass
     * objects from module to template.
     *
     * @access public
     * @param  $name  string The name of the reference variable
     * @return mixed  The value of the reference variable, or NULL if unset
     */
    public function getVarByRef($name) {
        return isset ( $this->_templateRefs [$name] ) ? $this->_templateRefs [$name] : NULL;
    }

    /**
     * Method to set a template refernce variable. These are used to pass
     * objects from module to template.
     *
     * @access public
     * @param  $name  string The name of the reference variable
     * @param  $ref   mixed  A reference to the object to set the reference variable to
     */
    public function setVarByRef($name, &$ref) {
        if (is_object($ref)) {
            $this->_templateRefs [$name] = $ref;
        } else {
            $this->_templateRefs [$name] =& $ref;
        }
    }

    /**
     * Method to append a value to a template variable holding an array. If the
     * array does not exist, it is created
     *
     * @access public
     * @param  string $name  The name of the variable holding an array
     * @param  mixed  $value The value to append to the array
     * @return string as associative array
     */
    public function appendArrayVar($name, $value) {
        if (! isset ( $this->_templateVars [$name] )) {
            $this->_templateVars [$name] = array ();
        }
        if (! is_array ( $this->_templateVars [$name] )) {
            throw new customException ( "Attempt to append to a non-array template variable $name" );
        }
        if (! in_array ( $value, $this->_templateVars [$name] )) {
            $this->_templateVars [$name] [] = $value;
        }
    }

    /**
     * Method to return a request parameter (i.e. a URL query parameter,
     * a form field value or a cookie value).
     *
     * @access public
     * @param  $name    string The name of the parameter
     * @param  $default mixed  The value to return if the parameter is unset (optional)
     * @return mixed    The value of the parameter, or $default if unset
     */
    public function getParam($name, $default = NULL) {
        $result = isset ( $_REQUEST [$name] ) ? is_string ( $_REQUEST [$name] ) ? trim ( $_REQUEST [$name] ) : $_REQUEST [$name] : $default;

        return $this->install_gpc_stripslashes ( $result );
    }

    /**
     * Method to return a request parameter (i.e. a URL query parameter,
     * a form field value or a cookie value).
     *
     * @access public
     * @param  $name    string The name of the parameter
     * @param  $default mixed  The value to return if the parameter is unset (optional)
     * @return mixed    The value of the parameter, or $default if unset
     */
    public function getArrayParam($name, $default = NULL) {
        if ((isset ( $_REQUEST [$name] )) && (is_array ( $_REQUEST [$name] ))) {
            return $_REQUEST [$name];
        } else {
            return $default;
        }
    }

    /**
     * Strips the slashes from a variable if magic quotes is set for GPC
     * Handle normal variables and array
     *
     * @param mixed $var	the var to cleanup
     * @return mixed
     * @access public
     */
    public function install_gpc_stripslashes($var) {
        if (get_magic_quotes_gpc ()) {
            if (is_array ( $var ))
                $this->install_stripslashes_array ( $var, true );
            else
                $var = stripslashes ( $var );
        }
        return $var;
    }

    /**
     * Strips the slashes from an entire associative array
     *
     * @param array		$array			the array to stripslash
     * @param boolean	$strip_keys		whether or not to stripslash the keys as well
     * @return array
     * @access public
     */
    public function install_stripslashes_array($array, $strip_keys = false) {
        if (is_string ( $array ))
            return stripslashes ( $array );
        $keys_to_replace = Array ();
        foreach ( $array as $key => $value ) {
            if (is_string ( $value )) {
                $array [$key] = stripslashes ( $value );
            } elseif (is_array ( $value )) {
                $this->install_stripslashes_array ( $array [$key], $strip_keys );
            }
            if ($strip_keys && $key != ($stripped_key = stripslashes ( $key ))) {
                $keys_to_replace [$key] = $stripped_key;
            }
        }
        // now replace any of the keys that needed strip slashing
        foreach ( $keys_to_replace as $from => $to ) {
            $array [$to] = $array [$from];
            unset ( $array [$from] );
        }
        return $array;
    }

    /**
     * Method to return a session value.
     *
     * @access public
     * @param  $name    string The name of the session value
     * @param  $default mixed  The value to return if the session value is unset (optional)
     * @return mixed    the value of the parameter, or $default if unset
     */
    public function getSession($name, $default = NULL) {
        $val = $default;
        if (isset ( $_SESSION [$name] )) {
            $val = $_SESSION [$name];
        }
        return $val;
    }

    /**
     * Method to set a session value.
     *
     * @access public
     * @param  $name  string The name of the session value
     * @param  $val   mixed  The value to set the session value to
     * @return void
     */
    public function setSession($name, $val) {
        //if (! $this->_sessionStarted) {
        //    $this->sessionStart ();
        //}
        $_SESSION [$name] = $val;
    }

    /**
     * Method to unset a session parameter.
     *
     * @access public
     * @param  $name  string The name of the session parameter
     * @return void
     */
    public function unsetSession($name) {
        unset ( $_SESSION [$name] );
    }

    /**
     * Method to set the global error message, and an error field if appropriate
     *
     * @access public
     * @param  $errormsg string The error message
     * @param  $field    string The name of the field the error applies to (optional)
     * @return FALSE
     */
    public function setErrorMessage($errormsg, $field = NULL) {
        if (! $this->_hasError) {
            $this->_errorMessage = $errormsg;
            $this->_hasError = TRUE;
        }
        if ($field) {
            $this->_errorField = $field;
        }
        // error return code if needed by caller
        return FALSE;
    }

    /**
     * Method to add a global system message.
     *
     * @access public
     * @param  $msg   string The message
     * @return string the message
     */
    public function addMessage($msg) {
        $this->_messages [] = $msg;
    }

    /**
     * Method to call a further action within a module
     *
     * @access public
     * @param  string $action Action to perform next
     * @param  array  $params Parameters to pass to action
     * @return string template
     */
    public function nextAction($action, $params = array()) {
        list ( $template, $_ ) = $this->_dispatch ( $action, $this->_moduleName );
        return $template;
    }

    /**
     * Method to return an application URI. All URIs pointing at the application
     * must be generated by this method. It is recommended that an action parameter
     * is used to indicate the action being performed.
     * The $mode parameter allows the use of a push/pop mechanism for storing
     * user context for return later. **This needs more work, both implementation
     * and documentation **
     *
     * @access  public
     * @param   array  $params         Associative array of parameter values
     * @param   string $module         Name of module to point to (blank for core actions)
     * @param   string $mode           The URI mode to use, must be one of 'push', 'pop', or 'preserve'
     * @param   string $omitServerName flag to produce relative URLs
     * @param   bool   $javascriptCompatibility flag to produce javascript compatible URLs
     * @returns string $uri the URL
     */
    public function uri($params = array(), $module = '', $mode = '', $omitServerName = FALSE, $javascriptCompatibility = FALSE, $Strict = FALSE, $https = FALSE) {
        if (! empty ( $action )) {
            $params ['action'] = $action;
        }
        if ($omitServerName) {
            $uri = $_SERVER ['PHP_SELF'];
        } elseif($https == FALSE) {
            $uri = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['PHP_SELF'];
        }
        else {
            $uri = "https://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['PHP_SELF'];
        }
        if ($mode == 'push' && $this->getParam ( '_pushed_action' )) {
            $mode = 'preserve';
        }
        if ($mode == 'pop') {
            $params ['module'] = $this->getParam ( '_pushed_module', '' );
            $params ['action'] = $this->getParam ( '_pushed_action', '' );
        }
        if (in_array ( $mode, array ('push', 'pop', 'preserve' ) )) {
            $excluded = array ('action', 'module' );
            if ($mode == 'pop') {
                $excluded [] = '_pushed_action';
                $excluded [] = '_pushed_module';
            }
            foreach ( $_GET as $key => $value ) {
                //echo "using GET";
                if (! isset ( $params [$key] ) && ! in_array ( $key, $excluded )) {
                    $params [$key] = $value;
                }
            }
            if ($mode == 'push') {
                $params ['_pushed_module'] = $this->_moduleName;
                $params ['_pushed_action'] = $this->_action;
            }
        } elseif ($mode != '') {
            throw new customException ( "Incorrect URI mode in Engine::uri" );
        }
        if (count ( $params ) > 1) {
            $params = array_reverse ( $params, TRUE );
        }
        $params ['module'] = $module;
        $params = array_reverse ( $params, TRUE );
        if (! empty ( $params )) {
            $output = array ();

            foreach ( $params as $key => $item ) {
                if (! is_null ( $item )) {
                    $output [] = urlencode ( $key ) . "=" . urlencode ( $item );
                }
            }
            $uri .= '?' . implode ( $javascriptCompatibility ? ($Strict ? '&' : '&#38;') : '&amp;', $output );
        }
        return $uri;
    }

    /**
     * Method to generate a URI to a static resource stored in a module.
     * The resource should be stored within the 'resources' subdirectory of
     * the module directory.
     *
     * @access public
     * @param  string $resourceFile The path to the file within the resources
     *                              subdirectory of the module
     * @param  string $moduleName   The name of the module the resource belongs to
     * @return string URI to a resource in the module
     */
    public function getResourceUri($resourceFile, $moduleName) {
        if (in_array ( $moduleName, $this->coremods )) {
            return "services/core/" . $moduleName . "/resources/" . $resourceFile;
        }
        $moduleURI = $this->_objConfig->getModuleURI () . "/$moduleName/resources/$resourceFile";
        // Convert back slashes to forward slashes.
        $moduleURI = preg_replace ( '/\\\\/', '/', $moduleURI );
        // Replace multiple instances of forward slashes with single ones.
        $moduleURI = preg_replace ( '/\/+/', '/', $moduleURI );
        return $moduleURI;
    }

    /**
     * Method to generate a path to a static resource stored in a module.
     * The resource should be stored within the 'resources' subdirectory of
     * the module directory.
     *
     * @access public
     * @param  string $resourceFile The path to the file within the resources
     *                              subdirectory of the module
     * @param  string $moduleName   The name of the module the resource belongs to
     * @return string Path to the Resource in a module
     */
    public function getResourcePath($resourceFile, $moduleName) {
        if (in_array ( $moduleName, $this->coremods )) {
            return $this->_objConfig->getsiteRootPath () . "services/core/" . $moduleName . "/resources/" . $resourceFile;
        }
        return $this->_objConfig->getModulePath () . $moduleName . "/resources/" . $resourceFile;
    }

    /**
     * Method to generate a URI to a static resource stored in a module.
     * The resource should be stored within the 'resources' subdirectory of
     * the module directory.
     *
     * @access public
     * @param  string $resourceFile The path to the file within the resources
     *                              subdirectory of the module
     * @param  string $moduleName   The name of the module the resource belongs to
     * @return string URI to a resource in the module
     */
    public function getServicesUri($resourceFile, $moduleName) {
        if (in_array ( $moduleName, $this->coremods )) {
            return "services/core/" . $moduleName . "/resources/" . $resourceFile;
        }
        $moduleURI = $this->_objConfig->getModuleURI () . "/$moduleName/resources/$resourceFile";
        // Convert back slashes to forward slashes.
        $moduleURI = preg_replace ( '/\\\\/', '/', $moduleURI );
        // Replace multiple instances of forward slashes with single ones.
        $moduleURI = preg_replace ( '/\/+/', '/', $moduleURI );
        return $moduleURI;
    }

    /**
     * Method to generate a path to a static resource stored in a module.
     * The resource should be stored within the 'resources' subdirectory of
     * the module directory.
     *
     * @access public
     * @param  string $resourceFile The path to the file within the resources
     *                              subdirectory of the module
     * @param  string $moduleName   The name of the module the resource belongs to
     * @return string Path to the Resource in a module
     */
    public function getServicesPath($resourceFile, $moduleName) {
        if (in_array ( $moduleName, $this->coremods )) {
            return $this->_objConfig->getsiteRootPath () . "services/core" . $moduleName . "/resources/" . $resourceFile;
        }
        return $this->_objConfig->getModulePath () . $moduleName . "/resources/" . $resourceFile;
    }

    /**
     * Method to generate a path to a static resource stored in a module.
     * The resource should be stored within the 'resources' subdirectory of
     * the module directory.
     *
     * @access public
     * @param  string $resourceFile The path to the file within the resources
     *                              subdirectory of the module
     * @return string Path to the Resource in a module
     */
    public function getPearResource($resourceFile) {
        if (@include_once ($resourceFile)) {
            return $resourceFile;
        } else {
            return $this->_objConfig->getsiteRootPath () . "lib/pear/" . $resourceFile;
        }

    }

    /**
     * Method that generates a URI to a static javascript
     * file that is stored in the resources folder in the subdirectory
     * in the modules directory
     *
     * @access public
     * @param  string $javascriptFile The javascript file name
     * @param  string $moduleName     The name of the module that the script is in
     * @return string Javascript headers
     */
    public function getJavascriptFile($javascriptFile, $moduleName) {
        return '<script type="text/javascript" src="' . $this->getResourceUri ( $javascriptFile, $moduleName ) . '"></script>';
    }

    /**
     * Method to output javascript that will display system error message and/or
     * system messages as set by setErrorMessage and addMessage
     *
     * @access public
     * @param  void
     * @return string
     */
    public function putMessages() {
        $str = '';
        if ($this->_hasError) {
            $str .= '<script type="text/javascript">' . 'alert("' . $this->javascript_escape ( $this->_errorMessage ) . '");' . '</script>';
        }
        if (is_array ( $this->_messages )) {
            foreach ( $this->_messages as $msg ) {
                $str .= '<script language="JavaScript" type="text/javascript">' . 'alert("' . $this->javascript_escape ( $msg ) . '");' . '</script>';
            }
        }
        echo $str;
    }

    /**
     * Method to find the given template, either in the given module's template
     * subdir (if a module is specified) or in the core templates subdir.
     * Type must be 'content' or 'layout'
     *
     * @access public
     * @param  $tpl        string The name of the template to find,
     *                            including file extension but excluding path
     * @param  $moduleName string The name of the module to search (can be empty to search only core)
     * @param  $type       string The type of template to load: 'content' or 'layout' are current options
     * @return string      The full path to the found template
     */
    public function _findTemplate($tpl, $moduleName, $type) {
        $path = '';
        if (! empty ( $moduleName )) {
            if (in_array ( $moduleName, $this->coremods )) {
                $path = "services/core/" . "${moduleName}/templates/${type}/${tpl}";
            } else {
                $path = $this->_objConfig->getModulePath () . "${moduleName}/templates/${type}/${tpl}";
            }
        }
        if (empty ( $path ) || ! file_exists ( $path )) {
            $firstpath = $path;
            $path = $this->_objSkin->getTemplate ( $type );
            if (! file_exists ( $path )) {
                throw new customException ( "Template $tpl not found (looked in $firstpath)!" );
            }
        }
        return $path;
    }

    /**
     * Method to start the session
     *
     * @access public
     * @param  void
     * @return set    property to true
     */
    public function sessionStart() {
        //session_start();
        if($this->_sessionStarted == FALSE) {
            $this->sesscreate();
            $this->_sessionStarted = TRUE;
        }
    }

    /**
     * Method to instantiate the pear error handler callback
     *
     * @access public
     * @param  string $error
     * @return void   (die)
     */
    public function _pearErrorCallback($error) {

        $msg = $error->getMessage () . ': ' . $error->getUserinfo ();
        $errConfig = $this->_objConfig->geterror_reporting ();
        if ($errConfig == "developer") {
            $usermsg = $msg;
            $this->setErrorMessage ( $usermsg );
            echo $this->putMessages ();
            die ();
        } else {
            $usermsg = $error->getMessage ();
        }
        log_debug ( __LINE__ . "  " . $msg );
        $messages = array ($usermsg, $msg );

        return customException::dbDeath ( $messages );
    }

    /**
     * Method that escapes a string suitable for inclusion as a JavaScript
     * string literal. Add's backslashes for
     *
     * @access public
     * @param  $str   string String to escape
     * @return string Escaped string
     */
    public function javascript_escape($str) {
        return addcslashes ( $str, "\0..\37\"\'\177..\377" );
    }

    

    /**
     * Method to clean up at end of page rendering.
     *
     * @access private
     * @param  void
     * @return __destruct object db
     */
    private function _finish() {
        if ($this->_dbabs === 'MDB2') {
            $this->_objDb->disconnect ();
        } elseif ($this->_dbabs === 'PDO') {
            $this->_objDb = NULL;
        }
        
        //echo $this->convert(memory_get_peak_usage());
        //header("Content-Type: application/json");
        $this->dispatcher = new dispatcher($this);
        //var_dump($this->serviceData);
        return $this->serviceData;
        
    }

    public function __destruct() {
        if ($this->_dbabs === 'MDB2') {
         //   $this->_objDb->disconnect ();
        } elseif ($this->_dbabs === 'PDO') {
            $this->_objDb = NULL;
        }
    }
    
    private function convert($size) {
        $unit = array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
    
    /**
     * Session handlers
     */
       /**
  * Create a new session.
  *
  * @param   array  variables to set after creation
  * @return  void
  */
  public function sesscreate($vars = NULL)
  {
    // Start the session!
    session_start();
    //var_dump($_SESSION);
    // Name the session, this will also be the name of the cookie
    session_name($this->sessconfig['name']);
    //var_dump($this->sessconfig);
    //var_dump($this->sessuser_agent());
    // Destroy any current sessions
    //$this->sessdestroy();
    //if ($this->sessconfig['regenerate'] > 0 && ($_SESSION['total_hits'] % $this->sessconfig['regenerate']) === 0)
    //{
      // Regenerate session id and update session cookie
    //  $this->sessregenerate();
    //}
    //else
    //{
      // Always update session cookie to keep the session alive
      //	cookie::set(session::$config['name'], $_SESSION['session_id'], session::$config['expiration']);
    //}

    // Validate the session name
    if ( ! preg_match('~^(?=.*[a-z])[a-z0-9_]++$~iD', $this->sessconfig['name'])) {
      // This needs to come in once modules have cleaned up session code
      
      // echo ('invalid_session_name');
      // echo $this->sessconfig['name'];
    }
    

    // Set the session cookie parameters
    session_set_cookie_params
    (
      $this->sessconfig['expiration'],
      $this->sessconfig['cookiepath'],
      $this->sessconfig['cookiedomain'],
      $this->sessconfig['cookiesecure']
      );

    

    // Put session_id in the session variable
    $_SESSION['session_id'] = session_id();

    // Set defaults
    if ( ! isset($_SESSION['user_agent']))
    {
      $_SESSION['total_hits'] = 0;
      $_SESSION['user_agent'] = $this->sessuser_agent();
    }

    // Increase total hits
    $_SESSION['total_hits'] += 1;

    // Update last activity
    $_SESSION['last_activity'] = time();

    // Set the new data
    $this->sessset($vars);
  }
  
 /**
  * Destroys the current session.
  *
  * @return  void
  */
  public function sessdestroy()
  {
    if (session_id() !== '')
    {
      // Get the session name
      $name = session_name();

      // Destroy the session
      session_destroy();

      // Re-initialize the array
      $_SESSION = array();

      // Delete the session cookie
      //cookie::delete($name);
    }
  }
  
 /**
  * Set a session variable.
  *
  * @param   string|array  key, or array of values
  * @param   mixed         value (if keys is not an array)
  * @return  void
  */
  public function sessset($keys, $val = FALSE)
  {
    if (empty($keys))
      return FALSE;

    if ( ! is_array($keys))
    {
      $keys = array($keys => $val);
    }

    foreach ($keys as $key => $val)
    {
      if (isset($this->sessprotect[$key]))
        continue;

      // Set the key
      $_SESSION[$key] = $val;
    }
  }
  
    /**
  * Get the session id.
  *
  * @return  string
  */
  public function sessid()
  {
    return $_SESSION['session_id'];
  }
  
    /**
  * Regenerates the global session id.
  *
  * @return  void
  */
  public function sessregenerate()
  {

    // Get the session name
    $name = session_name();

    if (isset($_COOKIE[$name]))
    {
      // Change the cookie value to match the new session id to prevent "lag"
      $_COOKIE[$name] = $_SESSION['session_id'];
    }
  }
  
    /**
  * Get a variable. Access to sub-arrays is supported with key.subkey.
  *
  * @param   string  variable key
  * @param   mixed   default value returned if variable does not exist
  * @return  mixed   Variable data if key specified, otherwise array containing all session data.
  */
  public function sessget($key = FALSE, $default = FALSE)
  {
    if (empty($key))
      return $_SESSION;

    $result = isset($_SESSION[$key]) ? $_SESSION[$key] : $this->sesskey_string($_SESSION, $key);

    return ($result === NULL) ? $default : $result;
  }
  
    /**
  * Returns the value of a key, defined by a 'dot-noted' string, from an array.
  *
  * @param   array   array to search
  * @param   string  dot-noted string: foo.bar.baz
  * @return  string  if the key is found
  * @return  void    if the key is not found
  */
  public static function sesskey_string($array, $keys)
  {
    if (empty($array))
      return NULL;

    // Prepare for loop
    $keys = explode('.', $keys);

    do
    {
      // Get the next key
      $key = array_shift($keys);

      if (isset($array[$key]))
      {
        if (is_array($array[$key]) && ! empty($keys))
        {
          // Dig down to prepare the next loop
          $array = $array[$key];
        }
        else
        {
          // Requested key was found
          return $array[$key];
        }
      }
      else
      {
        // Requested key is not set
        break;
      }
    }
    while ( ! empty($keys));

    return NULL;
  }
  
    /**
  * Get a variable, and delete it.
  *
  * @param   string  variable key
  * @param   mixed   default value returned if variable does not exist
  * @return  mixed
  */
  public function sessget_once($key, $default = FALSE)
  {
    $return = $this->sessget($key, $default);
    $this->sessdelete($key);

    return $return;
  }
  
    /**
  * Delete one or more variables.
  *
  * @param   string  variable key(s)
  * @return  void
  */
  public function sessdelete($keys)
  {
    $args = func_get_args();

    foreach ($args as $key)
    {
      if (isset($this->sessprotect[$key]))
        continue;

      // Unset the key
      unset($_SESSION[$key]);
    }
  }
  
    /**
  	 * Retrieves current user agent information:
  	 * keys:  browser, version, platform, mobile, robot, referrer, languages, charsets
  	 * tests: is_browser, is_mobile, is_robot, accept_lang, accept_charset
  	 *
  	 * @param   string   key or test name
  	 * @param   string   used with "accept" tests: user_agent(accept_lang, en)
  	 * @return  array    languages and charsets
  	 * @return  string   all other keys
  	 * @return  boolean  all tests
  	 */
  	public static function sessuser_agent($key = 'agent', $compare = NULL)
  	{
  		static $info;

  		// Return the raw string
  		if ($key === 'agent')
  			return self::$user_agent;

  		if ($info === NULL)
  		{
  			// Parse the user agent and extract basic information
  			$agents = $this->sessconfig('user_agents');

  			foreach ($agents as $type => $data)
  			{
  				foreach ($data as $agent => $name)
  				{
  					if (stripos(self::$user_agent, $agent) !== FALSE)
  					{
  						if ($type === 'browser' AND preg_match('|'.preg_quote($agent).'[^0-9.]*+([0-9.][0-9.a-z]*)|i', self::$user_agent, $match))
  						{
  							// Set the browser version
  							$info['version'] = $match[1];
  						}

  						// Set the agent name
  						$info[$type] = $name;
  						break;
  					}
  				}
  			}
  		}

  		if (empty($info[$key]))
  		{
  			switch ($key)
  			{
  				case 'is_robot':
  				case 'is_browser':
  				case 'is_mobile':
  					// A boolean result
  					$return = ! empty($info[substr($key, 3)]);
  				break;
  				case 'languages':
  					$return = array();
  					if ( ! empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
  					{
  						if (preg_match_all('/[-a-z]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])), $matches))
  						{
  							// Found a result
  							$return = $matches[0];
  						}
  					}
  				break;
  				case 'charsets':
  					$return = array();
  					if ( ! empty($_SERVER['HTTP_ACCEPT_CHARSET']))
  					{
  						if (preg_match_all('/[-a-z0-9]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])), $matches))
  						{
  							// Found a result
  							$return = $matches[0];
  						}
  					}
  				break;
  				case 'referrer':
  					if ( ! empty($_SERVER['HTTP_REFERER']))
  					{
  						// Found a result
  						$return = trim($_SERVER['HTTP_REFERER']);
  					}
  				break;
  			}

  			// Cache the return value
  			isset($return) and $info[$key] = $return;
  		}

  		if ( ! empty($compare))
  		{
  			// The comparison must always be lowercase
  			$compare = strtolower($compare);

  			switch ($key)
  			{
  				case 'accept_lang':
  					// Check if the lange is accepted
  					return in_array($compare, self::sessuser_agent('languages'));
  				break;
  				case 'accept_charset':
  					// Check if the charset is accepted
  					return in_array($compare, self::sessuser_agent('charsets'));
  				break;
  				default:
  					// Invalid comparison
  					return FALSE;
  				break;
  			}
  		}

  		// Return the key, if set
  		return isset($info[$key]) ? $info[$key] : NULL;
  	}
  
}
?>
