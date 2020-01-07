<?php

namespace Claromentis\ThankYou\Tags\DataTables;

use Claromentis\Core\DataTable\Contract\DataSource;
use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Tags\TagRepository;
use LogicException;

class TagDataTableSource implements DataSource
{
	use ColumnHelper;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var TagRepository
	 */
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
			['actions', ($this->lmsg)('thankyou.common.actions'), new ActionsDataTableDecorator()]
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

		$tags = $this->repository->GetFilteredTags($limit, $offset, null, [['column' => 'name']]);

		$rows = [];
		foreach ($tags as $tag)
		{
			$bg_colour = $tag->GetBackgroundColour();

			$rows[] = [
				'name'      => $tag->GetName(),
				'bg_colour' => $bg_colour,
				'actions'   => ['active' => $tag->GetActive(), 'id' => $tag->GetId()]
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
