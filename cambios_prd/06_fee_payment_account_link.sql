-- Vincular cuenta de pago "ColegioNotarialdeLima" (id=1) a todas las instancias fee
UPDATE mdl_enrol 
SET customint1 = 1 
WHERE enrol = 'fee' AND (customint1 IS NULL OR customint1 = 0);
