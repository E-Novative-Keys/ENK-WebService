<?php

// Configuration
define('DEBUG', 		true);
define('PROD', 			false);

// Security
define('AUTH_ENABLE', 	true);
define('SALT',   		'salt');
define('PEPPER', 		'pepper');
define('JAVA_SALT', 	'java_salt');
define('JAVA_PEPPER', 	'java_pepper');

// Global
define('WEBROOT', 		dirname(__FILE__));
define('ROOT', 			dirname(WEBROOT));
define('DS', 			DIRECTORY_SEPARATOR);
define('BASE_URL', 		dirname(dirname($_SERVER['SCRIPT_NAME'])) == '/' ? '' : dirname(dirname($_SERVER['SCRIPT_NAME'])));

define('EMAIL_ADDR', 	'email');
define('EMAIL_PWD', 	'email_password');

define('CORE', 			ROOT.DS.'Core'.DS);
define('CONTROLLERS', 	ROOT.DS.'Controllers'.DS);
define('VIEWS', 		ROOT.DS.'Views'.DS);
define('ELEMENTS', 		VIEWS.'Elements'.DS);
define('MODELS', 		ROOT.DS.'Models'.DS);

define('CSS', 			BASE_URL.DS.'css'.DS);
define('JS', 			BASE_URL.DS.'js'.DS);
define('IMG', 			BASE_URL.DS.'img'.DS);
define('FONTS', 		BASE_URL.DS.'fonts'.DS);
define('FILES', 		BASE_URL.DS.'files'.DS);

define('CSS_ROOT', 		ROOT.DS.'webroot'.DS.'css'.DS);
define('JS_ROOT', 		ROOT.DS.'webroot'.DS.'js'.DS);
define('IMG_ROOT', 		ROOT.DS.'webroot'.DS.'img'.DS);
define('FONTS_ROOT', 	ROOT.DS.'webroot'.DS.'fonts'.DS);
define('FILES_ROOT', 	ROOT.DS.'webroot'.DS.'files'.DS);

//define('INI_FILES', 	DS.'Users'.DS.'Worker'.DS.'Desktop'.DS.'ini'.DS);
define('INI_FILES', 	'/var/www/ini/');

// Database
class DBConfig
{
	public static $databases = array(
		'dev' => array(
			'dbtype'	=> 'mysql',
			'host'		=> 'localhost',
			'database'	=> 'enkwebservice',
			'login'		=> 'login',
			'password'	=> 'password',
			'prefix'	=>	'',
			'encoding'	=>	'utf8'
		),
		'prod' => array(
			'dbtype'	=> 'mysql',
			'host'		=> 'localhost',
			'database'	=> 'enkwebservice',
			'login'		=> 'login',
			'password'	=> 'password',
			'prefix'	=>	'',
			'encoding'	=>	'utf8'
		)
	);
}

?>