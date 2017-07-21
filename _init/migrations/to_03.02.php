<?php
$_db_migration_to = '03.02'; // 3.1.1
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



//===========================================================================================
$migrations->SetVersion('03.02');
