<?php

namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\ThankYou\Api\ThankYous;
use Claromentis\ThankYou\View\ThanksListView;
use DateClaTimeZone;
use Psr\Http\Message\ServerRequestInterface;

class ThanksRestV2
{
	//TODO: Catch the exceptions
	private $api_thank_yous;

	private $thank_you_view;

	public function __construct(ThankYous $thank_yous, ThanksListView $thank_you_view)
	{
		$this->api_thank_yous = $thank_yous;
		$this->thank_you_view = $thank_you_view;
	}

	public function GetThankYou(int $id): JsonPrettyResponse
	{
		$thank_you         = $this->api_thank_yous->GetThankYous($id, true);
		$display_thank_you = $this->thank_you_view->ConvertThankYouToArray($thank_you, DateClaTimeZone::GetCurrentTZ());

		return new JsonPrettyResponse($display_thank_you);
	}

	public function GetThankYous(ServerRequestInterface $request)
	{
		$query_params = $request->getQueryParams();
		$limit        = $query_params['limit'] ?? 20;
		$offset       = $query_params['offset'] ?? 0;
		$thanked      = (bool) (int) ($query_params['thanked'] ?? null);

		$thank_yous         = $this->api_thank_yous->GetRecentThankYous($limit, $offset, $thanked);
		$display_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			$display_thank_yous[] = $this->thank_you_view->ConvertThankYouToArray($thank_you, DateClaTimeZone::GetCurrentTZ());
		}

		return new JsonPrettyResponse($display_thank_yous);
	}
}
