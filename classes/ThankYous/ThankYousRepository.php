<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\AclRepository;
use Claromentis\Core\Acl\Exception\InvalidSubjectException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\DAL\Interfaces\DbInterface;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Exception\ThankYouInvalidAuthor;
use Claromentis\ThankYou\Exception\ThankYouInvalidThankable;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\ThanksItemFactory;
use Date;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use User;

class ThankYousRepository
{
	//TODO: Link SUGRE options to this const.
	const THANKABLES = [PERM_OCLASS_INDIVIDUAL, PERM_OCLASS_GROUP];

	private $db;

	private $acl_repository;

	private $thanks_item_factory;

	private $thank_you_factory;

	public function __construct(ThankYouFactory $thank_you_factory, ThanksItemFactory $thanks_item_factory, AclRepository $acl_repository, DbInterface $db_interface)
	{
		$this->acl_repository      = $acl_repository;
		$this->db                  = $db_interface;
		$this->thanks_item_factory = $thanks_item_factory;
		$this->thank_you_factory   = $thank_you_factory;
	}

	/**
	 * Create a Thank you with
	 *
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 * @throws ThankYouInvalidAuthor
	 * @throws LogicException
	 * @throws ThankYouRuntimeException
	 */
	public function Create($author, string $description, $date_created = null)
	{
		if (is_int($author))
		{
			$author = new User($author);
		}

		if (!($author instanceof User))
		{
			throw new ThankYouInvalidAuthor("Failed to Create Thank You, invalid Author");
		}

		if (!$author->IsLoaded())
		{
			$author->Load();
		}

		if (!isset($date_created))
		{
			$date_created = new Date();
		}

		return $this->thank_you_factory->Create($author, $date_created, $description);
	}

	/**
	 * @param int $id
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function DeleteFromDb(int $id)
	{
		$thanks_item = $this->thanks_item_factory->Create();
		$thanks_item->SetId($id);
		try
		{
			$thanks_item->Delete();
		} catch (ThankYouRuntimeException $thank_you_runtime_exception)
		{
			throw new LogicException("Unexpected ThankYouRuntimeException thrown by ThanksItem:Delete", null, $thank_you_runtime_exception);
		}
	}

	/**
	 * Create an array of Thankables from an array of Group IDs. The returned array is indexed by the Group's ID
	 *
	 * @param array $groups_ids
	 * @return Thankable[]
	 * @throws InvalidArgumentException
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
	 * @throws InvalidArgumentException
	 * @throws ThankYouInvalidUsers
	 */
	public function CreateThankablesFromOClasses(array $o_classes)
	{
		//TODO: Expand accepted objects to include all PERM_OCLASS_*

		$supported_o_class_ids = [PERM_OCLASS_INDIVIDUAL, PERM_OCLASS_GROUP];

		$o_classes_object_ids = [];
		foreach ($o_classes as $o_class)
		{
			if (!isset($o_class['oclass']) || !in_array($o_class['oclass'], $supported_o_class_ids))
			{
				throw new InvalidArgumentException("Failed to Get Permission Object Classes Names, Object Class not specified or is not supported");
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
	 * @throws LogicException
	 * @throws ThankYouInvalidUsers
	 */
	public function CreateThankablesFromUserIds(array $user_ids)
	{
		return $this->CreateThankablesFromUsers($this->GetUsers($user_ids));
	}

	/**
	 * Creates an array of Thankables from an array of Users. Retains indexes.
	 *
	 * @param array $users
	 * @return Thankable[]
	 * @throws ThankYouInvalidUsers
	 */
	public function CreateThankablesFromUsers(array $users)
	{
		foreach ($users as $user_offset => $user)
		{
			if (!($user instanceof User) || !$user->IsLoaded())
			{
				throw new ThankYouInvalidUsers("Failed to Create Thankables From Users, invalid object passed");
			}

			$user_image_url = null;
			try
			{
				$user_image_url = User::GetPhotoUrl($user->GetId());//TODO: Replace with a non-static post People API update
			} catch (CDNSystemException $cdn_system_exception)
			{
				//TODO: add logging.
			}

			$user_profile_url = User::GetProfileUrl($user->GetId(), false);//TODO: Replace with a non-static post People API update

			$users[$user_offset] = new Thankable($user->GetFullname(), PERM_OCLASS_INDIVIDUAL, $user->GetId(), $user->GetExAreaId(), $user_image_url, $user_profile_url);
		}

		return $users;
	}

	/**
	 * @param int[] $ids
	 * @return string[]
	 * @throws ThankYouRuntimeException
	 */
	public function GetThankableObjectTypesNamesFromIds(array $ids): array
	{
		$names = [];
		foreach ($ids as $offset => $id)
		{
			if (!is_int($id))
			{
				throw new ThankYouRuntimeException("Failed to Get Thankable Object Type's Name From ID, non-integer value given");
			}
			$names[$offset] = PermOClass::GetName($id);
			if (!is_string($names[$offset]))
			{
				throw new ThankYouRuntimeException("Failed to Get Thankable Object Type's Name From ID, oClass did not return string");
			}
		}

		return $names;
	}

	/**
	 * Returns an array of Users indexed by their ID.
	 *
	 * @param array $user_ids
	 * @return User[]
	 * @throws LogicException
	 */
	public function GetUsers(array $user_ids)
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
	 * @throws ThankYouInvalidUsers
	 * @throws ThankYouNotFound
	 */
	public function SaveToDb(ThankYou $thank_you)
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

		$thanked = $thank_you->GetThanked();
		if (isset($thanked))
		{
			$thankyou_thanked = [];
			foreach ($thanked as $thank)
			{
				$object_type = $thank->GetObjectTypeId();
				$object_id   = $thank->GetId();

				if (isset($object_type) && isset($object_id))
				{
					$thankyou_thanked[] = ['object_type' => $object_type, 'object_id' => $object_id];
				}
			}
			$thanks_item->SetThanked($thankyou_thanked);
		}

		$thanks_item->Save();
	}

	public function PopulateThankYouUsersFromThankables(ThankYou $thank_you)
	{
		$thankables = $thank_you->GetThanked();

		if (!isset($thankables))
		{
			$thank_you->SetThanked(null);

			return;
		}

		$acl = $this->acl_repository->Get(0, 0);

		foreach ($thankables as $thankable)
		{
			$id        = $thankable->GetId();
			$oclass_id = $thankable->GetObjectTypeId();

			if (!isset($id) || !isset($oclass_id))
			{
				continue;
			}

			try
			{
				$acl->Add(0, $oclass_id, $id);
			} catch (InvalidSubjectException $invalid_subject_exception)
			{
				throw new ThankYouRuntimeException("Failed to Populate Thank You's Users, invalid oclass object", null, $invalid_subject_exception);
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
	 * @return ThankYou[]
	 * @throws ThankYouRuntimeException
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function GetThankYous(array $ids, bool $thanked = false)
	{
		if (count($ids) === 0)
		{
			return [];
		}

		foreach ($ids as $id)
		{
			if (!is_int($id))
			{
				throw new ThankYouRuntimeException("Failed to Get Thank Yous, invalid ID given");
			}
		}

		if ($thanked === false)
		{
			$query = "SELECT thankyou_item.id, thankyou_item.author AS author_id,thankyou_item.date_created, thankyou_item.description FROM thankyou_item LEFT JOIN users AS author ON author.id=thankyou_item.author WHERE thankyou_item.id IN in:int:ids";
		} else
		{
			$query = "SELECT thankyou_item.id, thankyou_item.author AS author_id,thankyou_item.date_created, thankyou_item.description, thankyou_thanked.object_type AS thanked_object_type, thankyou_thanked.object_id AS thanked_object_id FROM thankyou_item LEFT JOIN users AS author ON author.id=thankyou_item.author LEFT JOIN thankyou_thanked ON thankyou_thanked.item_id=thankyou_item.id WHERE thankyou_item.id IN in:int:ids";
		}
		$result = $this->db->query($query, $ids);

		$perm_oclasses  = [PERM_OCLASS_INDIVIDUAL => []];
		$thankyou_items = [];
		while ($row = $result->fetchArray())
		{
			$id           = (int) $row['id'];
			$author_id    = (int) $row['author_id'];
			$date_created = (string) $row['date_created'];

			$perm_oclasses[PERM_OCLASS_INDIVIDUAL][$author_id] = null;

			if (!isset($thankyou_items[$id]))
			{
				$thankyou_items[$id] = ['author_id' => $author_id, 'date_created' => $date_created, 'description' => $row['description']];
			}

			if ($thanked === true)
			{
				if (!isset($row['thanked_object_type']) || !isset($row['thanked_object_id']))
				{
					continue;
				}

				$thanked_object_type = (int) $row['thanked_object_type'];
				$thanked_object_id   = (int) $row['thanked_object_id'];

				if (!isset($thankyou_items[$id]['thankyou_thanked']))
				{
					$thankyou_items[$id]['thankyou_thanked'] = [];
				}
				$thankyou_items[$id]['thankyou_thanked'][] = ['object_type' => $thanked_object_type, 'object_id' => $thanked_object_id];

				if (!isset($perm_oclasses[$thanked_object_type]))
				{
					$perm_oclasses[$thanked_object_type] = [];
				}

				$perm_oclasses[$thanked_object_type][$thanked_object_id] = null;
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
					} catch (ThankYouRuntimeException $thank_you_runtime_exception)
					{
						throw new LogicException("Unexpected ThankYouRuntimeException thrown by CreateThankablesFromUsers", null, $thank_you_runtime_exception);
					}
					break;
				case PERM_OCLASS_GROUP:
					try
					{
						$perm_oclasses[$object_type_id] = $this->CreateThankablesFromGroupIds(array_keys($object_type_objects));
					} catch (ThankYouRuntimeException $thank_you_runtime_exception)
					{
						throw new LogicException("Unexpected ThankYouRuntimeException thrown by CreateThankablesFromUsers", null, $thank_you_runtime_exception);
					}
					break;
				default:
					throw new ThankYouInvalidThankable("Unable to create Thankable for Permission OClass '" . (string) $object_type_id . "'");
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
				$thank_you = $this->Create($users[$thankyou_items[$id]['author_id']], $thankyou_items[$id]['description'], new Date($thankyou_items[$id]['date_created'], new DateTimeZone('UTC')));
				$thank_you->SetId($id);

				if (isset($thankyou_items[$id]['thankyou_thanked']))
				{
					$thankables = [];
					foreach ($thankyou_items[$id]['thankyou_thanked'] as $thanked)
					{
						$thankables[] = $perm_oclasses[$thanked['object_type']][$thanked['object_id']];
					}
					$thank_you->SetThanked($thankables);
				}
			} catch (ThankYouRuntimeException $thank_you_runtime_exception)
			{
				throw new LogicException("Failed to Get Thank Yous, unexpected Runtime Exception thrown when creating a ThankYou", null, $thank_you_runtime_exception);
			}

			$thank_yous[$id] = $thank_you;
		}

		return $thank_yous;
	}

	/**
	 * @param int      $limit
	 * @param int      $offset
	 * @param int|null $extranet_area_id
	 * @return int[]
	 */
	public function GetRecentThankYousIdsFromDb(int $limit, int $offset, ?int $extranet_area_id = null)
	{
		$query = "SELECT DISTINCT(thankyou_item.id) FROM thankyou_item LEFT JOIN thankyou_thanked ON thankyou_thanked.item_id = thankyou_item.id LEFT JOIN users ON users.id = thankyou_thanked.object_id AND thankyou_thanked.object_type = 1 LEFT JOIN groups ON groups.groupid = thankyou_thanked.object_id AND object_type = 3";
		if (isset($extranet_area_id))
		{
			$query .= " WHERE groups.ex_area_id = " . $extranet_area_id . " OR users.ex_area_id = " . $extranet_area_id;
		}
		$query .= " ORDER BY thankyou_item.date_created DESC LIMIT int:limit OFFSET int:offset";

		$result = $this->db->query($query, $limit, $offset);

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['id'];
		}

		return $thank_you_ids;
	}

	/**
	 * @param int $user_id
	 * @param int $limit
	 * @param int $offset
	 * @return int[]
	 */
	public function GetUsersRecentThankYousIdsFromDb(int $user_id, int $limit, int $offset)
	{
		$query = "SELECT thanks_id FROM thankyou_user LEFT JOIN thankyou_item ON thankyou_item.id = thankyou_user.thanks_id WHERE user_id = int:user_id ORDER BY thankyou_item.date_created DESC LIMIT int:limit OFFSET int:offset";
		$result = $this->db->query($query, $user_id, $limit, $offset);

		$thank_you_ids = [];
		while ($row = $result->fetchArray())
		{
			$thank_you_ids[] = (int) $row['thanks_id'];
		}

		return $thank_you_ids;
	}
}
