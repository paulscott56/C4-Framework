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
 * @version   $Id: engine_class_inc.php 23094 2012-01-03 11:46:48Z joconnor $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */

/* --------------------------- engine class ------------------------*/

// security check - must be included in all scripts
if (! /**
 * Description for $GLOBALS
 * @global entry point $GLOBALS['chisimba_entry_point_run']
 * @name   $kewl_entry_point_run
 */
$GLOBALS ['chisimba_entry_point_run']) {
    die ( "You cannot view this page directly" );
}
// end security check

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
    public $version = '4.0.0';

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
        // Set the user agent
        self::$user_agent = ( ! empty($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '');
        $this->sessprotect = array_combine($this->sessprotect, $this->sessprotect);
    }
    
    public function bootstrap() 
    {
        require_once "Doctrine/ORM/Tools/Setup.php";
        Setup::registerAutoloadPEAR();
        
        $isDevMode = true;
        $config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
        
        // database configuration parameters
        $conn = array(
            'driver' => 'pdo_mysql',
            'path' => __DIR__ . '/db.sqlite',
        );
        
        // obtaining the entity manager
        $this->entityManager = \Doctrine\ORM\EntityManager::create($conn, $config);
        
        $this->sessconfig = array( 
                                'name'           => 'c4', //$this->_objConfig->getValue('sess_name', 'security', 'CHISIMBASESSION'),
                                'gc_probability' => 2,
                                'expiration'     => 7200, //$this->_objConfig->getValue('auth_cont_expiretime', 'security', 7200 ),
                                'regenerate'     => 3,
                                'validate'       => array(self::$user_agent), // possible removal
                                'cookiepath'     => null, //$this->_objConfig->getValue ( 'auth_cookiepath', 'security', NULL ),
                                'cookiedomain'   => null, //$this->_objConfig->getValue ( 'auth_cookiedomain', 'security', NULL ),
                                'cookiesecure'   => true, //$this->_objConfig->getValue ( 'auth_cookiesecure', 'security', true )
        );
        
        // Configure garbage collection
        ini_set('session.gc_probability', (int) $this->sessconfig['gc_probability']);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', ($this->sessconfig['expiration'] == 0) ? 86400 : $this->sessconfig['expiration']);
        
        // create the gearman client
        $this->gmc= new GearmanClient();
        // add the default server (localhost)
        $this->gmc->addServer();
        
        // add a callback
        //$this->gmc->setCreatedCallback(array($this, 'reverse_created'));
        //$this->gmc->setDataCallback(array($this, "reverse_data"));
        $this->gmc->setStatusCallback(array($this, "reverse_status"));
        $this->gmc->setCompleteCallback(array($this, "reverse_complete"));
        $this->gmc->setFailCallback(array($this, "reverse_fail"));
        
        return $this;
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
        //var_dump($this->serviceData);
        $task= $this->gmc->addTask("reverse", "foo", $this->serviceData);
        //return $this->serviceData;
        if (! $this->gmc->runTasks())
        {
            echo "ERROR " . $this->gmc->error() . "\n";
            exit;
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
    
    public function reverse_created($task)
    {
        echo "CREATED: " . $task->jobHandle() . "\n";
    }
    
    public function reverse_status($task)
    {
        echo "STATUS: " . $task->jobHandle() . " - " . $task->taskNumerator() .
             "/" . $task->taskDenominator() . "\n";
    }
    
    public function reverse_complete($task)
    {
        echo "COMPLETE: " . $task->jobHandle() . ", " . $task->data() . "\n";
    }
    
    public function reverse_fail($task)
    {
        echo "FAILED: " . $task->jobHandle() . "\n";
    }
    
    public function reverse_data($task)
    {
        echo "DATA: " . $task->data() . "\n";
    }
    
}