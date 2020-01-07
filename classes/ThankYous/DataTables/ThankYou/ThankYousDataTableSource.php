<?php

namespace Claromentis\ThankYou\ThankYous\DataTables\ThankYou;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\ThankYous\DataTables\FilterDataTableSource;
use DateClaTimeZone;

class ThankYousDataTableSource extends FilterDataTableSource
{
	use ColumnHelper;

	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	public function __construct(Api\ThankYous $thank_you_api, Configuration\Api $config_api, SugreUtility $sugre_utility, Lmsg $lmsg)
	{
		$this->config_api = $config_api;
		$this->lmsg       = $lmsg;

		parent::__construct($thank_you_api, $sugre_utility);
	}

	/**
	 * {@inheritDoc}
	 */
	public function Columns(SecurityContext $context, Parameters $params = null)
	{
		$columns = [
			['date_created', ($this->lmsg)('common.date_created')],
			['thanked_groups', ($this->lmsg)('thankyou.common.thanked_groups')],
			['total_thanked_users', ($this->lmsg)('thankyou.common.total_thanked_users')]
		];

		if ($this->config_api->IsTagsEnabled())
		{
			$columns[] = ['tags', ($this->lmsg)('thankyou.common.tags')];
		}

		$columns[] = ['description', ($this->lmsg)('thankyou.common.comment'), new DescriptionDecorator()];
		$columns[] = ['likes_count', ($this->lmsg)('common.likes')];

		if ($this->config_api->IsCommentsEnabled())
		{
			$columns[] = ['comments_count', ($this->lmsg)('common.cla_comments.comments')];
		}

		return $this->CreateWithColumns($columns);
	}

	/**
	 * {@inheritDoc}
	 */
	public function Data(SecurityContext $context, Parameters $params, TableFilter $filter)
	{
		$time_zone = DateClaTimeZone::GetCurrentTZ();

		$offset = (int) $params->GetOffset();
		$limit  = (int) $params->GetLimit();

		$filters = $this->FormatFilters($params->GetFilters());

		$get_tags     = $this->config_api->IsTagsEnabled();
		$get_comments = $this->config_api->IsCommentsEnabled();

		$rows = [];

		$thank_yous = $this->api->GetRecentThankYous($context, true, true, $get_tags, $limit, $offset, $filters['date_range'], $filters['thanked_user_ids'], $filters['tags']);

		if ($get_comments)
		{
			$this->api->LoadThankYousComments($thank_yous);
		}

		$likes_counts = $this->api->GetThankYousLikesCount($thank_yous);

		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();

			$date_created = clone $thank_you->GetDateCreated();
			$date_created->setTimezone($time_zone);

			$first_group    = true;
			$thanked_groups = '';
			foreach ($thank_you->GetThankable() as $thankable)
			{
				if ($thankable->GetOwnerClass() === PermOClass::GROUP)
				{
					$thanked_groups .= $first_group ? $thankable->GetName() : ", " . $thankable->GetName();
				}
				$first_group = false;
			}

			$thanked_users = $thank_you->GetUsers();

			$thanked_users_string = '';
			$first_user           = true;
			foreach ($thanked_users as $user)
			{
				$user_name            = $this->api->CanSeeUser($context, $user) ? $user->GetFullname() : ($this->lmsg)('common.perms.hidden_name');
				$thanked_users_string .= $first_user ? $user_name : ", " . $user_name;
				$first_user           = false;
			}

			$row = [
				'date_created'        => $date_created->format('d-m-Y'),
				'thanked_groups'      => $thanked_groups,
				'total_thanked_users' => $thanked_users_string
			];

			if ($get_tags)
			{
				$first_tag = true;
				$tags      = '';
				foreach ($thank_you->GetTags() as $tag)
				{
					$tags      .= $first_tag ? $tag->GetName() : ", " . $tag->GetName();
					$first_tag = false;
				}

				$row['tags'] = $tags;
			}

			$row['description'] = ['description' => $thank_you->GetDescription(), 'thank_you_url' => $this->api->GetThankYouUrl($thank_you)];
			$row['likes_count'] = $likes_counts[$id] ?? 0;

			if ($this->config_api->IsCommentsEnabled())
			{
				$row['comments_count'] = $thank_you->GetComment()->GetTotalComments();
			}

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

		return $this->api->GetTotalThankYousCount($context, $filters['date_range'], $filters['thanked_user_ids'], $filters['tags']);
	}
}
