<?php

namespace Claromentis\ThankYou\ThankYous;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\DAL\Exceptions\TransactionException;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryBuilder;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\People\Entity\User;
use Claromentis\People\Repository\UserRepository;
use Claromentis\ThankYou\Exception\EmptyQueryFilterException;
use Claromentis\ThankYou\Exception\ThankedException;
use Claromentis\ThankYou\Tags;
use Claromentis\ThankYou\Exception\UnsupportedOwnerClassException;
use Claromentis\ThankYou\Thanked;
use Date;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class ThankYousRepository
{
	const AGGREGATION_ID = 143;
	const THANKED_OWNER_CLASSES = [PermOClass::INDIVIDUAL, PermOClass::GROUP];

	const TAG_TABLE = 'thankyou_tag';
	const THANK_YOU_TABLE = 'thankyou_item';
	const THANKED_USERS_TABLE = 'thankyou_user';
	const THANK_YOU_TAGS_TABLE = 'thankyou_tagged';
	const USER_TABLE = 'users';
	const THANKED_TABLE = 'thankyou_thanked';

	/**
	 * @var DbInterface
	 */
	private $db;

	/**
	 * @var DeletedUserFactory
	 */
	private $deleted_user_factory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var Thanked\Factory
	 */
	private $thanked_factory;

	/**
	 * @var ThankYouFactory
	 */
	private $thank_you_factory;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * @var QueryFactory
	 */
	private $query_factory;

	/**
	 * @var Tags\Api
	 */
	private $tags;

	/**
	 * ThankYousRepository constructor.
	 *
	 * @param ThankYouFactory    $thank_you_factory
	 * @param ThankYouUtility    $thank_you_utility
	 * @param DbInterface        $db_interface
	 * @param UserRepository     $user_repository
	 * @param LoggerInterface    $logger
	 * @param QueryFactory       $query_factory
	 * @param Tags\Api           $tag_api
	 * @param Thanked\Factory    $thanked_factory
	 * @param DeletedUserFactory $deleted_user_factory
	 */
	public function __construct(
		ThankYouFactory $thank_you_factory,
		ThankYouUtility $thank_you_utility,
		DbInterface $db_interface,
		UserRepository $user_repository,
		LoggerInterface $logger,
		QueryFactory $query_factory,
		Tags\Api $tag_api,
		Thanked\Factory $thanked_factory,
		DeletedUserFactory $deleted_user_factory
	) {
		$this->thank_you_factory    = $thank_you_factory;
		$this->utility              = $thank_you_utility;
		$this->user_repository      = $user_repository;
		$this->db                   = $db_interface;
		$this->logger               = $logger;
		$this->query_factory        = $query_factory;
		$this->tags                 = $tag_api;
		$this->thanked_factory      = $thanked_factory;
		$this->deleted_user_factory = $deleted_user_factory;
	}

	/**
	 * Given an array of Thank You IDs, returns an array of Thank Yous indexed by their IDs.
	 *
	 * @param int[] $ids
	 * @return ThankYou[]
	 * @throws MappingException
	 */
	public function GetThankYous(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new InvalidArgumentException("Failed to Get Thank Yous, invalid ID given");
			}
		}

		$query_string = "SELECT * FROM " . self::THANK_YOU_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause("id IN in:int:ids", $ids);

		$result = $this->db->query($query->GetQuery());

		$rows     = [];
		$user_ids = [];
		while ($row = $result->fetchArray())
		{
			$id           = (int) $row['id'];
			$author_id    = (int) $row['author'];
			$date_created = (string) $row['date_created'];

			$rows[$id] = ['author_id' => $author_id, 'date_created' => $date_created, 'description' => $row['description']];

			$user_ids[$author_id] = true;
		}

		$user_ids = array_keys($user_ids);

		$users = $this->user_repository->find($user_ids);

		$thank_yous = [];
		foreach ($ids as $id)
		{
			if (!isset($rows[$id]))
			{
				continue;
			}

			$thank_you = $this->Create(
				($users->find($rows[$id]['author_id']) ?? $rows[$id]['author_id']),
				$rows[$id]['description'] ?? '',
				new Date($rows[$id]['date_created'], new DateTimeZone('UTC'))
			);
			$thank_you->SetId($id);

			$thank_yous[$id] = $thank_you;
		}

		return $thank_yous;
	}

	/**
	 * @param int             $limit
	 * @param int             $offset
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $thanked_user_ids
	 * @param int[]|null      $tag_ids
	 * @param int[]|null      $extranet_ids
	 * @param bool            $allow_no_thanked
	 * @return int[]
	 */
	public function GetRecentThankYousIds(?int $limit = null, ?int $offset = null, ?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null)
	{
		$table = self::THANK_YOU_TABLE;

		$query = "
			SELECT $table.id
			FROM $table
			GROUP BY $table.id, $table.date_created
			ORDER BY $table.date_created DESC";

		$query = $this->query_factory->GetQueryBuilder($query);

		$query->setLimit($limit, $offset);

		if (isset($date_range))
		{
			$this->QueryFilterDateCreated($query, $date_range);
		}

		if (isset($extranet_ids) || isset($thanked_user_ids))
		{
			$query->AddJoin(self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		try
		{
			if (isset($thanked_user_ids))
			{
				$this->QueryFilterThankedUser($query, $thanked_user_ids);
			}

			if (isset($tag_ids))
			{
				$this->QueryJoinThankYouToTagged($query);
				$this->QueryFilterTags($query, $tag_ids);
			}

			if (isset($extranet_ids))
			{
				$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
				$this->QueryFilterExtranet($query, $extranet_ids, $allow_no_thanked);
			}
		} catch (EmptyQueryFilterException $exception)
		{
			return [];
		}

		$result = $this->db->query($query->GetQuery());

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['id'];
		}

		return $thank_you_ids;
	}

	/**
	 * Given an array of Thank You IDs, returns an array of Thankeds, indexed by the Thank You's ID and then the Thanked's ID.
	 *
	 * @param int[] $ids
	 * @return array[Thanked]
	 */
	public function GetThankYousThankedsByThankYouIds(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT id, item_id, object_type, object_id FROM " . self::THANKED_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause(self::THANKED_TABLE . ".item_id IN in:int:thank_you_ids", $ids);

		$results = $this->db->query($query->GetQuery());

		$thank_yous_thankeds = [];
		$thankeds            = [];
		while ($row = $results->fetchArray())
		{
			$id                    = (int) $row['id'];
			$thank_you_id          = (int) $row['item_id'];
			$owner_class_id        = (int) $row['object_type'];
			$owner_classes_item_id = (int) $row['object_id'];

			$thankeds[$id] = ['oclass' => $owner_class_id, 'id' => $owner_classes_item_id];

			$thank_yous_thankeds[$thank_you_id][$id] = true;
		}

		try
		{
			$thankeds = $this->CreateThanked($thankeds);
		} catch (UnsupportedOwnerClassException $exception)
		{
			$this->logger->error("One or more Thanked in the Repository is invalid", [$exception]);

			return [];
		}

		foreach ($thank_yous_thankeds as $thank_you_id => $thank_you_thankeds)
		{
			foreach ($thank_you_thankeds as $id => $true)
			{
				$thank_yous_thankeds[$thank_you_id][$id] = $thankeds[$id];
			}
		}

		return $thank_yous_thankeds;
	}

	/**
	 * Given and array of Thank You IDs, returns an array of Thank You Users, indexed by the Thank Yous's ID.
	 *
	 * @param int[] $ids
	 * @return array[]
	 * @throws MappingException
	 */
	public function GetThankYousUsersByThankYouIds(array $ids)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		$query_string = "SELECT * FROM " . self::THANKED_USERS_TABLE;
		$query        = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddWhereAndClause(self::THANKED_USERS_TABLE . ".thanks_id IN in:int:ids", $ids);

		$results = $this->db->query($query->GetQuery());

		$user_ids = [];
		$rows     = [];
		while ($row = $results->fetchArray())
		{
			$thank_you_id = (int) $row['thanks_id'];
			$user_id      = (int) $row['user_id'];

			$rows[]             = ['thank_you_id' => $thank_you_id, 'user_id' => $user_id];
			$user_ids[$user_id] = true;
		}

		$user_ids = array_keys($user_ids);

		$users_entity_collection = $this->user_repository->find($user_ids);

		$thank_yous_users = [];
		foreach ($rows as $row)
		{
			$user = $users_entity_collection->find($row['user_id']);
			if (isset($user))
			{
				$thank_yous_users[$row['thank_you_id']][$row['user_id']] = $user;
			} else
			{
				$thank_yous_users[$row['thank_you_id']][$row['user_id']] = $this->deleted_user_factory->Create($row['user_id']);
			}
		}

		return $thank_yous_users;
	}

	/**
	 * @param array|null      $orders
	 * @param int|null        $limit
	 * @param int|null        $offset
	 * @param bool|null       $active
	 * @param int[]|null      $extranet_ids
	 * @param bool            $allow_no_thanked
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $thanked_user_ids
	 * @param int[]|null      $tag_ids
	 * @return array
	 */
	public function GetTagsTotalThankYouUses(?array $orders = null, ?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null)
	{
		$order_string = "";
		if (isset($orders))
		{
			$order_string = $this->utility->BuildOrderString($orders);
		}

		// build group by string using query and order fields
		$group_columns = [self::TAG_TABLE . ".id"];
		foreach ($orders as $order)
		{
			if (!isset($order['aggregate']) || $order['aggregate'] !== true)
			{
				$group_columns[] = $order['column'];
			}
		}
		// ensure no duplicated columns in the grouping
		$group_columns = array_unique($group_columns);

		$query_string = "SELECT COUNT(DISTINCT " . self::THANK_YOU_TAGS_TABLE . ".item_id) AS \"" . self::THANK_YOU_TAGS_TABLE . ".total_uses\"";
		$query_string .= ", " . self::TAG_TABLE . ".id AS \"" . self::TAG_TABLE . ".id\"";
		$query_string .= " FROM " . self::TAG_TABLE;
		$query_string .= $order_string;
		$query_string .= " GROUP BY " . implode(',', $group_columns);

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddJoin(self::TAG_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE . ".tag_id = " . self::TAG_TABLE . ".id AND " . self::THANK_YOU_TAGS_TABLE . ".aggregation_id = " . self::AGGREGATION_ID);

		if ($active !== null)
		{
			$query->AddWhereAndClause(self::TAG_TABLE . ".active = int:active", (int) $active);
		}

		if (isset($thanked_user_ids) || isset($extranet_ids))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		try
		{
			if (isset($thanked_user_ids))
			{
				$this->QueryFilterThankedUser($query, $thanked_user_ids);
			}

			if (isset($date_range))
			{
				$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANK_YOU_TABLE . ".id");
				$this->QueryFilterDateCreated($query, $date_range);
			}

			if (isset($tag_ids))
			{
				$this->QueryFilterTags($query, $tag_ids);
			}

			if (isset($extranet_ids))
			{
				$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
				$this->QueryFilterExtranet($query, $extranet_ids, $allow_no_thanked);
			}
		} catch (EmptyQueryFilterException $exception)
		{
			return [];
		}

		$query->SetLimit($limit, $offset);

		$result = $this->db->query($query->GetQuery());

		$tags_total_thank_yous = [];
		while ($row = $result->fetchArray())
		{
			$tags_total_thank_yous[(int) $row[self::TAG_TABLE . ".id"]] = (int) $row[self::THANK_YOU_TAGS_TABLE . ".total_uses"];
		}

		if (isset($tag_ids))
		{
			foreach ($tag_ids as $tag_id)
			{
				if (!isset($tags_total_thank_yous[$tag_id]))
				{
					$tags_total_thank_yous[$tag_id] = 0;
				}
			}
		}

		return $tags_total_thank_yous;
	}

	/**
	 * Returns total number of thanks items in the database
	 *
	 * @param int[]|null      $extranet_ids
	 * @param bool            $allow_no_thanked
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $thanked_user_ids
	 * @param int[]|null      $tag_ids
	 * @return int
	 */
	public function GetTotalThankYousCount(?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::THANK_YOU_TABLE . ".id) FROM " . self::THANK_YOU_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		if (isset($extranet_ids) || isset($thanked_user_ids))
		{
			$query->AddJoin(self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		try
		{
			if (isset($extranet_ids))
			{
				$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
				$this->QueryFilterExtranet($query, $extranet_ids, $allow_no_thanked);
			}

			if (isset($date_range))
			{
				$this->QueryFilterDateCreated($query, $date_range);
			}

			if (isset($thanked_user_ids))
			{
				$this->QueryFilterThankedUser($query, $thanked_user_ids);
			}

			if (isset($tag_ids))
			{
				$this->QueryJoinThankYouToTagged($query);
				$this->QueryFilterTags($query, $tag_ids);
			}
		} catch (EmptyQueryFilterException $exception)
		{
			return 0;
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Returns an array of the total number of Thank Yous associated with a User, indexed by the User's ID.
	 *
	 * @param int|null        $limit
	 * @param int|null        $offset
	 * @param int[]           $user_ids
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $tag_ids
	 * @param int[]|null      $extranet_ids
	 * @return int[]
	 */
	public function GetTotalUsersThankYous(?int $limit = null, ?int $offset = null, ?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null, ?array $extranet_ids = null): array
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::THANKED_USERS_TABLE . ".thanks_id) AS \"" . self::THANKED_USERS_TABLE . ".total_thank_yous\"";
		$query_string .= ", " . self::USER_TABLE . ".id AS \"" . self::USER_TABLE . ".id\"";
		$query_string .= " FROM " . self::USER_TABLE;
		$query_string .= " ORDER BY " . self::USER_TABLE . ".firstname ASC";
		$query_string .= " GROUP BY " . self::USER_TABLE . ".id, " . self::USER_TABLE . ".firstname";

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddJoin(self::USER_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");

		try
		{
			$this->QueryFilterUserThankYouCounts($query, $user_ids, $date_range, $tag_ids, $extranet_ids);
		} catch (EmptyQueryFilterException $exception)
		{
			return [];
		}

		$query->SetLimit($limit, $offset);

		$result = $this->db->query($query->GetQuery());

		$users_total_thank_yous = [];
		while ($row = $result->fetchArray())
		{
			$users_total_thank_yous[(int) $row[self::USER_TABLE . ".id"]] = (int) $row[self::THANKED_USERS_TABLE . ".total_thank_yous"];
		}

		if (isset($user_ids))
		{
			foreach ($user_ids as $user_id)
			{
				if (!isset($users_total_thank_yous[$user_id]))
				{
					$users_total_thank_yous[$user_id] = 0;
				}
			}
		}

		return $users_total_thank_yous;
	}

	/**
	 * Counts the number of results for a list of the total number of Thank Yous associated with a User.
	 *
	 * Count equivalent of GetTotalUsersThankYous().
	 *
	 * @param int[]|null      $user_ids
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $tag_ids
	 * @param int[]|null      $extranet_ids
	 * @return int
	 */
	public function GetTotalUsers(?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null, ?array $extranet_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::USER_TABLE . ".id) FROM " . self::USER_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);

		$query->AddJoin(self::USER_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");

		try
		{
			$this->QueryFilterUserThankYouCounts($query, $user_ids, $date_range, $tag_ids, $extranet_ids);
		} catch (EmptyQueryFilterException $exception)
		{
			return 0;
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Returns the number of tags which satisfy the filtering provided.
	 *
	 * @param int[]|null      $extranet_ids
	 * @param bool            $allow_no_thanked
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $thanked_user_ids
	 * @param int[]|null      $tag_ids
	 * @return int
	 */
	public function GetTotalTags(?array $extranet_ids = null, bool $allow_no_thanked = true, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$query_string = "SELECT COUNT(DISTINCT " . self::TAG_TABLE . ".id) FROM " . self::TAG_TABLE;

		$query = $this->query_factory->GetQueryBuilder($query_string);
		$query->AddWhereAndClause(self::THANK_YOU_TAGS_TABLE . ".aggregation_id = " . self::AGGREGATION_ID);

		$query->AddJoin(self::TAG_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE . ".tag_id = " . self::TAG_TABLE . ".id");

		if (isset($thanked_user_ids) || isset($extranet_ids))
		{
			$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE, self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANKED_USERS_TABLE . ".thanks_id");
		}

		try
		{
			if (isset($thanked_user_ids))
			{
				$this->QueryFilterThankedUser($query, $thanked_user_ids);
			}

			if (isset($date_range))
			{
				$query->AddJoin(self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE . ".item_id = " . self::THANK_YOU_TABLE . ".id");
				$this->QueryFilterDateCreated($query, $date_range);
			}

			if (isset($tag_ids))
			{
				$this->QueryFilterTags($query, $tag_ids);
			}

			if (isset($extranet_ids))
			{
				$query->AddJoin(self::THANKED_USERS_TABLE, self::USER_TABLE, self::USER_TABLE, self::THANKED_USERS_TABLE . ".user_id = " . self::USER_TABLE . ".id");
				$this->QueryFilterExtranet($query, $extranet_ids, $allow_no_thanked);
			}
		} catch (EmptyQueryFilterException $exception)
		{
			return 0;
		}

		[$count] = $this->db->query_row($query->GetQuery());

		return $count;
	}

	/**
	 * Create a Thank You object.
	 *
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 */
	public function Create($author, string $description, ?Date $date_created = null)
	{
		return $this->thank_you_factory->Create($author, $description, $date_created);
	}

	/**
	 * Takes an array of arrays in the format ['oclass' => int, 'id' => int]
	 * Returns an array of Thanked Objects, retaining indexing.
	 *
	 * @param array $thankeds
	 * @return Thanked\ThankedInterface[]
	 * @throws UnsupportedOwnerClassException - If one or more of the Owner Classes given is not supported.
	 */
	public function CreateThanked(array $thankeds): array
	{
		//TODO: Expand accepted objects to include all PERM_OCLASS_*
		$owner_classes_ids = [];
		foreach ($thankeds as $thanked)
		{
			if (!isset($thanked['oclass']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object Class not specified");
			}

			if (!in_array($thanked['oclass'], self::THANKED_OWNER_CLASSES))
			{
				throw new UnsupportedOwnerClassException("Failed to Get Permission Object Classes Names, Object class is not supported");
			}

			if (!isset($thanked['id']) || !is_int($thanked['id']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object ID is not specified or is invalid");
			}

			if (!isset($owner_classes_ids[$thanked['oclass']]))
			{
				$owner_classes_ids[$thanked['oclass']] = [];
			}

			$owner_classes_ids[$thanked['oclass']][$thanked['id']] = true;
		}

		if (isset($owner_classes_ids[PermOClass::GROUP]))
		{
			$owner_classes_ids[PermOClass::GROUP] = $this->thanked_factory->Create(PermOClass::GROUP, array_keys($owner_classes_ids[PermOClass::GROUP]));
		}

		if (isset($owner_classes_ids[PermOClass::INDIVIDUAL]))
		{
			$owner_classes_ids[PermOClass::INDIVIDUAL] = $this->thanked_factory->Create(PermOClass::INDIVIDUAL, array_keys($owner_classes_ids[PermOClass::INDIVIDUAL]));
		}

		foreach ($thankeds as $offset => $thanked)
		{
			$thankeds[$offset] = $owner_classes_ids[$thanked['oclass']][$thanked['id']];
		}

		return $thankeds;
	}

	/**
	 * Saves a Thank You to the repository, including its Users and Thankeds, but excluding Tags.
	 * If the Thank You is new the ID its ID will also be set.
	 * If a Thanked is new its ID will also be set.
	 *
	 * @param ThankYou $thank_you
	 * @return int ID of saved Thank You
	 */
	public function Save(ThankYou $thank_you)
	{
		$id = $this->SaveThankYou($thank_you);

		$thank_you->SetId($id);

		$thankeds = $thank_you->GetThanked();
		if (isset($thankeds))
		{
			$this->DeleteThankYouThanked($id);

			foreach ($thankeds as $thanked)
			{
				try
				{
					$thanked_id = $this->SaveThanked($id, $thanked);
					$thanked->SetId($thanked_id);
				} catch (ThankedException $exception)
				{
					$this->logger->warning("Could not save a Thank You's Thanked, not enough data", [$exception]);
				}
			}
		}

		$thanked_users = $thank_you->GetUsers();
		if (isset($thanked_users))
		{
			$this->DeleteThankYouUsers($id);

			foreach ($thanked_users as $thanked_user)
			{
				$this->SaveUser($id, $thanked_user);
			}
		}

		return $id;
	}

	/**
	 * Given a Thank You ID, deletes the Thank You and any hard dependencies from the repository.
	 *
	 * @param int $id
	 * @throws StorageException - If the Thank You could not be deleted from the repository.
	 */
	public function Delete(int $id)
	{
		try
		{
			$this->db->DoTransaction(function () use ($id) {
				$this->DeleteThankYouUsers($id);
				$this->DeleteThankYouThanked($id);
				$this->DeleteThankYou($id);
			});
		} catch (TransactionException $exception)
		{
			throw new StorageException("Failed to Delete Thank You from Repository", null, $exception);
		}
	}

	/**
	 * Saves a Thank You. If an ID is provided, an existing record will be updated, if not a new entry will be created.
	 * Returns the ID of the saved Thank You.
	 *
	 * @param ThankYou $thank_you - The Thank You to be saved.
	 * @return int - The ID of the Thank You.
	 */
	private function SaveThankYou(ThankYou $thank_you): int
	{
		$id = $thank_you->GetId();

		$author_id = $thank_you->GetAuthor()->id;

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone(new DateTimeZone("UTC"));
		$date_created_string = $date_created->format('YmdHis');

		$description = $thank_you->GetDescription();

		$db_fields = [
			'int:author'       => $author_id,
			'int:date_created' => $date_created_string,
			'clob:description' => $description
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::THANK_YOU_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::THANK_YOU_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Saves a Thanked to the Repository. If an ID is provided, an existing record will be updated,
	 * if not a new entry will be created.
	 * Due to the hard dependency on the Thank You, it is recommended that a check has been done for the the Thank You
	 * with the given ID prior to calling this.
	 * Returns the ID of the saved Thanked.
	 *
	 * @param int                      $thank_you_id
	 * @param Thanked\ThankedInterface $thanked
	 * @return int
	 * @throws ThankedException - If the Thanked does not have an Owner Class ID or Item ID.
	 */
	private function SaveThanked(int $thank_you_id, Thanked\ThankedInterface $thanked): int
	{
		$id             = $thanked->GetId();
		$owner_class_id = $thanked->GetOwnerClass();
		$item_id        = $thanked->GetItemId();

		if (!isset($owner_class_id))
		{
			throw new ThankedException("Failed to Save Thanked, Thanked does not have an Owner Class ID set");
		}
		if (!isset($item_id))
		{
			throw new ThankedException("Failed to Save Thanked, Thanked does not have an Item ID set");
		}

		$db_fields = [
			'int:item_id'     => $thank_you_id,
			'int:object_type' => $owner_class_id,
			'int:object_id'   => $item_id
		];

		if (isset($id))
		{
			$query = $this->query_factory->GetQueryUpdate(self::THANKED_TABLE, "id=int:id", $db_fields);
			$query->Bind('id', $id);
			$this->db->query($query);
		} else
		{
			$query = $this->query_factory->GetQueryInsert(self::THANKED_TABLE, $db_fields);
			$this->db->query($query);
			$id = $this->db->insertId();
		}

		return $id;
	}

	/**
	 * Saves a Thank You's thanked User.
	 * Due to the hard dependency on the Thank You, it is recommended that a check has been done for the the Thank You
	 * with the given ID prior to calling this.
	 *
	 * @param int  $thank_you_id
	 * @param User $user
	 */
	private function SaveUser(int $thank_you_id, User $user)
	{
		$db_fields = [
			'int:thanks_id' => $thank_you_id,
			'int:user_id'   => $user->id
		];

		$query = $this->query_factory->GetQueryInsert(self::THANKED_USERS_TABLE, $db_fields);
		$this->db->query($query);
	}

	/**
	 * Given a Thank You's repository ID, deletes the Thank You from the repository.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYou(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANK_YOU_TABLE . " WHERE id=int:thank_you_id";

		$this->db->query($query_string, $thank_you_id);
	}

	/**
	 * Given a Thank You's deletes all of a Thank You's Thanked.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYouThanked(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANKED_TABLE . " WHERE item_id=int:thank_you_id";

		$this->db->query($query_string, $thank_you_id);
	}

	/**
	 * Delete all of a Thank You's Users.
	 *
	 * @param int $thank_you_id
	 */
	private function DeleteThankYouUsers(int $thank_you_id)
	{
		$query_string = "DELETE FROM " . self::THANKED_USERS_TABLE . " WHERE thanks_id=int:thank_you_id";
		$this->db->query($query_string, $thank_you_id);
	}

	private function QueryJoinThankYouToTagged(QueryBuilder $query)
	{
		$query->AddJoin(self::THANK_YOU_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TABLE . ".id = " . self::THANK_YOU_TAGS_TABLE . ".item_id AND aggregation_id = " . self::AGGREGATION_ID);
	}

	private function QueryJoinThankedUsersToTagged(QueryBuilder $query)
	{
		$query->AddJoin(self::THANKED_USERS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANK_YOU_TAGS_TABLE, self::THANKED_USERS_TABLE . ".thanks_id = " . self::THANK_YOU_TAGS_TABLE . ".item_id AND aggregation_id = " . self::AGGREGATION_ID);
	}

	/**
	 * Given a Query and a Date Range, routes to the correct QueryFilter for Date Created.
	 *
	 * @param QueryBuilder $query
	 * @param DateTime[]   $date_range
	 */
	private function QueryFilterDateCreated(QueryBuilder $query, array $date_range): void
	{
		if (isset($date_range[0]) && isset($date_range[1]))
		{
			$this->QueryFilterDateCreatedBetween($query, $date_range[0], $date_range[1]);
		} elseif (isset($date_range[0]))
		{
			$this->QueryFilterDateCreatedAfter($query, $date_range[0]);
		} elseif (isset($date_range[1]))
		{
			$this->QueryFilterDateCreatedBefore($query, $date_range[1]);
		} else
		{
			throw new InvalidArgumentException("Failed to Filter Date Created, invalid Argument 2");
		}
	}

	/**
	 * Given a Query and two DateTimes, applies a Filter to the Query that limits the Thank You's Date Created to a
	 * range defined by the DateTimes.
	 *
	 * @param QueryBuilder $query
	 * @param DateTime     $from
	 * @param DateTime     $to
	 */
	private function QueryFilterDateCreatedBetween(QueryBuilder $query, DateTime $from, DateTime $to): void
	{
		$query->AddWhereAndClause(
			self::THANK_YOU_TABLE . ".date_created BETWEEN "
			. $this->FormatQueryDate($from)
			. " AND "
			. $this->FormatQueryDate($to)
		);
	}

	/**
	 * Given a Query and a DateTime, applies a Filter to the Query that limits the Thank You's earliest Date Created.
	 *
	 * @param QueryBuilder $query
	 * @param DateTime     $from
	 */
	private function QueryFilterDateCreatedAfter(QueryBuilder $query, DateTime $from): void
	{
		$query->AddWhereAndClause(self::THANK_YOU_TABLE . ".date_created > " . $this->FormatQueryDate($from));
	}

	/**
	 * Given a Query and a DateTime, applies a Filter to the Query that limits the Thank You's latest Date Created.
	 *
	 * @param QueryBuilder $query
	 * @param DateTime     $to
	 */
	private function QueryFilterDateCreatedBefore(QueryBuilder $query, DateTime $to): void
	{
		$query->AddWhereAndClause(self::THANK_YOU_TABLE . ".date_created < " . $this->FormatQueryDate($to));
	}

	/**
	 * @param QueryBuilder $query
	 * @param array        $thanked_user_ids
	 * @throws EmptyQueryFilterException - If an empty array of User IDs is given.
	 */
	private function QueryFilterThankedUser(QueryBuilder $query, array $thanked_user_ids)
	{
		if (count($thanked_user_ids) === 0)
		{
			throw new EmptyQueryFilterException("Empty Thanked User Filter");
		}

		$invalid_user_ids = [];
		foreach ($thanked_user_ids as $user_id)
		{
			if (!is_int($user_id))
			{
				$invalid_user_ids[] = $user_id;
			}
		}

		if (!empty($invalid_user_ids))
		{
			throw new InvalidArgumentException("Failed to Add Thanked User Filter to Query, invalid user IDs for thanked user filter: " . implode(', ', $invalid_user_ids));
		}

		$query->AddWhereAndClause(self::THANKED_USERS_TABLE . ".user_id IN in:int:thanked_user_ids", $thanked_user_ids);
	}

	/**
	 * @param QueryBuilder $query
	 * @param array        $tag_ids
	 * @throws EmptyQueryFilterException - If an empty array of Tag IDs is given.
	 */
	private function QueryFilterTags(QueryBuilder $query, array $tag_ids)
	{
		if (count($tag_ids) === 0)
		{
			throw new EmptyQueryFilterException("Empty Tags Filter");
		}

		$invalid_tag_ids = [];
		foreach ($tag_ids as $tag_id)
		{
			if (!is_int($tag_id))
			{
				$invalid_tag_ids[] = $tag_id;
			}
		}

		if (!empty($invalid_tag_ids))
		{
			throw new InvalidArgumentException("Failed to Add Tagged Filter to Query, invalid Tag IDs for Tag filter: " . implode(', ', $invalid_tag_ids));
		}

		$query->AddWhereAndClause(self::THANK_YOU_TAGS_TABLE . ".tag_id IN in:int:tag_ids", $tag_ids);
	}

	/**
	 * @param QueryBuilder $query
	 * @param array        $extranet_ids
	 * @param bool         $allow_absence
	 * @throws EmptyQueryFilterException
	 */
	private function QueryFilterExtranet(QueryBuilder $query, array $extranet_ids, bool $allow_absence = false)
	{
		if (count($extranet_ids) === 0)
		{
			throw new EmptyQueryFilterException("Empty Extranets Filter");
		}

		$where             = "(" . self::USER_TABLE . ".ex_area_id IN (";
		$first_extranet_id = true;
		foreach ($extranet_ids as $extranet_id)
		{
			if (!is_int($extranet_id))
			{
				throw new InvalidArgumentException("Failed to Add Extranet Filter to Query, Extranet ID '" . (string) $extranet_id . "' is not an integer");
			}

			$where             .= $first_extranet_id ? $extranet_id : ", " . $extranet_id;
			$first_extranet_id = false;
		}
		$where .= ")";

		if ($allow_absence)
		{
			$where .= " OR " . self::USER_TABLE . ".id IS NULL";
		}

		$where .= ")";

		$query->AddWhereAndClause($where);
	}

	/**
	 * Filters a user thank you count query.
	 *
	 * Filters by user IDs, date range, thank you tags and user extranet IDs.
	 *
	 * @param QueryBuilder    $query
	 * @param int[]|null      $user_ids
	 * @param DateTime[]|null $date_range
	 * @param int[]|null      $tag_ids
	 * @param int[]|null      $extranet_ids
	 * @throws EmptyQueryFilterException
	 */
	private function QueryFilterUserThankYouCounts(
		QueryBuilder $query,
		?array $user_ids = null,
		?array $date_range = null,
		?array $tag_ids = null,
		?array $extranet_ids = null
	): void {
		if (isset($user_ids))
		{
			$this->QueryFilterThankedUser($query, $user_ids);
		}

		if (isset($date_range))
		{
			$query->AddJoin(self::THANKED_USERS_TABLE, self::THANK_YOU_TABLE, self::THANK_YOU_TABLE, self::THANKED_USERS_TABLE . ".thanks_id = " . self::THANK_YOU_TABLE . ".id");
			$this->QueryFilterDateCreated($query, $date_range);
		}

		if (isset($tag_ids))
		{
			$this->QueryJoinThankedUsersToTagged($query);
			$this->QueryFilterTags($query, $tag_ids);
		}

		if (isset($extranet_ids))
		{
			$this->QueryFilterExtranet($query, $extranet_ids);
		}
	}

	/**
	 * Given a DateTime, formats it into a string that may be used to query the Database.
	 *
	 * @param DateTime $date_time
	 * @return string
	 */
	private function FormatQueryDate(DateTime $date_time)
	{
		$date_time = clone $date_time;
		$date_time->setTimezone(new DateTimeZone('UTC'));

		return $date_time->format('YmdHis');
	}
}
