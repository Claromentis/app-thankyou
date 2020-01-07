<?php

namespace Claromentis\ThankYou\Configuration;

use Claromentis\Core\Config\ConfigDialog;
use Claromentis\Core\Config\Exception\DialogException;
use Claromentis\Core\Config\WritableConfig;
use Psr\Http\Message\ServerRequestInterface;

class Api
{
	/**
	 * @var WritableConfig $config
	 */
	private $config;

	/**
	 * @var ConfigOptions $config_options
	 */
	private $config_options;

	/**
	 * Configuration constructor.
	 *
	 * @param ConfigOptions  $config_options
	 * @param WritableConfig $config
	 */
	public function __construct(ConfigOptions $config_options, WritableConfig $config)
	{
		$this->config_options = $config_options;
		$this->config         = $config;
	}

	/**
	 * @return ConfigDialog
	 */
	public function GetConfigDialog(): ConfigDialog
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
		return new ConfigDialog($options, $this->config);
	}

	/**
	 * Returns an array of Configuration Options, as understood by a ConfigDialog.
	 *
	 * @return array[]
	 */
	public function GetConfigOptions()
	{
		return $this->config_options->GetOptions();
	}

	/**
	 * Updates one of the Config's Options with the given value.
	 *
	 * @param string $config_name
	 * @param        $value
	 */
	public function SetConfigValue(string $config_name, $value)
	{
		$this->config->Set($config_name, $value);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @throws DialogException
	 */
	public function SaveConfigFromConfigDialogRequest(ServerRequestInterface $request)
	{
		$config_dialog = $this->GetConfigDialog();
		$config_dialog->Update($request);
		$this->SaveConfig();
	}

	/**
	 * Saves a Writable Config to the Application.
	 */
	public function SaveConfig()
	{
		$this->config->Save();
	}

	/**
	 * Determines whether Comments are enabled for Thank Yous.
	 *
	 * @return bool
	 */
	public function IsCommentsEnabled()
	{
		return (bool) $this->config->Get('thank_you_comments');
	}

	/**
	 * Determines whether Line Manager Notifications are enabled for Thank Yous.
	 *
	 * @return bool
	 */
	public function IsLineManagerNotificationEnabled()
	{
		return (bool) $this->config->Get('notify_line_manager');
	}

	/**
	 * Determines whether Tags are enabled for Thank Yous.
	 *
	 * @return bool
	 */
	public function IsTagsEnabled()
	{
		return (bool) $this->config->Get('thankyou_core_values_enabled');
	}

	/**
	 * Determines whether Tags are mandatory for Thank Yous.
	 *
	 * @return bool
	 */
	public function IsTagsMandatory()
	{
		return $this->IsTagsEnabled() && (bool) $this->config->Get('thankyou_core_values_mandatory');
	}
}
