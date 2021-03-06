<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'root';
$db['default']['password'] = 'lemoon8888';
$db['default']['database'] = 'auther';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;


$db['erge']['hostname'] = 'localhost';
$db['erge']['username'] = 'root';
$db['erge']['password'] = 'lemoon8888';
$db['erge']['database'] = 'story_data_collect';
$db['erge']['dbdriver'] = 'mysql';
$db['erge']['dbprefix'] = '';
$db['erge']['pconnect'] = FALSE;
$db['erge']['db_debug'] = TRUE;
$db['erge']['cache_on'] = FALSE;
$db['erge']['cachedir'] = '';
$db['erge']['char_set'] = 'utf8';
$db['erge']['dbcollat'] = 'utf8_general_ci';
$db['erge']['swap_pre'] = '';
$db['erge']['autoinit'] = TRUE;
$db['erge']['stricton'] = FALSE;

$db['erge2']['hostname'] = 'localhost';
$db['erge2']['username'] = 'root';
$db['erge2']['password'] = 'lemoon8888';
$db['erge2']['database'] = 'story_data';
$db['erge2']['dbdriver'] = 'mysql';
$db['erge2']['dbprefix'] = '';
$db['erge2']['pconnect'] = FALSE;
$db['erge2']['db_debug'] = TRUE;
$db['erge2']['cache_on'] = FALSE;
$db['erge2']['cachedir'] = '';
$db['erge2']['char_set'] = 'utf8';
$db['erge2']['dbcollat'] = 'utf8_general_ci';
$db['erge2']['swap_pre'] = '';
$db['erge2']['autoinit'] = TRUE;
$db['erge2']['stricton'] = FALSE;


$db['prj_mmh']['hostname'] = 'localhost';
$db['prj_mmh']['username'] = 'root';
$db['prj_mmh']['password'] = 'lemoon8888';
$db['prj_mmh']['database'] = 'prj_mmh';
$db['prj_mmh']['dbdriver'] = 'mysql';
$db['prj_mmh']['dbprefix'] = '';
$db['prj_mmh']['pconnect'] = FALSE;
$db['prj_mmh']['db_debug'] = TRUE;
$db['prj_mmh']['cache_on'] = FALSE;
$db['prj_mmh']['cachedir'] = '';
$db['prj_mmh']['char_set'] = 'utf8';
$db['prj_mmh']['dbcollat'] = 'utf8_general_ci';
$db['prj_mmh']['swap_pre'] = '';
$db['prj_mmh']['autoinit'] = TRUE;
$db['prj_mmh']['stricton'] = FALSE;

/* End of file database.php */
/* Location: ./application/config/database.php */
