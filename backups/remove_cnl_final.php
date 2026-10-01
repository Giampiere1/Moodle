<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
global $DB;

// Limpiar cronremotepassword que era "cnl2020"
set_config('cronremotepassword', '');
echo "✓ cronremotepassword limpiado\n";

// Limpiar el script JS del body que menciona "CNL"
$html = get_config('core', 'additionalhtmltopofbody');
if ($html && stripos($html, 'cnl') !== false) {
    $html = str_ireplace(
        '"Función deshabilitada por CNL"',
        '"Función deshabilitada"',
        $html
    );
    set_config('additionalhtmltopofbody', $html);
    echo "✓ Mensaje CNL en JS del body limpiado\n";
}

// Limpiar siteidentifier que tiene "cnl" en el valor
$siteident = get_config('core', 'siteidentifier');
if ($siteident && stripos($siteident, 'cnl') !== false) {
    $new = str_ireplace('cnl', '', $siteident);
    set_config('siteidentifier', $new);
    echo "✓ siteidentifier limpiado\n";
}

// Limpiar contact name del hub
set_config('site_contactname', '', 'hub');
echo "✓ hub contact name limpiado\n";

echo "\n✅ Limpieza final completada.\n";
