<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
global $DB;

$old = 'Colegio de Notarios de Lima';
$new = 'LEUROLATAM';

echo "=== REEMPLAZANDO '{$old}' → '{$new}' EN BD ===\n\n";
$total = 0;

// mdl_config
$rows = $DB->get_records_select('config', "value LIKE ?", ["%{$old}%"]);
foreach ($rows as $r) {
    $updated = str_replace($old, $new, $r->value);
    $DB->set_field('config', 'value', $updated, ['id' => $r->id]);
    echo "  [config] {$r->name}: actualizado\n";
    $total++;
}

// mdl_config_plugins
$rows = $DB->get_records_select('config_plugins', "value LIKE ?", ["%{$old}%"]);
foreach ($rows as $r) {
    $updated = str_replace($old, $new, $r->value);
    $DB->set_field('config_plugins', 'value', $updated, ['id' => $r->id]);
    echo "  [config_plugins] {$r->plugin}/{$r->name}: actualizado\n";
    $total++;
}

// mdl_course (fullname, shortname, summary)
foreach (['fullname','summary'] as $col) {
    $DB->execute("UPDATE {course} SET {$col} = REPLACE({$col}, ?, ?) WHERE {$col} LIKE ?",
        [$old, $new, "%{$old}%"]);
}

// mdl_course_categories
$DB->execute("UPDATE {course_categories} SET name = REPLACE(name, ?, ?) WHERE name LIKE ?",
    [$old, $new, "%{$old}%"]);
$DB->execute("UPDATE {course_categories} SET description = REPLACE(description, ?, ?) WHERE description LIKE ?",
    [$old, $new, "%{$old}%"]);

// Nombre del sitio y shortname del sitio
set_config('fullname', $new);
set_config('shortname', 'LEUROLATAM');
echo "  [config] fullname → {$new}\n";
echo "  [config] shortname → LEUROLATAM\n";
$total += 2;

echo "\nTotal de registros actualizados: {$total}\n";
echo "\n✅ Reemplazo en BD completado.\n";
