<?php

namespace Claromentis\ThankYou\Controllers;

use Claromentis\Core\Config\Exception\DialogException;
use Claromentis\Core\Http\RequestData;
use Claromentis\Core\Http\RequestDataTokenException;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\Exception\AccessDeniedException;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Psr\Http\Message\ServerRequestInterface;

class Admin
{
	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	public function __construct(Lmsg $lmsg, Api $api)
	{
		$this->api  = $api;
		$this->lmsg = $lmsg;
	}

	/**
	 * @param RequestData            $request_data
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return TemplaterCallResponse
	 * @throws RequestDataTokenException
	 * @throws AccessDeniedException
	 */
	public function Configuration(RequestData $request_data, ServerRequestInterface $request, SecurityContext $security_context)
	{
		if (!$this->api->ThankYous()->IsAdmin($security_context))
		{
			throw new AccessDeniedException();
		}

		$config_dialog = $this->api->Configuration()->GetConfigDialog();

		$error_message = false;

		$post = $request->getParsedBody();

		if (is_array($post) && count($post) > 0)
		{
			$request_data->CheckToken();

			try
			{
				$this->api->Configuration()->SaveConfigFromConfigDialogRequest($request);
				$user_message = ($this->lmsg)('common.configuration_saved');
			} catch (DialogException $dialog_exception)
			{
				$config_dialog->SetHighlights($dialog_exception->GetInvalidFields());
				$user_message  = $dialog_exception->getMessage();
				$error_message = true;
			}
		}

		$args = [
			'config_rows_cycle.datasrc' => $config_dialog->Show(),
			'nav_configuration.+class'  => 'active'
		];

		$response = new TemplaterCallResponse('thankyou/admin/configuration.html', $args, ($this->lmsg)('thankyou.app_name'));

		if (isset($user_message))
		{
			$response->SetMessage($user_message, $error_message);
		}

		return $response;
	}

	public function CoreValues()
	{
		$tags_enabled   = $this->api->Configuration()->IsTagsEnabled();
		$tags_mandatory = $this->api->Configuration()->IsTagsMandatory();

		$args = [
			'nav_tags.+class'                => 'active',
			'core_values_enabled.checked'    => $tags_enabled,
			'core_values_mandatory.checked'  => $tags_mandatory,
			'core_values_enabled.offtext'    => ($this->lmsg)('common.disabled'),
			'core_values_enabled.ontext'     => ($this->lmsg)('common.enabled'),
			'core_values_enabled.body'       => ($this->lmsg)('thankyou.admin.core_values.description'),
			'core_values_mandatory.body'     => ($this->lmsg)('thankyou.configuration.core_values_mandatory.description'),
			'core_values_enabled_body.style' => $tags_enabled ? '' : 'display:none;'
		];

		return new TemplaterCallResponse('thankyou/admin/core_values.html', $args, ($this->lmsg)('thankyou.app_name'));
	}
}
