-- ============================================================
-- CAMBIO: Insertar categoría raíz ID=1 (requerida por Moodle)
-- ENTORNO: PRD
-- FECHA: 2026-06-17
-- ============================================================
INSERT IGNORE INTO mdl_course_categories
    (id, name, idnumber, description, descriptionformat, parent,
     sortorder, coursecount, visible, visibleold, timemodified, depth, path, theme)
VALUES
    (1, 'Miscelanea', '', '', 0, 0, 10000, 0, 1, 1, UNIX_TIMESTAMP(), 1, '/1', '');
