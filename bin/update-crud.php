<?php

require_once __DIR__.'/../vendor/autoload.php';

use Mwb\LaminasGenerator\UnitGenerator;

$current_working_directory = getcwd();
$generator = new UnitGenerator($current_working_directory . '/data/sakila_full.mwb');
$generator->generate($current_working_directory . '/tmp', True);

/*
$filepath = realpath(dirname(__FILE__, 2).'/vendor/mysql-workbench/mwb-dom/data/sakila_full.mwb');
$generator = new UnitGenerator($filepath);
$generator->generate(__DIR__.'/../tmp', True);
*/

