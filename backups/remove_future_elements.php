<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB, $CFG;

echo "=== ELIMINANDO ACTIVIDADES DE CAPACITACIÓN FUTURAS (2025/2026) ===\n";

$cmids_to_delete = [1005, 1011]; // CMIDs of the two 2025/2026 Page resources in Course 1

foreach ($cmids_to_delete as $cmid) {
    $cm = $DB->get_record('course_modules', ['id' => $cmid]);
    if ($cm) {
        $mod = $DB->get_record('modules', ['id' => $cm->module]);
        $instance = $DB->get_record($mod->name, ['id' => $cm->instance]);
        $name = $instance ? $instance->name : 'N/A';
        
        echo "Eliminando recurso: [CMID $cmid] '$name'...\n";
        course_delete_module($cmid);
        echo "✓ Eliminado con éxito.\n";
    } else {
        echo "Recurso con CMID $cmid ya no existe o ya fue eliminado.\n";
    }
}

echo "\n=== ELIMINANDO CATEGORÍAS ACADÉMICAS FUTURAS (2025/2026) ===\n";

// Categories 32 (EVENTOS ACADÉMICOS 2025) and 34 (EVENTOS ACADEMICOS 2026)
$category_ids = [32, 34];

foreach ($category_ids as $catid) {
    try {
        if ($category = core_course_category::get($catid, IGNORE_MISSING)) {
            echo "Encontrada categoría: [ID $catid] '{$category->name}'\n";
            
            // First, find and delete any courses inside this category or its subcategories
            $children_ids = $category->get_all_children_ids();
            $all_cat_ids = array_merge([$catid], $children_ids);
            
            list($insql, $params) = $DB->get_in_or_equal($all_cat_ids);
            $courses = $DB->get_records_select('course', "category $insql", $params);
            
            foreach ($courses as $c) {
                echo "  -> Eliminando curso asociado: [ID {$c->id}] '{$c->fullname}'...\n";
                delete_course($c->id, false);
                echo "  ✓ Curso eliminado.\n";
            }
            
            // Now delete the category (this deletes subcategories too)
            echo "  -> Eliminando categoría y sus subcategorías...\n";
            $category->delete_full(false);
            echo "✓ Categoría [ID $catid] eliminada con éxito.\n";
        } else {
            echo "Categoría con ID $catid ya no existe.\n";
        }
    } catch (Exception $e) {
        echo "Error al eliminar categoría [ID $catid]: " . $e->getMessage() . "\n";
    }
}

echo "\n=== PROCESO COMPLETADO ===\n";
