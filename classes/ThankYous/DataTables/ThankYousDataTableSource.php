<?php

namespace Claromentis\ThankYou\ThankYous\DataTables;

use Claromentis\Core\Config\Config;
use Claromentis\Core\DataTable\ColumnFilter;
use Claromentis\Core\DataTable\Contract\DataSource;
use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Date;
use DateClaTimeZone;
use Psr\Log\LoggerInterface;

class ThankYousDataTableSource implements DataSource
{
	use ColumnHelper;

	private $api;

	private $config;

	private $lmsg;

	private $log;

	private $sugre_utility;

	public function __construct(Api\ThankYous $thank_you_api, Config $thank_you_config, Lmsg $lmsg, LoggerInterface $logger, SugreUtility $sugre_utility)
	{
		$this->api           = $thank_you_api;
		$this->config        = $thank_you_config;
		$this->lmsg          = $lmsg;
		$this->log           = $logger;
		$this->sugre_utility = $sugre_utility;
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

		if ($this->config->Get('thankyou_core_values_enabled') === true)
		{
			$columns[] = ['tags', ($this->lmsg)('thankyou.common.tags')];
		}

		$columns[] = ['description', ($this->lmsg)('thankyou.common.comment')];
		$columns[] = ['likes_count', ($this->lmsg)('common.likes')];

		if ($this->config->Get('thank_you_comments') === true)
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

		$get_tags     = (bool) $this->config->Get('thankyou_core_values_enabled');
		$get_comments = (bool) $this->config->Get('thank_you_comments');

		$rows = [];

		try
		{
			$thank_yous = $this->api->GetRecentThankYous($limit, $offset, $filters['date_range'], $filters['thanked_user_ids'], $filters['tags'], true, true, $get_tags);
		} catch (ThankYouOClass $exception)
		{
			$this->log->error("Failed to Get Recent Thank Yous from the database", [$exception]);

			return $rows;
		}

		$thank_you_comment_counts = [];
		if ($get_comments)
		{
			$thank_you_comment_counts = $this->api->GetThankYousCommentsCount($thank_yous);
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
				if ($thankable->GetOwnerClass() === PERM_OCLASS_GROUP)
				{
					$thanked_groups .= $first_group ? $thankable->GetName() : ", " . $thankable->GetName();
				}
				$first_group = false;
			}

			$first_user    = true;
			$thanked_users = '';
			foreach ($thank_you->GetUsers() as $user)
			{
				$thanked_users .= $first_user ? $user->GetFullname() : ", " . $user->GetFullname();
				$first_user    = false;
			}

			$row = [
				'date_created'        => $date_created->format('d-m-Y'),
				'thanked_groups'      => $thanked_groups,
				'total_thanked_users' => $thanked_users
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

			$row['description'] = $thank_you->GetDescription();
			$row['likes_count'] = $likes_counts[$id] ?? 0;

			if ($this->config->Get('thank_you_comments') === true)
			{
				$row['comments_count'] = $thank_you_comment_counts[$id] ?? 0;
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

		return $this->api->GetTotalThankYousCount($filters['date_range'], $filters['thanked_user_ids']);
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
	private function FormatFilters(array $filters): array
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

		$formatted_filters = [
			'date_range'       => $date_range,
			'thanked_user_ids' => $user_ids,
			'tags'             => $tags
		];

		return $formatted_filters;
	}
}
