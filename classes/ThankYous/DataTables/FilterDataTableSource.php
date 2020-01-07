<?php

namespace Claromentis\ThankYou\ThankYous\DataTables;

use Claromentis\Core\DataTable\ColumnFilter;
use Claromentis\Core\DataTable\Contract\DataSource;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\Api\ThankYous;
use Date;

abstract class FilterDataTableSource implements DataSource
{
	/**
	 * @var ThankYous
	 */
	protected $api;

	/**
	 * @var SugreUtility
	 */
	protected $sugre_utility;

	public function __construct(ThankYous $thank_you_api, SugreUtility $sugre_utility)
	{
		$this->api           = $thank_you_api;
		$this->sugre_utility = $sugre_utility;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Filters()
	{
		return [
			'from_date' => new ColumnFilter('c.date1', 'str'),
			'to_date'   => new ColumnFilter('c.date2', 'str'),
			'erm'       => new ColumnFilter('c.erm', 'str'),
			'tags'      => new ColumnFilter('tags.tagged', 'str')
		];
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
			$user_ids      = $this->api->GetDistinctUserIdsFromOwnerClasses($owner_classes);
		}

		$date_range = null;
		$from_date  = $filters['from_date'] ?? null;
		$to_date    = $filters['to_date'] ?? null;
		if (isset($from_date) && isset($to_date))
		{
			$date_range = [Date::CreateFrom($from_date), Date::CreateFrom($to_date, '23:59')];
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
