<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_cnl_certificate';
$plugin->version   = 2026061600;
$plugin->requires  = 2021051700;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';
$plugin->dependencies = array(
    'local_cnl_certificates' => 2026061600
);
?>
