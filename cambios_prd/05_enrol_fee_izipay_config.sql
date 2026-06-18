-- Habilitar el plugin enrol_fee en el listado de plugins de matrícula habilitados
UPDATE mdl_config 
SET value = 'manual,guest,self,cohort,paypal,fee' 
WHERE name = 'enrol_plugins_enabled';

-- Configurar costo y moneda predeterminados para enrol_fee
INSERT INTO mdl_config_plugins (plugin, name, value) 
VALUES ('enrol_fee', 'cost', '10.00') 
ON DUPLICATE KEY UPDATE value = '10.00';

INSERT INTO mdl_config_plugins (plugin, name, value) 
VALUES ('enrol_fee', 'currency', 'PEN') 
ON DUPLICATE KEY UPDATE value = 'PEN';

INSERT INTO mdl_config_plugins (plugin, name, value) 
VALUES ('enrol_fee', 'status', '0') 
ON DUPLICATE KEY UPDATE value = '0';

-- Agregar método de matrícula fee a todos los cursos que aún no lo tengan
INSERT INTO mdl_enrol (enrol, status, courseid, sortorder, cost, currency, roleid, timecreated, timemodified)
SELECT 'fee', 0, c.id, 0, '10.00', 'PEN', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM mdl_course c
WHERE c.id > 1 AND NOT EXISTS (
    SELECT 1 FROM mdl_enrol e WHERE e.courseid = c.id AND e.enrol = 'fee'
);
