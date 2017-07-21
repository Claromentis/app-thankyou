<?php
$_db_migration_to = '02.04'; // 3.0.3
if (!isset($migrations) || !is_object($migrations))
	die("This file cannot be executed directly");
$migrations->CheckValid($_db_migration_to);
//===========================================================================================



//===========================================================================================
$migrations->SetVersion('02.04');
