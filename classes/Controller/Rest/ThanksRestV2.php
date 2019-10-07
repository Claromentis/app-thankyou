<?php

namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\View\ThanksListView;
use DateClaTimeZone;
use Psr\Http\Message\ServerRequestInterface;

class ThanksRestV2
{
	//TODO: Catch the exceptions
	private $api;

	private $thank_you_view;

	public function __construct(Api $api, ThanksListView $thank_you_view)
	{
		$this->api            = $api;
		$this->thank_you_view = $thank_you_view;
	}

	public function GetThankYou(int $id): JsonPrettyResponse
	{
		$thank_you         = $this->api->ThankYous()->GetThankYous($id, true);
		$display_thank_you = $this->thank_you_view->ConvertThankYouToArray($thank_you, DateClaTimeZone::GetCurrentTZ());

		return new JsonPrettyResponse($display_thank_you);
	}

	public function GetThankYous(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$limit        = $query_params['limit'] ?? 20;
		$offset       = $query_params['offset'] ?? 0;
		$thanked      = (bool) (int) ($query_params['thanked'] ?? null);

		$thank_yous         = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, $thanked, $security_context->GetExtranetAreaId());
		$display_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			$display_thank_yous[] = $this->thank_you_view->ConvertThankYouToArray($thank_you, DateClaTimeZone::GetCurrentTZ());
		}

		return new JsonPrettyResponse($display_thank_yous);
	}
}
