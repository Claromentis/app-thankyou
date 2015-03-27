<?php
/** @var $migrations \Claromentis\Setup\SetupFacade */

$config_file = $migrations->GetConfigEditor();
$config_file->AddText('$cfg_cla_plugins[] = "\\\\Claromentis\\\\ThankYou\\\\Plugin";'."\r\n");

