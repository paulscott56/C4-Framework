<?php
require_once __DIR__ . '/autoload.php';

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(__DIR__ . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'default'));

// Define application execution type (local or test)
defined('APPLICATION_EXEC')
    || define('APPLICATION_EXEC', (getenv('APPLICATION_EXEC') ? getenv('APPLICATION_EXEC') : 'local'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    realpath(APPLICATION_PATH . '/../library/vendors'),
    realpath(APPLICATION_PATH . '/../application/kernel'),
    get_include_path(),
)));

// this is a security measure, this can be checked by the included scripts and the script
// execution aborted if it is not set.
$GLOBALS['chisimba_entry_point_run'] = true;

// Create the application, bootstrap it, and run like hell!
$application = new \Engine();

$application->bootstrap()
            ->run();
