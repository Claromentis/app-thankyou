<?php
if (!defined('INSTALL_PROGRESS'))
	die("This file cannot be executed directly");

if (!isset($installer))
	throw new Exception("Install options are not defined");
/** @var $installer Claromentis\Setup\SetupFacade */

$installer->GetPluginsRepository()->Remove("thankyou", 'Claromentis\ThankYou\Plugin');
