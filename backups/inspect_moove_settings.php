<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

echo "=== CONFIG PLUGINS FOR THEME MOOVE ===\n";
$settings = $DB->get_records('config_plugins', ['plugin' => 'theme_moove']);
foreach ($settings as $s) {
    echo "Name: {$s->name} | Value: " . substr(strip_tags($s->value), 0, 100) . "\n";
}

echo "\n=== ALL CONFIG PLUGINS CONTAINS COLEGIO OR NOTARIOS ===\n";
$all_settings = $DB->get_records_select('config_plugins', "value LIKE '%Colegio%' OR value LIKE '%Notarios%'");
foreach ($all_settings as $s) {
    echo "Plugin: {$s->plugin} | Name: {$s->name} | Value: " . strip_tags($s->value) . "\n";
}
