<?php
if (!defined('INSTALL_PROGRESS'))
	die("This file cannot be executed directly");

if (!isset($installer))
	throw new Exception("Install options are not defined");
/** @var $installer Claromentis\Setup\SetupFacade */

/** @var $installer \Claromentis\Setup\SetupFacade */
if (method_exists($installer, 'GetPluginsRepository'))
{
	$installer->GetPluginsRepository()->Add("thankyou", 'Claromentis\ThankYou\Plugin');
} else
{
	$config_file = $installer->GetConfigEditor();
	$config_file->AddText('$cfg_cla_plugins[] = \'Claromentis\ThankYou\Plugin\';'.PHP_EOL);
}

