<?php

namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Http\RedirectResponse;
use Claromentis\Core\Http\RequestData;
use Claromentis\Core\Http\RequestDataTokenException;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\UseCase\ThankYou;
use Psr\Http\Message\ServerRequestInterface;

class ThanksController
{
	private $config;

	private $lmsg;

	private $thank_you_admin_panel;

	private $use_case;

	public function __construct(Lmsg $lmsg, ThankYou $use_case, Config $config, AdminPanel $thank_you_admin_panel)
	{
		$this->config                = $config;
		$this->lmsg                  = $lmsg;
		$this->thank_you_admin_panel = $thank_you_admin_panel;
		$this->use_case              = $use_case;
	}

	/**
	 * @param RequestData            $request_data
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return RedirectResponse
	 * @throws RequestDataTokenException
	 */
	public function CreateOrUpdate(RequestData $request_data, ServerRequestInterface $request, SecurityContext $security_context)
	{
		$request_data->CheckToken();
		$post     = $request->getParsedBody();
		$redirect = $request->getServerParams()['HTTP_REFERER'];

		$id          = (int) ($post['thank_you_id'] ?? null);
		$users_ids   = (array) $post['thank_you_user'] ?? null;
		$description = (string) $post['thank_you_description'] ?? '';

		try
		{
			if ($id === 0)
			{
				$notify_line_manager = false;
				if ($this->config->Get('notify_line_manager'))
				{
					$notify_line_manager = true;
				}

				$this->use_case->Create($users_ids, $description, $notify_line_manager);
			} else
			{
				try
				{
					$this->use_case->Update($security_context, $id, $users_ids, $description, $this->thank_you_admin_panel);
				} catch (ThankYouNotFound $thank_you_not_found)
				{
					return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
				} catch (ThankYouForbidden $thank_you_forbidden)
				{
					return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
				}
			}
		} catch (ThankYouInvalidUsers $thank_you_invalid_users)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.invalid_users'), true);
		}

		return RedirectResponse::httpRedirect($redirect);
	}

	/**
	 * @param RequestData            $request_data
	 * @param SecurityContext        $security_context
	 * @param ServerRequestInterface $request
	 * @return RedirectResponse
	 * @throws RequestDataTokenException
	 */
	public function Delete(RequestData $request_data, SecurityContext $security_context, ServerRequestInterface $request)
	{
		$request_data->CheckToken();
		$post     = $request->getParsedBody();
		$id       = (int) ($post['thank_you_id'] ?? null);
		$redirect = $request->getServerParams()['HTTP_REFERER'];

		try
		{
			$this->use_case->Delete($security_context, $id, $this->thank_you_admin_panel);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
		} catch (ThankYouForbidden $thank_you_forbidden)
		{
			return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
		}

		return RedirectResponse::httpRedirect($redirect, ($this->lmsg)('thankyou.common.thanks_deleted'), false);
	}
}
