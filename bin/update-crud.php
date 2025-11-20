<?php

require_once __DIR__.'/../vendor/autoload.php';

use Mwb\LaminasGenerator\UnitGenerator;


$filepath = realpath(dirname(__FILE__, 2).'/vendor/mysql-workbench/mwb-dom/data/sakila_full.mwb');
$generator = new UnitGenerator($filepath);
$generator->generate(__DIR__.'/../tmp');


