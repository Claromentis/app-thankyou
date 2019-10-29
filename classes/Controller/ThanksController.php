<?php

namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Config\Exception\DialogException;
use Claromentis\Core\Config\WritableConfig;
use Claromentis\Core\Http\RedirectResponse;
use Claromentis\Core\Http\RequestData;
use Claromentis\Core\Http\RequestDataTokenException;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\Exception\AccessDeniedException;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreRepository;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Psr\Http\Message\ServerRequestInterface;

class ThanksController
{
	const THANKS_HOMEPAGE = '/thankyou/thanks';

	private $api;

	private $config;

	private $lmsg;

	private $sugre_repository;

	public function __construct(Lmsg $lmsg, Api $api, SugreRepository $sugre_repository, WritableConfig $config)
	{
		$this->api              = $api;
		$this->config           = $config;
		$this->lmsg             = $lmsg;
		$this->sugre_repository = $sugre_repository;
	}

	public function Admin(ServerRequestInterface $server_request)
	{
		$args  = [];
		$limit = 20;

		$query_params = $server_request->getQueryParams();
		$offset       = (int) ($query_params['st'] ?? null);

		$args['nav_messages.+class'] = 'active';

		require_once('paging.php'); //TODO: not this...

		$args['paging.body_html'] = get_navigation($server_request->getUri()->getPath(), $this->api->ThankYous()->GetTotalThankYousCount(), $offset, '', $limit);

		$args['ty_list.limit']  = $limit;
		$args['ty_list.offset'] = $offset;

		return new TemplaterCallResponse('thankyou/admin/admin.html', $args, ($this->lmsg)('thankyou.app_name'));
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

		$config_dialog = $this->api->Configuration()->GetConfigDialog($this->config);

		$error_message = false;

		$post = $request->getParsedBody();

		if (is_array($post) && count($post) > 0)
		{
			$request_data->CheckToken();

			try
			{
				$config_dialog->Update($request);

				$this->api->Configuration()->SaveConfig($this->config);
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
		$args = [];

		return new TemplaterCallResponse('thankyou/admin/core_values.html', $args, ($this->lmsg)('thankyou.app_name'));
	}

	/**
	 * Create a new Thank You, or update an existing Thank You.
	 *
	 * @param RequestData            $request_data
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return RedirectResponse
	 * @throws
	 */
	public function CreateOrUpdate(RequestData $request_data, ServerRequestInterface $request, SecurityContext $security_context)
	{
		$request_data->CheckToken();
		$post = $request->getParsedBody();

		$id          = (int) ($post['thank_you_id'] ?? null);
		$thanked     = (array) ($post['thank_you_user'] ?? null);
		$description = (string) $post['thank_you_description'] ?? '';

		if (isset($thanked))
		{
			$thanked = $this->sugre_repository->DecodeOutput($thanked);
		}

		try
		{
			if ($id === 0)
			{
				$id = $this->api->ThankYous()->CreateAndSave($security_context->GetUser(), $thanked, $description);
			} else
			{
				try
				{
					$this->api->ThankYous()->UpdateAndSave($security_context, $id, $thanked, $description);
				} catch (ThankYouNotFound $thank_you_not_found)
				{
					return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
				} catch (ThankYouForbidden $thank_you_forbidden)
				{
					return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
				}
			}
		} catch (ThankYouInvalidUsers $thank_you_invalid_users)
		{
			return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.error.invalid_users'), true);
		}

		return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE . '/' . $id);
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
		$post = $request->getParsedBody();
		$id   = (int) ($post['thank_you_id'] ?? null);

		try
		{
			$this->api->ThankYous()->Delete($security_context, $id);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.error.thanks_not_found'), true);
		} catch (ThankYouForbidden $thank_you_forbidden)
		{
			return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.error.no_edit_permission'), true);
		}

		return RedirectResponse::httpRedirect(self::THANKS_HOMEPAGE, ($this->lmsg)('thankyou.common.thanks_deleted'), false);
	}

	public function View(ServerRequestInterface $request)
	{
		$id = $request->getAttribute('id');

		$args = [];

		if (!isset($id))
		{
			return new TemplaterCallResponse('thankyou/view.html', $args, ($this->lmsg)('thankyou.app_name'));
		}

		$args['thank.thank_you'] = $id;

		return new TemplaterCallResponse('thankyou/thank.html', $args, ($this->lmsg)('thankyou.app_name'));
	}
}
