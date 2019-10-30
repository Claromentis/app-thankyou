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
		$options = $this->config_options->GetOptions();

		foreach ($options as $config_name => $config_options)
		{
			$display = (bool) ($config_options['display'] ?? true);
			if ($display === false)
			{
				unset($options[$config_name]);
			}
		}

		return new ConfigDialog($options, $config); //TODO: Replace with Factory.
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
