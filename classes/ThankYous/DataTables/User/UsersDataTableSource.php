<?php

namespace Claromentis\ThankYou\ThankYous\DataTables\User;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Config\Config;
use Claromentis\Core\DataTable\Contract\Parameters;
use Claromentis\Core\DataTable\Contract\TableFilter;
use Claromentis\Core\DataTable\Shared\ColumnHelper;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Widget\Sugre\SugreUtility;
use Claromentis\People\Entity\User;
use Claromentis\People\Repository\UserRepository;
use Claromentis\ThankYou\ThankYous;
use Claromentis\ThankYou\ThankYous\DataTables\FilterDataTableSource;
use Psr\Log\LoggerInterface;

class UsersDataTableSource extends FilterDataTableSource
{
	use ColumnHelper;

	/**
	 * @var Config
	 */
	private $core_config;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	public function __construct(
		ThankYous\Api $thank_you_api,
		SugreUtility $sugre_utility,
		UserRepository $user_repository,
		Lmsg $lmsg,
		LoggerInterface $logger,
		Config $core_config
	) {
		$this->lmsg            = $lmsg;
		$this->user_repository = $user_repository;
		$this->logger          = $logger;
		$this->core_config     = $core_config;

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

		if ($this->IsUserSurnameFirst())
		{
			$order = [['column' => ThankYous\ThankYousRepository::USER_TABLE . '.surname'], ['column' => ThankYous\ThankYousRepository::USER_TABLE . '.firstname']];
		} else
		{
			$order = [['column' => ThankYous\ThankYousRepository::USER_TABLE . '.firstname'], ['column' => ThankYous\ThankYousRepository::USER_TABLE . '.surname']];
		}

		$users_total_thank_yous = $this->api->GetUsersTotalThankYous(
			$context,
			$order,
			$limit,
			$offset,
			$filters['thanked_user_ids'],
			$filters['date_range'],
			$filters['tags']
		);

		$user_ids = array_keys($users_total_thank_yous);

		$users_entity_collection = $this->user_repository->find($user_ids);

		$rows = [];
		foreach ($users_total_thank_yous as $user_id => $user_total_thank_yous)
		{
			try
			{
				/**
				 * @var User $user
				 */
				$user = $users_entity_collection->find($user_id);
				if (isset($user))
				{
					$rows[] = [
						'user'             => $user->getFullname(),
						'total_thank_yous' => $user_total_thank_yous
					];
				}
			} catch (MappingException $exception)
			{
				$this->logger->error("Unexpected MappingException", [$exception]);
				continue;
			}
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

	/**
	 * Determines whether the Application is configured to display the User's Surname first.
	 *
	 * @return bool
	 */
	//TODO: Replace this method with something in Core once it exists.
	private function IsUserSurnameFirst(): bool
	{
		$firstname_config_identifier = '{fname}';
		$surname_config_identifier   = '{sname}';

		$display_config = $this->core_config->Get('cfg_name_format');

		if (!is_string($display_config))
		{
			return false;
		}

		$firstname_position = strpos($display_config, $firstname_config_identifier);
		$surname_position   = strpos($display_config, $surname_config_identifier);

		if ($firstname_position === false || $surname_position === false)
		{
			return false;
		}

		return $surname_position < $firstname_position;
	}
}
