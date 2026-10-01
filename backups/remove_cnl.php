<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/moodlelib.php');
global $DB, $CFG;

echo "=== BUSCANDO Y ELIMINANDO RASTROS DE CNL ===\n\n";

// 1. Configuraciones del sitio (mdl_config)
$configs_to_check = $DB->get_records_select('config', "value LIKE '%cnl%' OR value LIKE '%CNL%' OR value LIKE '%Colegio de Notarios%'");
echo "--- mdl_config con 'cnl' o 'Colegio de Notarios' ---\n";
foreach ($configs_to_check as $c) {
    echo "  [{$c->name}] = {$c->value}\n";
}

// 2. Nombre/shortname del sitio
$sitename  = get_config('core', 'fullname');
$siteshort = get_config('core', 'shortname');
echo "\nNombre del sitio: {$sitename}\n";
echo "Shortname: {$siteshort}\n";

// Limpiar nombre si tiene CNL
if (stripos($sitename, 'cnl') !== false || stripos($sitename, 'Notarios') !== false) {
    set_config('fullname', 'Sala Virtual');
    echo "✓ fullname limpiado -> 'Sala Virtual'\n";
}
if (stripos($siteshort, 'cnl') !== false || stripos($siteshort, 'Notarios') !== false) {
    set_config('shortname', 'SalaVirtual');
    echo "✓ shortname limpiado -> 'SalaVirtual'\n";
}

// 3. Emails de usuarios con cnl
$users_cnl = $DB->get_records_select('user', "email LIKE '%cnl%'");
echo "\n--- Usuarios con email @cnl ---\n";
foreach ($users_cnl as $u) {
    $newemail = str_ireplace(['cnl.test', 'cnl'], ['sala.test', ''], $u->email);
    $newemail = trim($newemail, '.');
    $DB->set_field('user', 'email', $newemail, ['id' => $u->id]);
    echo "  {$u->username}: {$u->email} → {$newemail}\n";
}

// 4. Limpiar campo 'supportemail' y 'noreplyaddress'
$supportemail = get_config('core', 'supportemail');
if ($supportemail && stripos($supportemail, 'cnl') !== false) {
    set_config('supportemail', 'admin@sala.test');
    echo "\n✓ supportemail limpiado\n";
}
$noreply = get_config('core', 'noreplyaddress');
if ($noreply && stripos($noreply, 'cnl') !== false) {
    set_config('noreplyaddress', 'noreply@sala.test');
    echo "✓ noreplyaddress limpiado\n";
}

// 5. Buscar en mdl_config_plugins también
$plugin_configs = $DB->get_records_select('config_plugins', "value LIKE '%cnl%' OR value LIKE '%CNL%'");
echo "\n--- mdl_config_plugins con 'cnl' ---\n";
foreach ($plugin_configs as $c) {
    $newval = str_ireplace(['cnl.test', 'cnl', 'CNL'], ['sala.test', '', ''], $c->value);
    $DB->set_field('config_plugins', 'value', $newval, ['id' => $c->id]);
    echo "  plugin={$c->plugin} [{$c->name}]: {$c->value} → {$newval}\n";
}

// 6. Curso de prueba - quitar si tiene CNL en algún campo
$DB->execute("UPDATE {course} SET fullname = REPLACE(fullname, 'CNL', ''), shortname = REPLACE(shortname, 'CNL', '') WHERE fullname LIKE '%CNL%' OR shortname LIKE '%CNL%'");

echo "\n✅ Limpieza de CNL en BD completada.\n";
