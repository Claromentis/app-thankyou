<?php

namespace Claromentis\ThankYou\ThankYous\DataTables;

use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\Api;

class TagsDataTableSource extends FilterDataTableSource
{
	use ColumnHelper;

	private $lmsg;

	private $tag_api;

	public function __construct(Api\ThankYous $thank_you_api, SugreUtility $sugre_utility, Api\Tag $tag_api, Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;

		$this->tag_api = $tag_api;

		parent::__construct($thank_you_api, $sugre_utility);
	}

	/**
	 * {@inheritDoc}
	 */
	public function Columns(SecurityContext $context, Parameters $params = null)
	{
		$columns = [
			['name', ($this->lmsg)('common.date_created')],
			['total_uses', ($this->lmsg)('thankyou.tag.total_uses')]
		];

		return $this->CreateWithColumns($columns);
	}

	/**
	 * {@inheritDoc}
	 */
	public function Data(SecurityContext $context, Parameters $params, TableFilter $filter)
	{
		$offset = (int) $params->GetOffset();
		$limit  = (int) $params->GetLimit();

		$filters = $this->FormatFilters($params->GetFilters());

		$rows = [];

		$tags_thankyou_total_uses = $this->api->GetTagsTotalThankYouUses($context, $limit, $offset, $filters['thanked_user_ids'], $filters['date_range'], $filters['tags']);

		$tag_ids = array_keys($tags_thankyou_total_uses);

		$tags = $this->tag_api->GetTagsById($tag_ids);

		foreach ($tags as $tag_id => $tag)
		{
			$row = [
				'name'       => $tag->GetName(),
				'total_uses' => $tags_thankyou_total_uses[$tag_id]
			];

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Count(SecurityContext $context, Parameters $params, TableFilter $filters)
	{
		$filters = $this->FormatFilters($params->GetFilters());

		return $this->api->GetTotalTags($context, $filters['date_range'], $filters['thanked_user_ids'], $filters['tags']);
	}
}
