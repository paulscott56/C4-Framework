<?php

/**
 * System configuration
 *
 * System configuration for Chisimba
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
 * @package   config
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: altconfig_class_inc.php 18135 2010-06-22 12:48:18Z paulscott $
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */

/**
 * Class to manipulate system configs
 *
 * The altconfig class manipulates system configurations stored in the config.xml file in the config directory of the root
 * of the application.
 *
 * @category  Chisimba
 * @package   config
 * @author    Paul Scott <pscott@uwc.ac.za>
 * @copyright 2007 Paul Scott
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za
 * @see       core
 */
class altconfig extends object {
    /**
     * The pear config object
     *
     * @access public
     * @var    string
     */
    protected $_objPearConfig;

    /**
     * The path of the files to be read or written
     *
     * @access public
     * @var    string
     */
    public $_path = null;

    /**
     * The root object for configs read
     *
     * @access private
     * @var    string
     */
    protected $_root;

    /**
     * The root object for properties read
     *
     * @access private
     * @var    string
     */
    protected $_property;

    /**
     * The options value for altconfig read / write
     *
     * @access private
     * @var    string
     */
    protected $_options;

    /**
     * The sysconfig object for sysconfig storage
     *
     * @access private
     * @var    array
     */
    protected $_sysconfigVars;

    /**
     * languagetext object
     *
     * @var object
     */
    public $Text;

    /**
     * The global error callback for altconfig errors
     *
     * @access public
     * @var    string
     */
    public $_errorCallback;

    /**
     * Constructor
     *
     * This object needs external construction
     *
     * @return void
     * @access public
     * @throws customException Exception description (if any) ...
     */
    public function __construct() {
        // instantiate object
        $mepath = $_SERVER["SCRIPT_FILENAME"];
        $mepath = str_replace('index.php', '', $mepath);
        ini_set ( 'include_path', ini_get ( 'include_path' ) . PATH_SEPARATOR . $mepath.'lib/pear/');
        
        try {
            if (! class_exists ( 'Config.php', true )) {
                require_once 'Config.php';
            }
            $this->_objPearConfig = new Config ( );
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to parse config options.
     * For use when reading configuration options
     *
     * @access protected
     * @param  string    $config   xml file or PHPArray to parse
     * @param  string    $property used to set property value of incoming config string
     *                             $property can either be:
     *                             1. PHPArray
     *                             2. XML
     * @return boolean   True/False result.
     *
     */
    public function readConfig($config = FALSE, $property) {
        try {
            // read configuration data and get reference to root
            if (! isset ( $this->_path ))
                $this->_path = "config/";
            if (isset ( $this->_root )) {
                return $this->_root;
            }
            $this->_root = & $this->_objPearConfig->parseConfig ( "{$this->_path}config.xml", $property );
            if (PEAR::isError ( $this->_root )) {
                //throw new Exception('word_read_fail');
                log_debug ( $this->_root->getMessage () );
                echo $this->_root->getMessage () . "<br />";
                die ( "Error in config.xml!" );
            }
            return $this->_root;
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

    }

    /**
     * Method to wirte config options.
     * For use when writing configuration options
     *
     * @access public
     * @param  string  values   to be saved
     * @param  string  property used to set property value of incoming config string
     *                          $property can either be:
     *                          1. PHPArray
     *                          2. XML
     * @return boolean TRUE for success / FALSE fail.
     */
    public function writeConfig($values, $property) {
        // set xml root element
        try {
            $this->_objPearConfig = new Config ( );
            $this->_options = array ('name' => 'Settings' );
            $this->_objPearConfig->parseConfig ( $values, "PHPArray" );
            if (! isset ( $this->_path ))
                $this->_path = "config/";
            if (file_exists ( $this->_path . 'config.xml' )) {
                unlink ( $this->_path . 'config.xml' );
            }
            $this->_objPearConfig->writeConfig ( "{$this->_path}config.xml", $property, $this->_options );
            $this->readConfig ( '', 'XML' );
            return true;
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

    }

    /**
     * Public method to append arbitrary arrays of additional parameters to the config file
     *
     * @param  array   $newsettings
     * @return boolean
     */
    public function appendToConfig($newsettings) {
        try {
            $this->_objPearConfig = new Config ( );
            $configfile = $this->readConfig ( FALSE, 'PHPArray' );
            $arr = $configfile->toArray ();
            $a2 = $arr ['root'] ['Settings'];
            $final = array_merge ( $a2, $newsettings );
            //write back the file...
            $this->writeConfig ( $final, 'XML', FALSE );

            return TRUE;

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pvalue The value code of the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function getItem($pname) {
        try {
            if ($this->_root == NULL) {
                $this->readConfig ( FALSE, 'XML' );
            }
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            //check to see if one of them isset to search by
            if (isset ( $pname )) {
                $this->SettingsDirective = & $Settings->getItem ( "directive", "{$pname}" );
                if ($this->SettingsDirective == false) {
                    return FALSE;
                } else {
                    $value = $this->SettingsDirective->getContent ();
                    return $value;
                }
            }

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pvalue The value code of the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function setItem($pname, $pvalue) {
        try {
            //Read conf
            if ($this->_root == NULL) {
                $this->readConfig ( FALSE, 'XML' );
            }
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            //check to see if one of them isset to search by
            $this->SettingsDirective = & $Settings->getItem ( "directive", "{$pname}" );
            $this->SettingsDirective->setContent ( $pvalue );
            $result = $this->objConf->writeConfig ();
            return $result;

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to read sysconfig Properties options.
     * For use when reading sysconfig Properties options
     *
     * @access public
     * @param  string  path     to the properties config
     * @param  string  property used to set property value of incoming config string
     *                          $property can either be:
     *                          1. PHPArray
     *                          2. XML
     * @return boolean TRUE for success / FALSE fail .
     *
     */
    public function readProperties($path = false, $property) {
        // read configuration data and get reference to root
        try {
            if (! isset ( $path ))
                $path = "config";
            $this->_property = & $this->_objPearConfig->parseConfig ( "{$path}/sysconfig_properties.xml", $property );
            if ($this->_property != TRUE) {
                return FALSE;
            } else {
                return $this->_property;
            }
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to write sysconfig Properties options.
     * For use when writing sysconfig Properties options
     *
     * @access public
     * @param  PHParray $propertyValues which consists of :
     * @var    string   $pmodule The module code of the module owning the config item
     * @var string $pname The name of the parameter being set, use UPPER_CASE
     * @var string $plabel A label for the config parameter, usually a language string
     * @var string $value The value of the config parameter
     * @var boolean $isAdminConfigurable TRUE | FALSE Whether the parameter is admin configurable or not
     * @param  string   property        used to set property value of incoming config string
     *                                  $property can either be:
     *                                  1. PHPArray
     *                                  2. XML
     * @return boolean  TRUE for success / FALSE fail .
     *
     */
    public function writeProperties($propertyValues, $property) {
        try {
            // set xml root element
            $this->_options = array ('name' => 'sysConfigSettings' );
            $this->_property = & $this->_objPearConfig->parseConfig ( $propertyValues, "PHPArray" );
            $this->_objPearConfig->writeConfig ( "config/sysconfig_properties.xml", $property, $this->_options );
            if ($this->_objPearConfig != TRUE) {
                throw new Exception ( 'word_read_fail' );
            } else {
                return true;
            }

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to update a configuration parameter.
     *
     * @var string  $pmodule The module code of the module owning the config item
     * @var string  $pname The name of the parameter being set, use UPPER_CASE
     * @var string  $pvalue The value of the config parameter
     * @var boolean $isAdminConfigurable TRUE | FALSE Whether the parameter is admin configurable or not
     */
    public function updateParam($pname, $pmodule = False, $pvalue, $isAdminConfigurable = False) {
        try {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            //check to see if one of them isset to search by
            if (isset ( $pname )) {
                $SettingsDirective = & $Settings->getItem ( "directive", "{$pname}" );
            }
            //finally unearth whats inside
            if (! $SettingsDirective) {
                return FALSE;
            } else {
                $SettingsDirective->setContent ( $pvalue );
                $path = "config/";
                if (($path !== false) && (file_exists ( $path . 'config.xml' ))) {
                    unlink ( $path . 'config.xml' );
                    $value = $this->_objPearConfig->writeConfig ();
                    return $value;
                }
            }

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to get a system configuration parameter.
     *
     * @var    string $pmodule The module code of the module owning the config item
     * @var    string $pname The name of the parameter being set, use UPPER_CASE
     * @return string $value The value of the config parameter
     */
    public function getParam($pname, $pmodule) {
        try {
            //Read conf
            if (! isset ( $this->_property )) {
                $read = $this->readProperties ( 'XML' );
            }
            if ($read == FALSE) {
                return $read;
            }
            //Lets get the parent node section first
            $Settings = & $this->_property->getItem ( "section", "sysConfigSettings" );
            //Now onto the directive node
            //check to see if one of them isset to search by
            if (isset ( $pname ))
                $SettingsDirective = & $Settings->getItem ( "directive", "{$pname}" );
            if (isset ( $pmodule ))
                $SettingsDirective = & $Settings->getItem ( "directive", "{$pmodule}" );
                //finally unearth whats inside
            if (! $SettingsDirective) {
                return FALSE;
            } else {
                $value = $SettingsDirective->getContent ();
                return $value;
            }

        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }
    }

    /**
     * Method to read a configuration parameter. This is the preferred
     * method for routine lookups.
     *
     * @public string $module The module code of the module owning the config item
     * @public string $name The name of the parameter being set, use UPPER_CASE
     *
     * @return only the value of the parameter
     */
    public function getValue($pname, $pmodule = "_site_") {
        if (! isset ( $this->$pname )) {
            //$this->getParam('',$pmodule);
        }
        if (isset ( $this->$pname )) {
            return $this->$pname;
        } else if (defined ( $pname )) {
            $defValue = constant ( $pname );
            $this->insertParam ( $pname, $pmodule, $defValue, TRUE );
            return $defValue;
        } else {
            return NULL;
        }
    }

    /**
     * The property get name of the getSiteName
     *
     * @access public
     * @return the    name of the site as string
     */
    public function getSiteName() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITENAME" );
        //finally unearth whats inside
        $siteName = $SettingsDirective->getContent ();
        return $siteName;
        // KEWL_SITENAME;
    }

    /**
     * The property set name of the getSiteName
     *
     * @access public
     * @param  value  of the change to be made
     * @return bool   true / false
     */
    public function setSiteName($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //return $this->getValue("sitename");
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITENAME" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
    }

    /**
     * The property get name of the System type
     *
     * @access public
     * @return the    name of the systemtype as string
     */
    public function getSystemType() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEM_TYPE" );
        //finally unearth whats inside
        $systemtype = $SettingsDirective->getContent ();
        return $systemtype;
    }

    /**
     * The property set name of the Systemtype
     *
     * @access public
     * @param  value  of the change to be made
     * @return bool   true / false
     */
    public function setSystemType($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //return $this->getValue("sitename");
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEM_TYPE" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
    }

    /**
     * Get short name of the institutionShortName
     *
     * @access public
     * @return the    short name of the site as string
     */
    public function getinstitutionShortName() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_INSTITUTION_SHORTNAME" );
        //finally unearth whats inside
        $institutionShortName = $SettingsDirective->getContent ();
        return $institutionShortName;
        // KEWL_INSTITUTION_SHORTNAME;
    }

    /**
     * Set short name of the institutionShortName
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setinstitutionShortName($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITENAME" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
    }

    /**
     * Get name of the institution
     *
     * @access public
     * @return the    short name of the institution as string
     */
    public function getinstitutionName() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_INSTITUTION_NAME" );
        //finally unearth whats inside
        $institutionName = $SettingsDirective->getContent ();
        return $institutionName;
        // KEWL_INSTITUTION_NAME;
    }

    /**
     * Set name of the institution
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setinstitutionName($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_INSTITUTION_NAME" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_INSTITUTION_NAME;
    }

    /**
     * The email address of the website
     *
     * @access public
     * @return the    email address for the site as string
     */
    public function getsiteEmail() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEEMAIL" );
        //finally unearth whats inside
        $getsiteEmail = $SettingsDirective->getContent ();
        return $getsiteEmail;
        // KEWL_SITEEMAIL;
    }

    /**
     * The email address of the website
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsiteEmail($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEEMAIL" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_SITEEMAIL;
    }

    /**
     * The script timeout
     *
     * @access public
     * @return the    script timout in seconds
     */
    public function getsystemTimeout() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEMTIMEOUT" );
        //finally unearth whats inside
        $getsystemTimeout = $SettingsDirective->getContent ();
        return $getsystemTimeout;
        // KEWL_SYSTEMTIMEOUT;
    }

    /**
     * The script timeout
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsystemTimeout($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SYSTEMTIMEOUT" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_SYSTEMTIMEOUT;
    }

    /**
     * Get prelogin module
     *
     * @access public
     * @return the    system prelogin module settings
     */
    public function getPrelogin() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_PRELOGIN_MODULE" );
        //finally unearth whats inside
        $getPrelogin = $SettingsDirective->getContent ();
        return $getPrelogin;

    }

    /**
     * Set prelogin module
     *
     * @access public
     * @return the    system prelogin module settings
     */
    public function setPrelogin() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_PRELOGIN_MODULE" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
    }

    /**
     * The URL path of the site
     *
     * @access public
     * @return the    the site path, normally / as string
     */
    public function getSitePath() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEROOT" );
        //finally unearth whats inside
        $getsitePath = $SettingsDirective->getContent ();

        return $getsitePath;
        // KEWL_SITEROOT;
    }

    /**
     * The URL root of the site
     *
     * @access public
     * @return the    the site root, normally / as string
     */
    public function getsiteRoot() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITE_ROOT" );
        //finally unearth whats inside
        $getsiteRoot = $SettingsDirective->getContent ();

        return $getsiteRoot;
        // KEWL_SITE_ROOT;
    }

    /**
     * The URL root of the site
     *
     * @access public
     * @param value of the change to be made
     * @return bool true / false
     */
    public function setsiteRoot($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITE_ROOT" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_SITE_ROOT;
    }

    /**
     * The folder name of the default skin
     *
     * @access public
     * @return the    default skin name (normally default)
     *                leading and trailing forward slash (/)  as string
     */
    public function getdefaultSkin() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_SKIN" );
        //finally unearth whats inside
        $getdefaultSkin = $SettingsDirective->getContent ();
        return $getdefaultSkin;
        // KEWL_DEFAULT_SKIN;
    }

    /**
     * The folder name of the default skin
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultSkin($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_SKIN" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_SKINROOT;
    }

    /**
     * The skin root
     *
     * @access public
     * @return the    skin root (normally /skin/)
     *                leading and trailing forward slash (/)  as string
     */
    public function getskinRoot() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SKIN_ROOT" );
        //finally unearth whats inside
        $getskinRoot = $SettingsDirective->getContent ();
        return $getskinRoot;

    // KEWL_SKINROOT;
    }

    /**
     * Set skin root
     *
     * @param  $value     -string
     * @access public
     * @return TRUE/FALSE
     */
    public function setskinRoot($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SKIN_ROOT" );
        //finally unearth whats inside
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_DEFAULT_SKIN;
    }

    /**
     * The name of the default language (normally english)
     *
     * @access public
     * @return the    name of the default language as string
     */
    public function getdefaultLanguage() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE" );
        //finally unearth whats inside
        $getdefaultLanguage = $SettingsDirective->getContent ();
        return $getdefaultLanguage;
        // KEWL_DEFAULT_LANGUAGE;
    }

    /**
     * The name of the default language (normally english)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultLanguage($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_DEFAULT_LANGUAGE;
    }

    /**
     * The abbreviation of the default language (normally EN)
     *
     * @access public
     * @return the    abbreviation of the default language as string
     */
    public function getdefaultLanguageAbbrev() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE_ABBREV" );
        //finally unearth whats inside
        $getdefaultLanguageAbbrev = $SettingsDirective->getContent ();

        return $getdefaultLanguageAbbrev;
        // KEWL_DEFAULT_LANGUAGE_ABBREV;
    }

    /**
     * The abbreviation of the default language (normally EN)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setdefaultLanguageAbbrev($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DEFAULT_LANGUAGE_ABBREV" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_DEFAULT_LANGUAGE_ABBREV;
    }

    /**
     * The default extension for banners (jpg, gif, png)
     *
     * @access public
     * @return default extension for banners (jpg, gif, png) as string
     */
    public function getbannerExtension() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_BANNER_EXT" );
        //finally unearth whats inside
        $getbannerExtension = $SettingsDirective->getContent ();

        return $getbannerExtension;
        // KEWL_BANNER_EXT;
    }

    /**
     * The default extension for banners (jpg, gif, png)
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setbannerExtension($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_BANNER_EXT" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_BANNER_EXT;
    }

    /**
     * The default site root path as string
     *
     * @access public
     * @return default site root path as string
     */
    public function getsiteRootPath() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEROOT_PATH" );
        //finally unearth whats inside
        $getsiteRootPath = $SettingsDirective->getContent ();
        return $getsiteRootPath;
        // KEWL_SITEROOT_PATH;
    }

    /**
     * The default site root path as string
     *
     * @access public
     * @param value of the change to be made
     * @return bool   true / false
     */
    public function setsiteRootPath($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SITEROOT_PATH" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_SITEROOT_PATH;
    }

    /**
     * Whether to allow users to register themselves
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setallowSelfRegister($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ALLOW_SELFREGISTER" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
        // KEWL_ALLOW_SELFREGISTER;
    }

    /**
     * Whether to allow users to register themselves
     *
     * @return TRUE or FALSE
     */
    public function getallowSelfRegister() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ALLOW_SELFREGISTER" );
        //finally unearth whats inside
        $getallowSelfRegister = $SettingsDirective->getContent ();
        return $getallowSelfRegister;
        // KEWL_ALLOW_SELFREGISTER;
    }

    /**
     * Returns name of post-login module
     *
     * @access public
     * @return name   of post-login module
     */
    public function getdefaultModuleName() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_POSTLOGIN_MODULE" );
        //finally unearth whats inside
        $getdefaultModuleName = $SettingsDirective->getContent ();
        return $getdefaultModuleName;
        // KEWL_POSTLOGIN_MODULE;
    }

    /**
     * Method to set the default module name
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setdefaultModuleName($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_POSTLOGIN_MODULE" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
    }

    /**
     * Method to get Value of LDAP
     *
     * @access  PUBLIC
     * @Returns whether LDAP functionality should be used
     */
    public function getuseLDAP() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        if (function_exists ( "ldap_connect" )) {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "LDAP_USED" );
            //finally unearth whats inside
            $getuseLDAP = $SettingsDirective->getContent ();
            if ($getuseLDAP == "FALSE") {
                $getuseLDAP = FALSE;
            }
            return $getuseLDAP;
        } else {
            return FALSE;
        }
    }

    /**
     * Method to set LDAP as used
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setuseLDAP($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "LDAP_USED" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;

    }

    /**
     * Check if system is an alumni systemtype
     *
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function isAlumni() {
        //I dont know what this does, so am just setting it to false now
        return FALSE;
    }

    /**
     * Returns the country 2-letter code
     *
     * Defaults to 'ZA'
     *
     * @access  public
     * @returns string $code
     */
    public function getCountry() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SERVERLOCATION" );
        //finally unearth whats inside
        $getCountry = $SettingsDirective->getContent ();

        if ($getCountry == NULL) {
            $getCountry = 'ZA';

        }
        return $getCountry;
    }

    /**
     * ---------------- FILE SYSTEM PROPERTIES -----------*
     */

    /**
     * Returns the base path for all user files
     *
     * @access public
     * @return base   path for user files
     */
    public function getcontentBasePath() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_BASEPATH" );
        //finally unearth whats inside
        $getcontentBasePath = $SettingsDirective->getContent ();

        return $getcontentBasePath;
        // KEWL_CONTENT_BASEPATH;
    }

    /**
     * Returns the path for content files
     *
     * @access public
     */
    public function getcontentPath() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally unearth whats inside
        $getcontentPath = $SettingsDirective->getContent ();

        return $getcontentPath;
    }

    /**
     * Set the path for content files
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setcontentPath($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_CONTENT_PATH;
    }

    /**
     * Returns the root path for content files
     *
     * @access public
     * @return content root path
     */
    public function getcontentRoot() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally unearth whats inside
        $getcontentRoot = $SettingsDirective->getContent ();

        return $getcontentRoot;
        // KEWL_CONTENT_PATH;
    }

    /**
     * Set the root path for content files
     *
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function setcontentRoot($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_CONTENT_PATH" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
        // KEWL_CONTENT_PATH;
    }

    /**
     * Gets error reporting Setting
     *
     * @access public
     * @return geterror_reporting setting
     */
    public function geterror_reporting() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ERROR_REPORTING" );
        //finally unearth whats inside
        $geterror_reporting = $SettingsDirective->getContent ();

        return $geterror_reporting;

    }

    /**
     * Gets enable adm Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getenable_adm() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "ENABLE_ADM" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("ENABLE_ADM" => "FALSE" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $getenable_adm = $SettingsDirective->getContent ();

        return $getenable_adm;
    }

    /**
     * Gets flag to disable XML
     *
     * @access public
     * @returns string
     */
    public function getNoXML() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "NO_XML" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            // Little hack here to get around a strange quirk with the config
            if (! defined ( 'NO_XML_FLAG' )) {
                $newsettings = array ("NO_XML" => "1" );
                $this->appendToConfig ( $newsettings );
            } else {
                define ( 'NO_XML_FLAG', 1 );
            }
            return FALSE;
        }
        //finally unearth whats inside
        $noXML = $SettingsDirective->getContent ();

        return $noXML;

    }

    /**
     * Gets enable memcache Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getenable_memcache() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "ENABLE_MEMCACHE" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("ENABLE_MEMCACHE" => "FALSE" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $getenable_memcache = $SettingsDirective->getContent ();

        return $getenable_memcache;
    }

    /**
     * Gets enable memcache Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getenable_dbabs() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );

        //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "DATABASE_ABSTRACTION" );
        //var_dump($SettingsDirective);
        if($SettingsDirective == FALSE)
        {
                $newsettings = array("DATABASE_ABSTRACTION" => "MDB2");
                $this->appendToConfig($newsettings);
                return "MDB2";
        }
        //finally unearth whats inside
        $getenable_memcache = $SettingsDirective->getContent();

        return $getenable_memcache;
    }

    /**
     * Gets enable APC Setting
     *
     * @access public
     * @return getenable APC setting
     */
    public function getenable_apc() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "ENABLE_APC" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("ENABLE_APC" => "FALSE" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $getenable_apc = $SettingsDirective->getContent ();

        return $getenable_apc;
    }

    /**
     * Gets cache TTL Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getcache_ttl() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "CACHE_TTL" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("CACHE_TTL" => "3600" );
            $this->appendToConfig ( $newsettings );
            return FALSE;
        }
        //finally unearth whats inside
        $cache_ttl = $SettingsDirective->getContent ();

        return $cache_ttl;
    }
    
    /**
     * Gets language cache Setting
     *
     * @access public
     * @return getenable langcache setting
     */
    public function getlangcache() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "LANGCACHE" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("LANGCACHE" => "TRUE" );
            $this->appendToConfig ( $newsettings );
            return TRUE;
        }
        //finally unearth whats inside
        $langcache = $SettingsDirective->getContent ();

        return $langcache;
    }

    /**
     * Gets enable proxy Setting
     *
     * @access public
     * @return getenable adm setting
     */
    public function getProxy() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_PROXY" );
        //var_dump($SettingsDirective);
        if ($SettingsDirective == FALSE) {
            $newsettings = array ("KEWL_PROXY" => "NULL" );
            $this->appendToConfig ( $newsettings );
            return NULL;
        }
        //finally unearth whats inside
        $getProxy = $SettingsDirective->getContent ();

        return $getProxy;
    }

    /**
     * Method to return the modulepath setting from the config file
     *
     * @param  void
     * @return string
     */
    public function getModulePath() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );

        try {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_MODULE_PATH" );
            if (! ($SettingsDirective)) {
                throw new Exception ( 'Module path is missing' );
            }
            //finally unearth whats inside
            $modulePath = $SettingsDirective->getContent ();
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

        return $modulePath;
    }

    /**
     * Method to return the moduleURI setting from the config file
     *
     * @param  void
     * @return string
     */
    public function getModuleURI() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );

        try {
            //Lets get the parent node section first
            $Settings = & $this->_root->getItem ( "section", "Settings" );
            //Now onto the directive node
            $SettingsDirective = & $Settings->getItem ( "directive", "MODULE_URI" );
            if (! ($SettingsDirective)) {
                throw new Exception ( 'Module URI is missing' );
            }
            //finally unearth whats inside
            $moduleURI = $SettingsDirective->getContent ();
        } catch ( Exception $e ) {
            throw new customException ( $e->getMessage () );
            exit ();
        }

        return $moduleURI;
    }

    /**
     * Set error reporting Settings
     * @access public
     * @param  value  to be changed
     * @return TRUE   or FALSE
     */
    public function seterror_reporting($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ERROR_REPORTING" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;
    }

    /**
     * Set dsn settings
     * @access public
     * @param  $value -this is the value we want to inset
     * @return $bool  - TRUE /FALSE
     */
    public function setDsn($value) {
        //parse the dsn to an array for Oracle values
        $dsnparsed = $this->parseDSN ( $value );

        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB_DSN" );
        $SettingsDirective = & $Settings->getItem ( "directive", "CHISIMBA_DB_SERVER" );
        $SettingsDirective = & $Settings->getItem ( "directive", "CHISIMBA_DB_PROTOCOL" );
        $SettingsDirective = & $Settings->getItem ( "directive", "CHISIMBA_DB_USER" );
        $SettingsDirective = & $Settings->getItem ( "directive", "CHISIMBA_DB_PASS" );
        $SettingsDirective = & $Settings->getItem ( "directive", "CHISIMBA_DB_PORT" );

        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();
        return $bool;

    }

    /**
     * Get dsn settings
     *
     * @access public
     * @return $Dsn
     */
    public function getDsn() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB_DSN" );
        //finally unearth whats inside
        $Dsn = KEWL_DB_DSN; //$SettingsDirective->getContent();


        return $Dsn;
    }

    /**
     * Get Second dsn settings
     *
     * @access public
     * @return $Dsn2
     */
    public function getDsn2() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB2_DSN" );
        //finally unearth whats inside
        $Dsn2 = $SettingsDirective->getContent ();

        return $Dsn2;

    }

    /**
     * Set dsn2 settings
     *
     * @access public
     * @param  $value -this is the value we want to inset
     * @return $bool  - TRUE /FALSE
     */
    public function setDsn2($value) {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_DB2_DSN" );
        //finally save value
        $SettingsDirective->setContent ( $value );
        $bool = $this->_objPearConfig->writeConfig ();

        return $bool;
    }

    /**---------------- MIRRORING PROPERTIES -----------**/

    /**
     * Return's server name (used for dynamic mirroring)
     */
    public function serverName() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_SERVERNAME" );
        //finally unearth whats inside
        $serverName = $SettingsDirective->getContent ();
        if ($serverName != null) {
            return $serverName;
        } else {
            return 'default';
        }
    }

    /**
     * Returns mirror webservice WSDL URL (in production will usually be a service
     * on a non-standard port on the localhost)
     *
     * @return string WSDL URL
     */
    public function mirrorWsdlUrl() {
        if (! is_object ( $this->_root ))
            $this->_root = &$this->readConfig ( '', 'XML' );
            //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_MIRROR_WSDL_URL" );
        //finally unearth whats inside
        $mirrorWsdlUrl = $SettingsDirective->getContent ();
        if ($mirrorWsdlUrl != null) {
            return $mirrorWsdlUrl;
        } else {
            return NULL;
        }

    }

    /**
     * Whether to enable logging or not
     *
     * @access public
     * @return string true or false
     */
    public function getenable_logging() {
        if (! is_object ( $this->_root )) {
            $this->_root = &$this->readConfig ( '', 'XML' );
        }
        //Lets get the parent node section first
        $Settings = & $this->_root->getItem ( "section", "Settings" );
        //Now onto the directive node
        $SettingsDirective = & $Settings->getItem ( "directive", "KEWL_ENABLE_LOGGING" );
        //finally unearth whats inside
        $getlogging = $SettingsDirective->getContent ();

        return $getlogging;
    }

    /**
     * Whether to show the search box or not
     *
     * @access public
     * @return string true or false
     */
    public function getenable_searchBox() {
        $getsearch = $this->getItem ( "SHOW_SEARCH_BOX" );

        return $getsearch;
    }

    /**
     * The error callback function, defers to configured error handler
     *
     * @param  string $error
     * @return void
     * @access public
     */
    public function errorCallback($exception) {
        throw new customException ( $exception );
        exit ();
    }

    /**
     * Method to parse the DSN
     *
     * @access public
     * @param string $dsn
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
            $parsed ['protocol'] = $arr [1];
            $parsed ['protocol'] = ! $arr [2] ? $arr [1] : $arr [2];
        } else {
            $parsed ['protocol'] = $str;
            $parsed ['protocol'] = $str;
        }

        if (! count ( $dsn )) {
            return $parsed;
        }
        // Get (if found): username and password
        if (($at = strrpos ( $dsn, '@' )) !== false) {
            $str = substr ( $dsn, 0, $at );
            $dsn = substr ( $dsn, $at + 1 );
            if (($pos = strpos ( $str, ':' )) !== false) {
                $parsed ['user'] = rawurldecode ( substr ( $str, 0, $pos ) );
                $parsed ['pass'] = rawurldecode ( substr ( $str, $pos + 1 ) );
            } else {
                $parsed ['user'] = rawurldecode ( $str );
            }
        }
        //server
        if (($col = strrpos ( $dsn, ':' )) !== false) {
            $strcol = substr ( $dsn, 0, $col );
            $dsn = substr ( $dsn, $col + 1 );
            if (($pos = strpos ( $strcol, '/' )) !== false) {
                $parsed ['server'] = rawurldecode ( substr ( $strcol, 0, $pos ) );
            } else {
                $parsed ['server'] = rawurldecode ( $strcol );
            }
        }
        //now we are left with the port and mailbox so we can just explode the string and clobber the arrays together
        $pm = explode ( "/", $dsn );
        $parsed ['port'] = $pm [0];
        $parsed ['mailbox'] = $pm [1];
        $dsn = NULL;

        return $parsed;
    }

    /**
     * Function to determine if a property exist in the config file or not, true if exist/false if doesn't exist - in a config file
     * @param  string $propertyName is the property name eg SHOW_SEARCH_BOX
     * @author Emmanuel Natalis
     */
      public function isPropertyExist($propertyName)
      {

        if($this->getItem($propertyName)=="")
        {
           return 'FALSE';
        } else
        {
           return 'TRUE';
        }
      }

      /**
     * Function to get propaerty value returns false if doesn't exist in a config file
     * @param  string $propertyName is the property name eg SHOW_SEARCH_BOX
     * @author Emmanuel Natalis
     */
      public function getPropertyValue($propertyName)
      {
         return $this->getItem($propertyName);
      }

    /**
     * Destructor
     */
    public function __destruct() {

    }
}

?>
