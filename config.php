<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost = '127.0.0.1';
$CFG->dbname = 'moodle';
$CFG->dbuser = 'root';
$CFG->dbpass = '';
$CFG->prefix = 'mdl_';
$CFG->dboptions = array(
  'dbpersist' => 0,
  'dbport' => '3307',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_general_ci',
);

$CFG->wwwroot = 'http://localhost/SalaVirtualCNL';
$CFG->dataroot = 'C:\\wamp64\\www\\moodledatacnl';
$CFG->admin = 'admin';

$CFG->directorypermissions = 0777;

@ini_set('display_errors', '0');
$CFG->debug = 0;
$CFG->debugdisplay = 0;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!


/*
| Dominio: graneromaracaibo.com
| Panel de control: graneromaracaibo.com/cpanel
| Usuario: graneromaracaibo
| Contraseña: s)Rlf)dwU8SE
 */