<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Config\ConfigDialog;
use Claromentis\Core\Config\WritableConfig;
use Claromentis\ThankYou\Configuration\ConfigOptions;

// TODO: Move to Configuration directory
class Configuration
{
	/**
	 * @var ConfigOptions $config_options
	 */
	private $config_options;

	/**
	 * Configuration constructor.
	 *
	 * @param $config_options
	 */
	public function __construct(ConfigOptions $config_options)
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

		//TODO: Replace with Factory.
		return new ConfigDialog($options, $config);
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

	/**
	 * Determines whether Tags are enabled for Thank Yous.
	 *
	 * @param Config $config
	 * @return bool
	 */
	public function IsTagsEnabled(Config $config)
	{
		return (bool) $config->Get('thankyou_core_values_enabled');
	}

	/**
	 * Determines whether Tags are mandatory for Thank Yous.
	 *
	 * @param Config $config
	 * @return bool
	 */
	public function IsTagsMandatory(Config $config)
	{
		return $this->IsTagsEnabled($config) && (bool) $config->Get('thankyou_core_values_mandatory');
	}
}
