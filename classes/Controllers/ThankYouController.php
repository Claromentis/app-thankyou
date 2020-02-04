<?php

namespace Claromentis\ThankYou\Controllers;

use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Psr\Http\Message\ServerRequestInterface;

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

	public function __construct(Lmsg $lmsg, Api $api)
	{
		$this->api  = $api;
		$this->lmsg = $lmsg;
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

			$args['paging.body_html'] = get_navigation($request->getUri()->getPath(), $this->api->ThankYous()->GetTotalThankYousCount($context), $offset, '', $limit);

			$args['ty_list.limit']  = $limit;
			$args['ty_list.offset'] = $offset;

			$tags_enabled = $this->api->Configuration()->IsTagsEnabled();
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

		$args['thank.thank_you'] = $id;

		return new TemplaterCallResponse('thankyou/thank_you.html', $args, ($this->lmsg)('thankyou.app_name'));
	}
}
