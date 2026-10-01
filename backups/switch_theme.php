<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

// Cambiar tema a moove
set_config('theme', 'moove');
echo "✓ Tema cambiado a: moove\n";

// Configuraciones recomendadas para moove
set_config('theme_moove_shareurl', '');
echo "✓ Configuración básica de moove aplicada\n";

echo "\n✅ Listo. Purga la caché para ver el nuevo tema.\n";
