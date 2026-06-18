-- ============================================================
-- CAMBIO: Usuarios de prueba con roles en curso CEPLA (ID=2)
-- ENTORNO: PRD (adaptar IDs si difieren en producción)
-- FECHA: 2026-06-17
-- CONTRASEÑA: Password123!* (hash bcrypt generado por Moodle)
-- NOTA: En PRD ejecutar setup_users.php vía CLI en lugar de
--       este SQL, para que Moodle genere el hash correcto.
-- ============================================================

-- INSTRUCCIONES PARA PRD:
-- 1. Copiar setup_users.php al servidor de producción
-- 2. Ejecutar: php setup_users.php
-- 3. Verificar los 3 usuarios en Administración → Usuarios

-- Alternativamente, si se requiere SQL puro:
-- El hash de 'Password123!*' con cost=10 (Moodle bcrypt) es:
-- $2y$10$... (debe generarse en el servidor destino con hash_internal_user_password())

-- Enrolment manual en CEPLA (ID=2) - asegurar que exista instancia
INSERT IGNORE INTO mdl_enrol (enrol, status, courseid, sortorder, timecreated, timemodified)
SELECT 'manual', 0, 2, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM mdl_enrol WHERE courseid = 2 AND enrol = 'manual');
