-- ============================================================
-- CAMBIO: Activar formato Tiles (Mosaicos) en todos los cursos
-- ENTORNO: PRD
-- FECHA: 2026-06-17
-- DESCRIPCIÓN: Cambia cursos con formato 'topics' y 'weeks'
--              al formato visual interactivo 'tiles'
-- ============================================================

-- Cambiar formato de cursos topics → tiles
UPDATE mdl_course
SET format = 'tiles'
WHERE format IN ('topics', 'weeks')
  AND id != 1; -- excluir sitio principal (Moodle site course)

-- Eliminar opciones duplicadas de formato anterior antes de actualizar
-- (evita error de clave única al cambiar formato)
DELETE cfo1 FROM mdl_course_format_options cfo1
INNER JOIN mdl_course_format_options cfo2
  ON cfo1.courseid = cfo2.courseid
  AND cfo1.name = cfo2.name
  AND cfo1.sectionid = cfo2.sectionid
  AND cfo1.id > cfo2.id
WHERE cfo1.format IN ('topics','weeks');

-- Actualizar el formato en course_format_options
UPDATE mdl_course_format_options
SET format = 'tiles'
WHERE format IN ('topics', 'weeks');
