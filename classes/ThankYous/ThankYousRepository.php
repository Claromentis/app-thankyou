<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\AclRepository;
use Claromentis\Core\Acl\Exception\InvalidSubjectException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\Core\DAL\QueryFactory;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Exception\ThankYouAuthor;
use Claromentis\ThankYou\Exception\ThankYouException;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\Exception\ThankYouRepository;
use Claromentis\ThankYou\ThanksItemFactory;
use Date;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use User;

class ThankYousRepository
{
	const THANKABLES = [PERM_OCLASS_INDIVIDUAL, PERM_OCLASS_GROUP];

	/**
	 * @var AclRepository
	 */
	private $acl_repository;

	/**
	 * @var DbInterface
	 */
	private $db;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ThanksItemFactory
	 */
	private $thanks_item_factory;

	/**
	 * @var ThankYouFactory
	 */
	private $thank_you_factory;

	/**
	 * @var QueryFactory
	 */
	private $query_factory;

	public function __construct(
		ThankYouFactory $thank_you_factory,
		ThanksItemFactory $thanks_item_factory,
		AclRepository $acl_repository,
		DbInterface $db_interface,
		LoggerInterface $logger,
		QueryFactory $query_factory
	) {
		$this->acl_repository      = $acl_repository;
		$this->db                  = $db_interface;
		$this->thanks_item_factory = $thanks_item_factory;
		$this->thank_you_factory   = $thank_you_factory;
		$this->logger              = $logger;
		$this->query_factory       = $query_factory;
	}

	/**
	 * Create a Thank You object.
	 *
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 * @throws ThankYouAuthor - If the Author could not be loaded.
	 */
	public function Create($author, string $description, ?Date $date_created = null)
	{
		return $this->thank_you_factory->Create($author, $date_created, $description);
	}

	/**
	 * @param int $id
	 */
	public function DeleteFromDb(int $id)
	{
		$thanks_item = $this->thanks_item_factory->Create();
		$thanks_item->SetId($id);
		try
		{
			$thanks_item->Delete();
		} catch (ThankYouException $exception)
		{
			throw new LogicException("Unexpected Exception thrown when deleting Thank You", null, $exception);
		}
	}

	/**
	 * Create an array of Thankables from an array of Group IDs. The returned array is indexed by the Group's ID
	 *
	 * @param array $groups_ids
	 * @return Thankable[]
	 */
	public function CreateThankablesFromGroupIds(array $groups_ids): array
	{
		foreach ($groups_ids as $groups_id)
		{
			if (!is_int($groups_id))
			{
				throw new InvalidArgumentException("Failed to Create Thankables from Groups, invalid Group ID provided");
			}
		}

		$result = $this->db->query("SELECT groupid, groupname, ex_area_id FROM groups WHERE groupid IN in:int:groups ORDER BY groupid", $groups_ids);

		$group_thankables = [];
		while ($group = $result->fetchArray())
		{
			$id                    = (int) $group['groupid'];
			$group_thankables[$id] = new Thankable($group['groupname'], PERM_OCLASS_GROUP, $id, (int) $group['ex_area_id']);
		}

		return $group_thankables;
	}

	/**
	 * @param array $o_classes
	 * @return Thankable[]
	 * @throws ThankYouOClass - If one or more of the Owner Classes given is not supported.
	 */
	public function CreateThankablesFromOClasses(array $o_classes): array
	{
		//TODO: Expand accepted objects to include all PERM_OCLASS_*

		$supported_o_class_ids = [PERM_OCLASS_INDIVIDUAL, PERM_OCLASS_GROUP];

		$o_classes_object_ids = [];
		foreach ($o_classes as $o_class)
		{
			if (!isset($o_class['oclass']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object Class not specified");
			}

			if (!in_array($o_class['oclass'], $supported_o_class_ids))
			{
				throw new ThankYouOClass("Failed to Get Permission Object Classes Names, Object class is not supported");
			}

			if (!isset($o_class['id']) || !is_int($o_class['id']))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object ID is not specified or is invalid");
			}

			if (!isset($o_classes_object_ids[$o_class['oclass']]))
			{
				$o_classes_object_ids[$o_class['oclass']] = [];
			}

			$o_classes_object_ids[$o_class['oclass']][] = $o_class['id'];
		}

		$thankables = [];
		if (isset($o_classes_object_ids[PERM_OCLASS_GROUP]))
		{
			$thankables = array_merge($thankables, $this->CreateThankablesFromGroupIds($o_classes_object_ids[PERM_OCLASS_GROUP]));
		}

		if (isset($o_classes_object_ids[PERM_OCLASS_INDIVIDUAL]))
		{
			$thankables = array_merge($thankables, $this->CreateThankablesFromUserIds($o_classes_object_ids[PERM_OCLASS_INDIVIDUAL]));
		}

		return $thankables;
	}

	/**
	 * @param array $user_ids
	 * @return Thankable[]
	 */
	public function CreateThankablesFromUserIds(array $user_ids)
	{
		try
		{
			return $this->CreateThankablesFromUsers($this->GetUsers($user_ids));
		} catch (ThankYouException $exception)
		{
			throw new LogicException("Unexpected Exception thrown by CreateThankablesFromUsers in CreateThankablesFromUserIds", null, $exception);
		}
	}

	/**
	 * Creates an array of Thankables from an array of Users. Retains indexes.
	 *
	 * @param array $users
	 * @return Thankable[]
	 * @throws ThankYouException - If the Users given have not been loaded.
	 */
	public function CreateThankablesFromUsers(array $users)
	{
		foreach ($users as $user_offset => $user)
		{
			if (!($user instanceof User))
			{
				throw new InvalidArgumentException("Failed to Create Thankables From Users, invalid object passed");
			}

			if (!$user->IsLoaded())
			{
				throw new ThankYouException("Failed to Create Thankables From Users, one or more Users are not loaded");
			}

			try
			{
				$user_image_url = User::GetPhotoUrl($user->GetId());//TODO: Replace with a non-static post People API update
			} catch (CDNSystemException $cdn_system_exception)
			{
				$this->logger->error("Failed to Get User's Photo URL when Creating Thankable: " . $cdn_system_exception->getMessage());
				$user_image_url = null;
			}

			$user_profile_url = User::GetProfileUrl($user->GetId(), false);//TODO: Replace with a non-static post People API update

			$users[$user_offset] = new Thankable($user->GetFullname(), PermOClass::INDIVIDUAL, $user->GetId(), $user->GetExAreaId(), $user_image_url, $user_profile_url);
		}

		return $users;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
	 */
	public function GetUsers(array $user_ids): array
	{
		$users_list_provider = new UsersListProvider();
		$users_list_provider->SetFilterProtectExtranets(false);
		$users_list_provider->SetFilterIds($user_ids);
		try
		{
			return $users_list_provider->GetListObjects();
		} catch (InvalidFieldIsNotSingle $invalid_field_is_not_single)
		{
			throw new LogicException("Unexpected InvalidFieldIsNotSingle Exception throw by UserListProvider, GetListObjects", null, $invalid_field_is_not_single);
		}
	}

	/**
	 * @param ThankYou $thank_you
	 * @return int ID of saved Thank You
	 * @throws ThankYouNotFound
	 * @throws ThankYouRepository - On failure to save to database.
	 */
	public function SaveToDb(ThankYou $thank_you)
		//TODO : Rename to Save
	{
		$thanks_item = $this->thanks_item_factory->Create();

		$id = $thank_you->GetId();
		if (isset($id) && !$thanks_item->Load($id))
		{
			throw new ThankYouNotFound("Failed to Update Thank You, Thank You not found");
		}

		$thanks_item->SetAuthor($thank_you->GetAuthor()->GetId());

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone(new DateTimeZone("UTC"));
		$thanks_item->SetDateCreated($date_created->format('YmdHis'));

		$thanks_item->SetDescription($thank_you->GetDescription());

		$thanked_users = $thank_you->GetUsers();
		if (isset($thanked_users))
		{
			$users_ids = [];
			foreach ($thanked_users as $offset => $user)
			{
				$users_ids[] = $user->GetId();
			}
			$thanks_item->SetUsers($users_ids);
		}

		$thanked = $thank_you->GetThankable();
		if (isset($thanked))
		{
			$thankyou_thanked = [];
			foreach ($thanked as $thank)
			{
				$object_type = $thank->GetOwnerClass();
				$object_id   = $thank->GetId();

				if (isset($object_type) && isset($object_id))
				{
					$thankyou_thanked[] = ['object_type' => $object_type, 'object_id' => $object_id];
				}
			}

			$thanks_item->SetThanked($thankyou_thanked);
		}

		return $thanks_item->Save();
	}

	/**
	 * @param ThankYou $thank_you
	 * @throws ThankYouOClass - If one or more of the Owner Classes is not recognised.
	 */
	public function PopulateThankYouUsersFromThankables(ThankYou $thank_you)
	{
		$thankables = $thank_you->GetThankable();

		if (!isset($thankables))
		{
			$thank_you->SetUsers(null);

			return;
		}

		$acl = $this->acl_repository->Get(0, 0);

		foreach ($thankables as $thankable)
		{
			$id        = $thankable->GetId();
			$oclass_id = $thankable->GetOwnerClass();

			if (!isset($id) || !isset($oclass_id))
			{
				continue;
			}

			try
			{
				$acl->Add(0, $oclass_id, $id);
			} catch (InvalidSubjectException $invalid_subject_exception)
			{
				throw new ThankYouOClass("Failed to Populate Thank You's Users, invalid oclass object", null, $invalid_subject_exception);
			}
		}

		$users = $acl->GetIndividualsList(0);

		$users_list_provider = new UsersListProvider();
		$users_list_provider->SetFilterIds($users);

		try
		{
			$users = $users_list_provider->GetListObjects();
		} catch (InvalidFieldIsNotSingle $invalid_field_is_not_single)
		{
			throw new LogicException("Unexpected InvalidFieldIsNotSingle Exception throw by UserListProvider, GetListObjects", null, $invalid_field_is_not_single);
		}

		$thank_you->SetUsers($users);
	}

	/**
	 * Given an array of IDs from the table thankyou_item, returns (ThankYou)s in the same order.
	 * If param $thanked is TRUE, the (ThankYou)s the ThankYou's Thankables will be set.
	 *
	 * @param int[] $ids
	 * @param bool  $thanked
	 * @param bool  $users
	 * @return ThankYou[]
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 * @throws ThankYouNotFound - If one or more Thank Yous could not be found.
	 */
	public function GetThankYous(array $ids, bool $thanked = false, bool $users = false)
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

		$columns = ['thankyou_item.id', 'thankyou_item.author AS author_id', 'thankyou_item.date_created', 'thankyou_item.description'];

		if ($thanked)
		{
			array_push($columns, 'thankyou_thanked.object_type AS thanked_object_type', 'thankyou_thanked.object_id AS thanked_object_id');
		}

		if ($users)
		{
			array_push($columns, 'thankyou_user.user_id AS thanked_user_id');
		}

		$query = "SELECT ";

		$first_column = true;
		foreach ($columns as $column)
		{
			if ($first_column)
			{
				$query        .= $column;
				$first_column = false;
			} else
			{
				$query .= ", " . $column;
			}
		}

		$query .= " FROM thankyou_item";

		if ($thanked)
		{
			$query .= " LEFT JOIN thankyou_thanked ON thankyou_thanked.item_id=thankyou_item.id";
		}

		if ($users)
		{
			$query .= " LEFT JOIN thankyou_user ON thankyou_user.thanks_id=thankyou_item.id";
		}

		$query .= " WHERE thankyou_item.id IN in:int:ids";

		$result = $this->db->query($query, $ids);

		$perm_oclasses  = [PERM_OCLASS_INDIVIDUAL => []];
		$thankyou_items = [];
		while ($row = $result->fetchArray())
		{
			$id           = (int) $row['id'];
			$author_id    = (int) $row['author_id'];
			$date_created = (string) $row['date_created'];

			$perm_oclasses[PERM_OCLASS_INDIVIDUAL][$author_id] = true;

			if (!isset($thankyou_items[$id]))
			{
				$thankyou_items[$id] = ['author_id' => $author_id, 'date_created' => $date_created, 'description' => $row['description']];
			}

			if (isset($row['thanked_object_type']) && isset($row['thanked_object_id']))
			{
				$thanked_object_type = (int) $row['thanked_object_type'];
				$thanked_object_id   = (int) $row['thanked_object_id'];

				if (!isset($thankyou_items[$id]['thanked'][$thanked_object_type][$thanked_object_id]))
				{
					$thankyou_items[$id]['thanked'][$thanked_object_type][$thanked_object_id] = true;
				}

				if (!isset($perm_oclasses[$thanked_object_type][$thanked_object_id]))
				{
					$perm_oclasses[$thanked_object_type][$thanked_object_id] = true;
				}
			}

			if (isset($row['thanked_user_id']))
			{
				$thanked_user_id = (int) $row['thanked_user_id'];

				if (!isset($thankyou_items[$id]['thanked_users'][$thanked_user_id]))
				{
					$thankyou_items[$id]['thanked_users'][$thanked_user_id] = true;
				}

				if (!isset($perm_oclasses[PERM_OCLASS_INDIVIDUAL][$thanked_user_id]))
				{
					$perm_oclasses[PERM_OCLASS_INDIVIDUAL][$thanked_user_id] = true;
				}
			}
		}

		foreach ($perm_oclasses as $object_type_id => $object_type_objects)
		{
			switch ($object_type_id)
			{
				case PERM_OCLASS_INDIVIDUAL:
					$users = $this->GetUsers(array_keys($object_type_objects));
					try
					{
						$perm_oclasses[$object_type_id] = $this->CreateThankablesFromUsers($users);
					} catch (ThankYouException $exception)
					{
						throw new LogicException("Unexpected Exception thrown by CreateThankablesFromUsers in GetThankYous", null, $exception);
					}
					break;
				case PERM_OCLASS_GROUP:
					$perm_oclasses[$object_type_id] = $this->CreateThankablesFromGroupIds(array_keys($object_type_objects));
					break;
				default:
					throw new ThankYouOClass("Unable to create Thankable for Owner Class '" . (string) $object_type_id . "'");
					break;
			}
		}

		$thank_yous = [];
		foreach ($ids as $id)
		{
			if (!isset($thankyou_items[$id]))
			{
				throw new ThankYouNotFound("Failed to Get Thanks Yous, Thank You with ID '" . $id . "' could not be found'");
			}

			try
			{
				$thank_you = $this->Create($users[$thankyou_items[$id]['author_id']], (string) $thankyou_items[$id]['description'], new Date($thankyou_items[$id]['date_created'], new DateTimeZone('UTC')));
			} catch (ThankYouAuthor $exception)
			{
				throw new LogicException("Unexpected Runtime Exception thrown when creating a ThankYou", null, $exception);
			}

			$thank_you->SetId($id);

			if (isset($thankyou_items[$id]['thanked']))
			{
				$thankables = [];
				foreach ($thankyou_items[$id]['thanked'] as $thanked_object_type_id => $thanked_object_ids)
				{
					foreach ($thanked_object_ids as $thanked_object_id => $true)
					{
						$thankables[] = $perm_oclasses[$thanked_object_type_id][$thanked_object_id];
					}
				}
				$thank_you->SetThanked($thankables);
			}

			if (isset($thankyou_items[$id]['thanked_users']))
			{
				$thanked_users = [];
				foreach ($thankyou_items[$id]['thanked_users'] as $user_id => $true)
				{
					$thanked_users[] = $users[$user_id];
				}
				$thank_you->SetUsers($thanked_users);
			}

			$thank_yous[$id] = $thank_you;
		}

		return $thank_yous;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return int[]
	 */
	public function GetRecentThankYousIds(int $limit, int $offset)
	{
		$query = "
			SELECT thankyou_item.id
			FROM thankyou_item
			LEFT JOIN thankyou_thanked ON thankyou_thanked.item_id = thankyou_item.id
			LEFT JOIN users
				ON users.id = thankyou_thanked.object_id
				AND thankyou_thanked.object_type = 1
			LEFT JOIN groups
				ON groups.groupid = thankyou_thanked.object_id
				AND object_type = 3
			GROUP BY thankyou_item.id, thankyou_item.date_created
			ORDER BY thankyou_item.date_created DESC";

		try
		{
			$query = $this->query_factory->GetQuery($query);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown", null, $exception);
		}

		$query->setLimit($limit, $offset);

		$result = $this->db->query($query);

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['id'];
		}

		return $thank_you_ids;
	}

	/**
	 * Returns total number of thanks items in the database
	 *
	 * @return int
	 */
	public function GetTotalThankYousCount(): int
	{
		list($count) = $this->db->query_row("SELECT COUNT(1) FROM thankyou_item");

		return $count;
	}

	/**
	 * Returns total number of Thank Yous associated with a User
	 *
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function GetUsersThankYousCount(int $user_id): int
	{
		list($count) = $this->db->query_row("SELECT COUNT(1) FROM thankyou_user WHERE user_id=int:uid", $user_id);

		return $count;
	}

	/**
	 * @param int $user_id
	 * @param int $limit
	 * @param int $offset
	 * @return int[]
	 */
	public function GetUsersRecentThankYousIdsFromDb(int $user_id, int $limit, int $offset)
	{
		$query = "SELECT thanks_id FROM thankyou_user LEFT JOIN thankyou_item ON thankyou_item.id = thankyou_user.thanks_id WHERE user_id = int:user_id ORDER BY thankyou_item.date_created DESC";

		try
		{
			$query = $this->query_factory->GetQuery($query, $user_id);
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown", null, $exception);
		}

		$query->setLimit($limit, $offset);

		$result = $this->db->query($query);

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['thanks_id'];
		}

		return $thank_you_ids;
	}
}
