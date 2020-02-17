<?php

namespace Claromentis\ThankYou\Controllers;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Http\RedirectResponse;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ThankYouController
{
	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	public function __construct(Lmsg $lmsg, Api $api, ?LoggerInterface $logger = null)
	{
		$this->api    = $api;
		$this->lmsg   = $lmsg;
		$this->logger = $logger;
	}

	public function View(ServerRequestInterface $request, SecurityContext $context)
	{
		$id = $request->getAttribute('id');

		$args = [];

		if (!isset($id))
		{
			//TODO: Replace pagination with better tool once it exists.
			require_once('paging.php');

			$limit = 20;

			$query_params = $request->getQueryParams();
			$offset       = (int) ($query_params['st'] ?? null);

			$args['paging.body_html'] = get_navigation(
				$request->getUri()->getPath(),
				$this->api->ThankYous()->GetTotalThankYousCount($this->api->ThankYous()->GetVisibleExtranetIds($context)),
				$offset,
				'',
				$limit
			);

			$args['ty_list.limit']  = $limit;
			$args['ty_list.offset'] = $offset;

			$tags_enabled      = $this->api->Configuration()->IsTagsEnabled();
			$active_tags_count = $this->api->Tag()->GetTotalTags(true);

			if ($tags_enabled && $active_tags_count > 0)
			{
				$args['thankyou_list_container.+class'] = "col-sm-9 col-md-pull-3";
			} else
			{
				$args['thankyou_list_container.+class']       = "col-sm-12";
				$args['thankyou_tag_stats_container.visible'] = false;
			}

			return new TemplaterCallResponse('thankyou/view.html', $args, ($this->lmsg)('thankyou.app_name'));
		}

		try
		{
			$args['thank.thank_you'] = $this->api->ThankYous()->GetThankYou($id, true, true, true);
		} catch (ThankYouNotFoundException $exception)
		{
			return RedirectResponse::httpRedirect('/thankyou/thanks', ($this->lmsg)('thankyou.error.thanks_not_found'), true);
		} catch (MappingException $exception)
		{
			if (isset($this->logger))
			{
				$this->logger->error("Unexpected Mapping Exception when viewing a Thank You Note", [$exception]);
			}

			return RedirectResponse::httpRedirect('/thankyou/thanks', '', true);
		}

		return new TemplaterCallResponse('thankyou/thank_you.html', $args, ($this->lmsg)('thankyou.app_name'));
	}
}
