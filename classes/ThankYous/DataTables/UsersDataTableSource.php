<?php

namespace Claromentis\ThankYou\ThankYous\DataTables;

use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\Api;

class UsersDataTableSource extends FilterDataTableSource
{
	use ColumnHelper;

	private $lmsg;

	public function __construct(Api\ThankYous $thank_you_api, SugreUtility $sugre_utility, Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;

		parent::__construct($thank_you_api, $sugre_utility);
	}

	/**
	 * {@inheritDoc}
	 */
	public function Columns(SecurityContext $context, Parameters $params = null)
	{
		$columns = [
			['user', ($this->lmsg)('common.user')],
			['total_thank_yous', ($this->lmsg)('thankyou.common.total_times_thanked')]
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

		$users_total_thank_yous = $this->api->GetUsersTotalThankYous($context, $limit, $offset, $filters['thanked_user_ids'], $filters['date_range'], $filters['tags']);

		$user_ids = array_keys($users_total_thank_yous);

		$users = $this->api->GetUsers($user_ids);

		$rows = [];
		foreach ($users_total_thank_yous as $user_id => $user_total_thank_yous)
		{
			$rows[] = [
				'user'             => $users[$user_id]->GetFullname(),
				'total_thank_yous' => $user_total_thank_yous
			];
		}

		return $rows;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Count(SecurityContext $context, Parameters $params, TableFilter $filters)
	{
		$filters = $this->FormatFilters($params->GetFilters());

		return $this->api->GetTotalUsers($context, $filters['thanked_user_ids'], $filters['date_range'], $filters['tags']);
	}
}
