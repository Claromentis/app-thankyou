<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\DataTable\Contract\DataSource;
use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use LogicException;

class TagDataTableSource implements DataSource
{
	use ColumnHelper;

	private $lmsg;

	private $repository;

	public function __construct(Lmsg $lmsg, TagRepository $repository)
	{
		$this->lmsg       = $lmsg;
		$this->repository = $repository;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Columns(SecurityContext $context, Parameters $params = null)
	{
		$columns = [
			['name', ($this->lmsg)('common.name')],
			['bg_colour', ($this->lmsg)('common.background_colour')],
			['active', ($this->lmsg)('common.active')]
		];

		return $this->CreateWithColumns($columns);
	}

	/**
	 * {@inheritDoc}
	 * @throws LogicException
	 */
	public function Data(SecurityContext $context, Parameters $params, TableFilter $filter)
	{
		$offset = (int) $params->GetOffset();
		$limit  = (int) $params->GetLimit();

		$tags = $this->repository->GetActiveAlphabeticTags($limit, $offset);

		$rows = [];
		foreach ($tags as $tag)
		{
			$metadata  = $tag->GetMetadata();
			$bg_colour = $metadata['bg_colour'] ?? '';

			$rows[] = [
				'name'      => $tag->GetName(),
				'bg_colour' => $bg_colour,
				'active'    => $tag->GetActive() ? ($this->lmsg)('common.yes') : ($this->lmsg)('common.no')
			];
		}

		return $rows;
	}

	/**
	 * {@inheritDoc}
	 */
	public function Count(SecurityContext $context, Parameters $params, TableFilter $filters)
	{
		return $this->repository->GetTotalTags();
	}

	/**
	 * {@inheritDoc}
	 */
	public function Filters()
	{
		return [];
	}
}
