<?php
if (!defined('INSTALL_PROGRESS'))
	die("This file cannot be executed directly");

if (!isset($installer))
	throw new Exception("Install options are not defined");
/** @var $installer Claromentis\Setup\SetupFacade */

$config_file = $installer->GetConfigEditor();
$config_file->AddText('$cfg_cla_plugins[] = "\\\\Claromentis\\\\ThankYou\\\\Plugin";'."\r\n");

