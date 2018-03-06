<?php
$_db_migration_to = '04.02'; // 3.2.0
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



//===========================================================================================
$migrations->SetVersion('04.02');
