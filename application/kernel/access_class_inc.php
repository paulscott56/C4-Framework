<?php

/**
 * Access Class.
 *
 * The Access class handles some of the user authentication and permissions system access.
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
 * @version    $Id: access_class_inc.php 19719 2010-11-14 10:41:02Z davidwaf $
 * @package    core
 * @subpackage access
 * @author     Paul Scott <pscott@uwc.ac.za>
 * @copyright  2006-2007 AVOIR
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @link       http://avoir.uwc.ac.za
 * @see        core
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
 * Access class to control access.
 *
 * Access class to control user access and the user ACL and permissions system
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
class access extends object {

    public $objContext;
    public $objUser;
    private $objLog;
    private $userid;
    private $objSysConfig;
    private $logActivity;
    private $preloginModule;
    public $objConfig;
    private $loggedInUsers;
    private $logoutdestroy = true;
    private $modulesNotToLog;

    /**
     * Constructor for the access class.
     *
     * @param object $objEngine  the engine object reference
     * @param string $moduleName The module name
     */
    public function __construct($objEngine, $moduleName) {
        parent::__construct($objEngine, $moduleName);
        $this->objContext = $this->getObject("dbcontext", "context");
        $this->objUser = $this->getObject('user', 'security');
        $this->loggedInUsers = $this->getObject("loggedinusers", "security");
        $this->objLog = $this->getObject('useractivity', 'security');
        $this->userid = $this->objUser->userid();
        $this->objSysConfig = $this->getObject('dbsysconfig', 'sysconfig');
        $this->logActivity = $this->objSysConfig->getValue('LOG_USER_ACTIVITY', 'security');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->preloginModule = $this->objConfig->getPrelogin('KEWL_PRELOGIN_MODULE');
        $xlogoutdestroy = $this->objConfig->getValue('auth_logoutdestroy', 'security', true);
        if (strtoupper($xlogoutdestroy) == 'TRUE') {
            $this->logoutdestroy = true;
        } else {
            $this->logoutdestroy = false;
        }
        $modulesNotToLogStr = $this->objSysConfig->getValue('EXCLUDE_LOGGING', 'security');
        $this->modulesNotToLog = explode(",", $modulesNotToLogStr);
    }

    /**
     * Method to control access to the module.
     *
     * Called by engine before the dispatch method.
     *
     * @param object $module The module controller.
     * @param string $action The action param passed to the dispatch method
     *
     * @return array The next action to be done
     */
    public function dispatchControl($module, $action) {
        /*
          // Extract isRegistered
          extract( $this->getModuleInformation( 'decisiontable' ) );
          // Safety net if the decision table module has not been registered.
          if( !$isRegistered ) {
          return $module->dispatch( $action );
          }
          // Get an instance of the decisiontable object.
          $this->objDT = $this->getObject( 'decisiontable','decisiontable' );
          // Create the decision table for the current module
          $this->objDT->create( $this->moduleName );
          // Collect information from the database.
          $this->objDT->retrieve( $this->moduleName );
          // Test the current action being requested, to determine if it requires access control.
          if( $this->objDT->hasAction( $action ) ) {
          // Is the action allowed?
          if ( !$this->isValid( $action ) ) {
          // redirect and indicate the user does not have sufficient access.
          return $this->nextAction( 'noaction', array('modname' => $this->moduleName, 'actionname' => $action), 'redirect' );
          }
          }
          // Action allowed continue.
         * 
         */
        //if we hit prelogin module, logout, if logoutdestroy is false, else update
        //last activity
        if (!$this->logoutdestroy) {
            if ($this->objUser->isLoggedIn()) {
                $this->loggedInUsers->doUpdateLogin($this->userid, $this->objContext->getContextCode());
            }
            if ($this->moduleName == $this->preloginModule && $action == '') {
                $this->loggedInUsers->doLogOut($this->userid);
            }
        }

        $logThisModule = TRUE;
        if (in_array($this->moduleName, $this->modulesNotToLog)) {
            $logThisModule = FALSE;
        }
        if (strtoupper($this->logActivity) == 'TRUE' && $logThisModule) {
            $fields = array(
                "userid" => $this->userid,
                "module" => $this->moduleName,
                "action" => $action,
                "contextcode" => $this->objContext->getContextCode(),
                "createdon" => strftime('%Y-%m-%d %H:%M:%S', mktime())
            );

            $this->objLog->log($fields);
        }
        return $module->dispatch($action);
    }

    /**
     * Method to test if the action is valid.
     *
     * @param string $action  the action.
     * @param string $default the default to be used if action does not exist.
     *
     * @return bool true|false True if action valid, otherwise False.
     */
    public function isValid($action, $default = TRUE) {
        //return $this->objDT->isValid($action, $default);
        return TRUE;
    }

    /**
     * Method to gather information about the given module.
     *
     * @param string $moduleName The module name.
     *
     * @return string $info
     */
    public function getModuleInformation($moduleName) {
        $objModAdmin = $this->getObject('modules', 'modulecatalogue');
        $array = $objModAdmin->getArray('SELECT isadmin, dependscontext FROM tbl_modules WHERE module_id=\'' . $moduleName . '\'');
        $info = array();
        $info['isRegistered'] = isset($array[0]);
        $info['isAdminMod'] = $info['isRegistered'] ? $array[0]['isadmin'] : NULL;
        $info['isContextMod'] = $info['isRegistered'] ? $array[0]['dependscontext'] : NULL;
        return $info;
    }

    /**
     * Method to control access to the module based on the modules configuration parameters.
     *
     * @param string $moduleName The module name.
     *
     * @return array the next action to be completed.
     */
    public function getPermissions($moduleName) {

        // Extract isRegistered, isAdminMod, isContextMod
        extract($this->getModuleInformation($moduleName));
        // The module is not registered redirect with option to register.
        if (!$isRegistered) {
            return $this->nextAction('notregistered', array('modname' => $moduleName), 'redirect');
        }
        // The module is admin only, allow only admin users.
        if ($isAdminMod) {
            $objUser = $this->getObject('user', 'security');
            if (!$objUser->isAdmin()) {
                return $this->nextAction('nopermission', array('modname' => $moduleName), 'redirect');
            }
        }
        // The module depends on being in a context, redirect if not in a context.
        if ($isContextMod) {
            $objContext = $this->getObject('dbcontext', 'context');
            if (!$this->objContext->isInContext()) {
                return $this->nextAction('nocontext', array('modname' => $moduleName), 'redirect');
            }
        }
    }

    public function getAreas($moduleName) {
        // areas are either isRegistrered, isAdminMod or isContextMod
        $areas = $this->luAdmin->perm->getAreas();
        return $areas;
    }

}

?>