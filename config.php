<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost = '66.116.209.78';
$CFG->dbname = 'leuroa19_moodle';
$CFG->dbuser = 'leuroa19_leuroa19';
$CFG->dbpass = 'Giampiere1234';
$CFG->prefix = 'mdl_';
$CFG->dboptions = array(
  'dbpersist' => 0,
  'dbport' => '3306',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_general_ci',
);

$CFG->wwwroot = 'http://localhost/SalaVirtual';
$CFG->dataroot = 'C:\\wamp64\\www\\moodledata';
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