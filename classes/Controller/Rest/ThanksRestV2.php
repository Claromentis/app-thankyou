<?php

namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use DateClaTimeZone;
use Psr\Http\Message\ServerRequestInterface;

class ThanksRestV2
{
	//TODO: Catch the exceptions
	private $api;

	public function __construct(Api $api)
	{
		$this->api = $api;
	}

	public function GetThankYou(int $id, SecurityContext $security_context): JsonPrettyResponse
	{
		$extranet_area_id  = (int) $security_context->GetExtranetAreaId();
		$thank_you         = $this->api->ThankYous()->GetThankYous($id, true);
		$display_thank_you = $this->api->ThankYous()->ConvertThankYousToArrays($thank_you, DateClaTimeZone::GetCurrentTZ(), $extranet_area_id);

		return new JsonPrettyResponse($display_thank_you);
	}

	public function GetThankYous(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$extranet_area_id = (int) $security_context->GetExtranetAreaId();
		$query_params     = $request->getQueryParams();
		$limit            = $query_params['limit'] ?? 20;
		$offset           = $query_params['offset'] ?? 0;
		$thanked          = (bool) (int) ($query_params['thanked'] ?? null);

		$thank_yous         = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, $thanked, $extranet_area_id);
		$display_thank_yous = $this->api->ThankYous()->ConvertThankYousToArrays($thank_yous, DateClaTimeZone::GetCurrentTZ(), $extranet_area_id);

		return new JsonPrettyResponse($display_thank_yous);
	}
}
