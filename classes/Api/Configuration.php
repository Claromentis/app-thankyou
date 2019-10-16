<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Config\ConfigDialog;
use Claromentis\Core\Config\WritableConfig;

class Configuration
{
	private $config_options;

	/**
	 * Configuration constructor.
	 *
	 * @param $config_options
	 */
	public function __construct(\Claromentis\ThankYou\Configuration\Configuration $config_options)
	{
		$this->config_options = $config_options;
	}

	/**
	 * @param WritableConfig $config
	 * @return ConfigDialog
	 */
	public function GetConfigDialog(WritableConfig $config): ConfigDialog
	{
		return new ConfigDialog($this->config_options->GetOptions(), $config); //TODO: Replace with Factory.
	}

	/**
	 * Saves a Writable Config to the Application.
	 * Although this method is slightly superfluous at present, it follows practises allowing for the objects nature to change later.
	 *
	 * @param WritableConfig $config
	 */
	public function SaveConfig(WritableConfig $config)
	{
		$config->Save();
	}
}
