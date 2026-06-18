-- ============================================================
-- CAMBIO: Activar tema Moove como tema principal del sitio
-- ENTORNO: PRD
-- FECHA: 2026-06-17
-- DESCRIPCIÓN: Cambia el tema de 'academi' a 'moove'
--              (limpio, moderno, responsivo y corporativo)
-- ============================================================

-- Tema del sitio
UPDATE mdl_config SET value = 'moove' WHERE name = 'theme';

-- En caso no exista el registro, lo inserta
INSERT INTO mdl_config (name, value)
SELECT 'theme', 'moove'
WHERE NOT EXISTS (SELECT 1 FROM mdl_config WHERE name = 'theme');

-- Incrementar themerev para forzar recarga de assets
UPDATE mdl_config SET value = UNIX_TIMESTAMP() WHERE name = 'themerev';
