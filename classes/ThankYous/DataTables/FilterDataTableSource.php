<?php

namespace Claromentis\ThankYou\ThankYous\DataTables;

use Claromentis\Core\DataTable\Contract\DataSource;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\ThankYous;
use Date;

abstract class FilterDataTableSource implements DataSource
{
	/**
	 * @var ThankYous\Api
	 */
	protected $api;

	/**
	 * @var SugreUtility
	 */
	protected $sugre_utility;

	public function __construct(ThankYous\Api $thank_you_api, SugreUtility $sugre_utility)
	{
		$this->api           = $thank_you_api;
		$this->sugre_utility = $sugre_utility;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Filters()
	{
		return [];
	}

	/**
	 * @param array $filters
	 * @return array
	 */
	protected function FormatFilters(array $filters): array
	{
		$user_ids      = null;
		$owner_classes = $filters['owner_classes']['selected_options'] ?? null;
		if (is_array($owner_classes) && count($owner_classes) > 0)
		{
			$owner_classes = $this->sugre_utility->DecodeOutput($owner_classes);
			$user_ids      = $this->api->GetOwnersUserIds($owner_classes);
		}

		// Date Range
		$date_range = null;
		$from_date  = isset($filters['from_date']) ? Date::CreateFrom($filters['from_date']) : null;
		$to_date    = isset($filters['to_date']) ? Date::CreateFrom($filters['to_date'], '23:59') : null;
		if (isset($from_date) && isset($to_date))
		{
			$date_range = [$from_date, $to_date];
		}

		$tags = null;
		$tag  = isset($filters['tag']) && $filters['tag'] !== '' ? $filters['tag'] : null;
		if (isset($tag))
		{
			$tags = [(int) $tag];
		}

		return [
			'date_range'       => $date_range,
			'thanked_user_ids' => $user_ids,
			'tags'             => $tags
		];
	}
}
