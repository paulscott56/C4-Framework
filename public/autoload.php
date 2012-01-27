<?php
require_once __DIR__ . '/../library/vendors/Symfony/Component/Classloader/UniversalClassLoader.php';

$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader(); // new ApcUniversalClassLoader('apc.prefix.');
$loader->useIncludePath(true);
$loader->register();

$loader->registerNamespaces(array(
    'Kernel'                => __DIR__ . "/../application/kernel/",
    'Modules'               => __DIR__ . "/../application/modules/",
    'Doctrine\Common'       => __DIR__ . "/../library/vendors/doctrine/common/lib",
    'Doctrine\DBAL'         => __DIR__ . "/../library/vendors/doctrine/dbal/lib",
    'Doctrine\ORM'          => __DIR__ . "/../library/vendors/doctrine/orm/lib",
    'Doctrine\ODM'          => __DIR__ . "/../library/vendors/doctrine/odm/lib",
    'DoctrineExtensions'    => __DIR__ . "/../library/vendors/doctrine/extensions/beberlei/lib",
    'Gedmo'                 => __DIR__ . "/../library/vendors/doctrine/extensions/gedmo/lib",
    'Bisna'                 => array(
                               __DIR__ . "/../library/vendors/bisna/doctrine/library", 
                               __DIR__ . "/../library/vendors/bisna/service/src/library"),
    'DMS'                   => __DIR__ . "/../library/vendors",
    'Symfony'               => __DIR__ . "/../library/vendors",
    'Imagine'               => __DIR__ . "/../library/vendors/imagine/lib",
    'Jackalope'             => __DIR__ . "/../library/vendors/jackalope/src",
    'PHPCR'                 => __DIR__ . "/../library/vendors/jackalope/lib/phpcr/src",
    'PHPCR\Util'            => __DIR__ . "/../library/vendors/jackalope/lib/phpcr-utils/src",
));

$loader->registerPrefixes(array(
    'Modules_'      => __DIR__ . "/../library/",
    'Bisna_'    => array(
                   __DIR__ . "/../library/vendors/bisna/doctrine/library",
                   __DIR__ . "/../library/vendors/bisna/service/src/library"),
));