<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Comments\CommentsRepository;
use Claromentis\Core\Acl\AclRepository;
use Claromentis\Core\Acl\Exception\InvalidSubjectException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Audit\Audit;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Like\LikesRepository;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\People\InvalidFieldIsNotSingle;
use Claromentis\People\Service\UserExtranetService;
use Claromentis\People\UsersListProvider;
use Claromentis\ThankYou\Comments;
use Claromentis\ThankYou\Exception\ThankYouAuthor;
use Claromentis\ThankYou\Exception\ThankYouException;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\Plugin;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Claromentis\ThankYou\Thankable\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use Date;
use DateTime;
use Exception;
use InvalidArgumentException;
use LogicException;
use NotificationMessage;
use User;

class ThankYous
{
	const IM_TYPE_THANKYOU = 0x1A0;

	private $acl;

	private $acl_repository;

	private $audit;

	private $comments_repository;

	private $config;

	private $extranet_service;

	private $comments_factory;

	private $likes_repository;

	private $line_manager_notifier;

	private $thank_yous_repository;

	private $utility;

	private $tag_api;

	public function __construct(
		Audit $audit,
		LineManagerNotifier $line_manager_notifier,
		ThankYousRepository $thank_yous_repository,
		Config $config,
		ThankYouAcl $acl,
		ThankYouUtility $thank_you_utility,
		CommentsRepository $comments_repository,
		Comments\Factory $comments_factory,
		LikesRepository $likes_repository,
		AclRepository $acl_repository,
		UserExtranetService $user_extranet_service,
		Tag $tag_api
	) {
		$this->acl                   = $acl;
		$this->acl_repository        = $acl_repository;
		$this->audit                 = $audit;
		$this->comments_factory      = $comments_factory;
		$this->comments_repository   = $comments_repository;
		$this->config                = $config;
		$this->extranet_service      = $user_extranet_service;
		$this->likes_repository      = $likes_repository;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->utility               = $thank_you_utility;
		$this->tag_api               = $tag_api;
	}

	/**
	 * @param int  $id
	 * @param bool $thanked
	 * @param bool $users
	 * @param bool $tags
	 * @return ThankYou
	 * @throws ThankYouNotFound - If the Thank You could not be found.
	 */
	public function GetThankYou(int $id, bool $thanked = false, bool $users = false, bool $tags = false): ThankYou
	{
		$thank_yous = $this->GetThankYous([$id], $thanked, $users, $tags);
		if (!isset($thank_yous[$id]))
		{
			throw new ThankYouNotFound("Failed to load Thank You, the Thank You could not be found");
		}

		return $thank_yous[$id];
	}

	/**
	 * Given an array of Thank You IDs, returns the associated objects.
	 *
	 * @param int[] $ids
	 * @param bool      $thanked
	 * @param bool      $users
	 * @param bool      $tags
	 * @return ThankYou[]
	 */
	public function GetThankYous(array $ids, bool $thanked = false, bool $users = false, bool $tags = false)
	{
		$thank_yous = $this->thank_yous_repository->GetThankYous($ids);

		if ($thanked)
		{
			$this->LoadThankYousThankeds($thank_yous);
		}

		if ($users)
		{
			$this->LoadThankYousUsers($thank_yous);
		}

		if ($tags)
		{
			$this->LoadThankYousTags($thank_yous);
		}

		return $thank_yous;
	}

	/**
	 * @param ThankYou[] $thank_yous
	 */
	public function LoadThankYousThankeds(array $thank_yous)
	{
		$ids = [];
		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (isset($id))
			{
				$ids[$id] = true;
			}
		}

		$ids = array_keys($ids);

		$thankeds = $this->thank_yous_repository->GetThankYousThankedsByThankYouIds($ids);

		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (isset($id) && isset($thankeds[$id]))
			{
				$thank_you->SetThanked($thankeds[$id]);
			} else
			{
				$thank_you->SetThanked([]);
			}
		}
	}

	/**
	 * @param ThankYou[] $thank_yous
	 */
	public function LoadThankYousUsers(array $thank_yous)
	{
		$ids = [];
		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (isset($id))
			{
				$ids[$id] = true;
			}
		}

		$ids = array_keys($ids);

		$thank_you_users = $this->thank_yous_repository->GetThankYousUsersByThankYouIds($ids);

		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (isset($id) && isset($thank_you_users[$id]))
			{
				$thank_you->SetUsers($thank_you_users[$id]);
			} else
			{
				$thank_you->SetUsers([]);
			}
		}
	}

	/**
	 * Given an array of ThankYous, loads the ThankYous Tags from the Repository and sets them.
	 *
	 * @param ThankYou[] $thank_yous
	 */
	public function LoadThankYousTags(array $thank_yous)
	{
		$ids = [];
		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (!isset($id))
			{
				throw new InvalidArgumentException("Failed to Load Thank You's Tags, one or more Thank You does not have an ID");
			}
			$ids[$id] = true;
		}

		$ids = array_keys($ids);

		$taggeds_tags = $this->tag_api->GetTaggedsTags($ids, ThankYousRepository::AGGREGATION_ID);

		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (!isset($taggeds_tags[$id]))
			{
				throw new LogicException("Failed to Load Thank Yous Tags, Thank You's Tags missing");
			}

			$thank_you->SetTags($taggeds_tags[$id]);
		}
	}

	/**
	 * @param SecurityContext       $context
	 * @param bool                  $get_thanked
	 * @param bool                  $get_users
	 * @param bool                  $get_tags
	 * @param int                   $limit
	 * @param int                   $offset
	 * @param DateTime[]|int[]|null $date_range
	 * @param int[]|null            $thanked_user_ids
	 * @param int[]|null            $tag_ids
	 * @return ThankYou[]
	 */
	public function GetRecentThankYous(SecurityContext $context, bool $get_thanked = false, bool $get_users = false, bool $get_tags = false, ?int $limit = null, ?int $offset = null, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null)
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		if (isset($date_range))
		{
			$date_range = $this->utility->FormatDateRange($date_range);
		}

		$thank_you_ids = $this->thank_yous_repository->GetRecentThankYousIds($limit, $offset, $extranet_ids, true, $date_range, $thanked_user_ids, $tag_ids);

		return $this->GetThankYous($thank_you_ids, $get_thanked, $get_users, $get_tags);
	}

	/**
	 * @return int[]
	 */
	public function GetThankableObjectTypes(): array
	{
		return $this->thank_yous_repository::THANKABLES;
	}

	/**
	 * Return total number of Thank Yous in the database
	 *
	 * @param SecurityContext       $context
	 * @param DateTime[]|int[]|null $date_range
	 * @param int[]|null            $thanked_user_ids
	 * @param array|null            $tag_ids
	 * @return int
	 */
	public function GetTotalThankYousCount(SecurityContext $context, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		return $this->thank_yous_repository->GetTotalThankYousCount($extranet_ids, true, $date_range, $thanked_user_ids, $tag_ids);
	}

	/**
	 * Returns an array of the total number of Thank Yous associated with a User, indexed by the User's ID.
	 *
	 * @param SecurityContext $context
	 * @param int|null        $limit
	 * @param int|null        $offset
	 * @param int[]|null      $user_ids
	 * @param array|null      $date_range
	 * @param int[]|null      $tag_ids
	 * @return int[]
	 */
	public function GetUsersTotalThankYous(SecurityContext $context, ?int $limit = null, ?int $offset = null, ?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null): array
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		return $this->thank_yous_repository->GetTotalUsersThankYous($limit, $offset, $user_ids, $date_range, $tag_ids, $extranet_ids);
	}

	/**
	 * Return an array of the total number of times a Tag has been used, indexed by the Tag's ID.
	 *
	 * @param SecurityContext $context
	 * @param array|null      $orders
	 * @param int|null        $limit
	 * @param int|null        $offset
	 * @param int[]|null      $thanked_user_ids
	 * @param array|null      $date_range
	 * @param int[]|null      $tag_ids
	 * @return int[]
	 */
	public function GetTagsTotalThankYouUses(SecurityContext $context, ?array $orders = null, ?int $limit = null, ?int $offset = null, ?array $thanked_user_ids = null, ?array $date_range = null, ?array $tag_ids = null): array
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		return $this->thank_yous_repository->GetTagsTotalThankYouUses($orders, $limit, $offset, $extranet_ids, true, $date_range, $thanked_user_ids, $tag_ids);
	}

	/**
	 * Given an array of ThankYous, returns the total likes on each, indexed by their IDs.
	 *
	 * @param ThankYou[] $thank_yous
	 * @return int[]
	 */
	public function GetThankYousLikesCount(array $thank_yous): array
	{
		$thank_yous_likes = [];
		foreach ($thank_yous as $thank_you)
		{
			if (!($thank_you instanceof ThankYou))
			{
				throw new InvalidArgumentException("Failed to Get Thank Yous Likes Count, one or more entities provided is not a Thank You");
			}

			$id = $thank_you->GetId();

			if (!isset($id))
			{
				continue;
			}

			$thank_yous_likes[$id] = (int) $this->likes_repository->GetCount(ThankYousRepository::AGGREGATION_ID, $id);
		}

		return $thank_yous_likes;
	}

	/**
	 * @param SecurityContext $context
	 * @param int[]|null      $user_ids
	 * @param array|null      $date_range
	 * @param int[]|null      $tag_ids
	 * @return int
	 */
	public function GetTotalUsers(SecurityContext $context, ?array $user_ids = null, ?array $date_range = null, ?array $tag_ids = null): int
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		return $this->thank_yous_repository->GetTotalUsers($user_ids, $date_range, $tag_ids, $extranet_ids);
	}

	public function GetTotalTags(SecurityContext $context, ?array $date_range = null, ?array $thanked_user_ids = null, ?array $tag_ids = null): int
	{
		$extranet_ids = $this->GetVisibleExtranetIds($context);

		return $this->thank_yous_repository->GetTotalTags($extranet_ids, true, $date_range, $thanked_user_ids, $tag_ids);
	}

	/**
	 * @param int|int[] $object_types_id
	 * @return string|string[]
	 * @throws ThankYouOClass - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassNamesFromIds(array $object_types_id)
	{
		return $this->utility->GetOwnerClassNamesFromIds($object_types_id);
	}

	/**
	 * Returns an array of Users indexed by their IDs.
	 *
	 * @param int[] $user_ids
	 * @return User[]
	 */
	public function GetUsers(array $user_ids)
	{
		return $this->thank_yous_repository->GetUsers($user_ids);
	}

	/**
	 * Given an array of arrays with offset id = int, oclass = int, returns a distinct list of User IDs.
	 *
	 * @param array $owner_classes
	 * @return int[]
	 */
	public function GetDistinctUserIdsFromOwnerClasses(array $owner_classes)
	{
		$acl = $this->acl_repository->Get(0, 0);

		foreach ($owner_classes as $owner_class_member)
		{
			$id        = $owner_class_member['id'] ?? null;
			$oclass_id = $owner_class_member['oclass'] ?? null;

			if (!isset($id))
			{
				throw new InvalidArgumentException("Failed to Get Distinct User IDs From Owner Classes, one or more Owner Class Members does not have an ID");
			}
			if (!is_int($id) || !($id > 0))
			{
				throw new InvalidArgumentException("Failed to Get Distinct User IDs From Owner Classes, Owner Class Member ID " . (string) $id . " is not a natural number");
			}

			if (!isset($oclass_id))
			{
				throw new InvalidArgumentException("Failed to Get Distinct User IDs From Owner Classes, one or more Owner Class Members does not have an Owner Class ID");
			}
			if (!is_int($oclass_id))
			{
				throw new InvalidArgumentException("Failed to Get Distinct User IDs From Owner Classes, Owner Class ID " . (string) $oclass_id . " is not an integer");
			}

			try
			{
				$acl->Add(0, $oclass_id, $id);
			} catch (InvalidSubjectException $invalid_subject_exception)
			{
				throw new LogicException("Unexpected Exception thrown by Acl method Add", null, $invalid_subject_exception);
			}
		}

		$user_ids = $acl->GetIndividualsList(0);

		foreach ($user_ids as $offset => $user_id)
		{
			$user_ids[$offset] = (int) $user_id;
		}

		return $user_ids;
	}

	/**
	 * Determines which Extranets are visible to a specific Security Context, and returns their IDs.
	 * In the event that all Extranets are visible, null is returned.
	 *
	 * @param SecurityContext $context
	 * @return int[]|null
	 */
	public function GetVisibleExtranetIds(SecurityContext $context)
	{
		if ($context->IsPrimaryExtranet())
		{
			return null;
		} else
		{
			return [(int) $context->GetExtranetAreaId(), (int) $this->extranet_service->GetPrimaryId()];
		}
	}

	/**
	 * @param ThankYou[] $thank_yous
	 */
	public function LoadThankYousComments(array $thank_yous)
	{
		$thank_yous_comments = [];
		foreach ($thank_yous as $thank_you)
		{
			if (!($thank_you instanceof ThankYou))
			{
				throw new InvalidArgumentException("Failed to Load Thank Yous Comments, one or more Thank You given is not a Thank You");
			}

			$id = $thank_you->GetId();

			if (!isset($id))
			{
				continue;
			}

			$thank_yous_comments[$id] = $this->comments_factory->Create($id);

			$thank_you->SetComment($thank_yous_comments[$id]);
		}
		$this->LoadThankYousCommentsTotalComments($thank_yous_comments);
	}

	/**
	 * Sets Thank Yous Comments' Total Comments.
	 *
	 * @param Comments\CommentableThankYou[] $thank_yous_comments
	 * @return array
	 */
	public function LoadThankYousCommentsTotalComments(array $thank_yous_comments): array
	{
		foreach ($thank_yous_comments as $thank_you_comments)
		{
			if (!$this->comments_factory->IsCommentInstance($thank_you_comments))
			{
				throw new InvalidArgumentException("Failed to Load Thank Yous' Comments Total Comments, one ore more entities provided is not a Thank You Comments");
			}

			$comments_total_count = (int) $this->comments_repository->GetCommentsCount($thank_you_comments);
			$thank_you_comments->SetTotalComments($comments_total_count);
		}

		return $thank_yous_comments;
	}

	/**
	 * @param ThankYou $thank_you
	 * @return string
	 */
	public function GetThankYouUrl(ThankYou $thank_you)
	{
		$id = $thank_you->GetId();

		if (!isset($id))
		{
			throw new InvalidArgumentException("Failed to Get Thank You's URL, Thank You's ID is unknown");
		}

		return $this->GetThankYouUrlById($id);
	}

	/**
	 * @param int $id
	 * @return string
	 */
	public function GetThankYouUrlById(int $id)
	{
		return $this->utility->GetThankYouUrl($id);
	}

	/**
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 * @throws ThankYouAuthor - If the Author could not be loaded.
	 */
	public function Create($author, string $description, ?Date $date_created = null)
	{
		return $this->thank_yous_repository->Create($author, $description, $date_created);
	}

	/**
	 * @param int $o_class
	 * @param int $id
	 * @return Thankable
	 * @throws ThankYouOClass - If the Owner Class given is not supported.
	 */
	public function CreateThankableFromOClass(int $o_class, int $id)
	{
		return $this->CreateThankablesFromOClasses([['oclass' => $o_class, 'id' => $id]])[0];
	}

	/**
	 * Takes an array of arrays in the format ['oclass' => int, 'id' => int]
	 * Returns an array of Thanked Objects, retaining indexing.
	 *
	 * @param array $oclasses
	 * @return Thankable[]
	 * @throws ThankYouOClass - If one or more of the Owner Classes given is not supported.
	 */
	public function CreateThankablesFromOClasses(array $oclasses)
	{
		return $this->thank_yous_repository->CreateThankablesFromOClasses($oclasses);
	}

	/**
	 * Save a Thank You to the Repository and generate an Audit. If the Thank You doesn't have an ID, one will be set.
	 *
	 * @param ThankYou $thank_you
	 * @throws ThankYouNotFound - If the Thank You could not be found in the Repository.
	 * @throws TagNotFound If one or more of the Thank You's Tags could not be found in the Repository.
	 */
	public function Save(ThankYou $thank_you)
	{
		$new = ($thank_you->GetId() === null) ? true : false;

		$this->thank_yous_repository->Save($thank_you);

		$id = $thank_you->GetId();

		$tags = $thank_you->GetTags();
		if (isset($tags))
		{
			$this->tag_api->RemoveAllTaggedTags($id, ThankYousRepository::AGGREGATION_ID);
			$this->tag_api->AddTaggedTags($id, ThankYousRepository::AGGREGATION_ID, $tags);
		}

		if ($new)
		{
			$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_create', $id, $thank_you->GetDescription());
		} else
		{
			$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_edit', $id, $thank_you->GetDescription());
		}
	}

	/**
	 * @param SecurityContext $security_context
	 * @param int             $id
	 * @throws ThankYouNotFound - If the Thank You could not be found.
	 * @throws ThankYouForbidden - If the Security Context's User does not have permission.
	 */
	public function Delete(SecurityContext $security_context, int $id)
	{
		$thank_you = $this->GetThankYou($id);

		if (!$this->CanDeleteThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$this->tag_api->RemoveAllTaggedTags($id, ThankYousRepository::AGGREGATION_ID);
		$this->thank_yous_repository->Delete($id);

		$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_delete', $id, $thank_you->GetDescription());
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanDeleteThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanDeleteThankYou($thank_you, $security_context);
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanEditThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanEditThankYou($thank_you, $security_context);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanSeeThankYouAuthor(SecurityContext $security_context, ThankYou $thank_you): bool
	{
		return $this->acl->CanSeeThankYouAuthor($security_context, $thank_you);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param Thankable       $thankable
	 * @return bool
	 */
	public function CanSeeThankableName(SecurityContext $security_context, Thankable $thankable): bool
	{
		return $this->acl->CanSeeThankableName($security_context, $thankable);
	}

	/**
	 * Determines whether a Security Context can view a User's details.
	 *
	 * @param SecurityContext $context
	 * @param User            $user
	 * @return bool
	 */
	public function CanSeeUser(SecurityContext $context, User $user): bool
	{
		return $this->acl->CanSeeUser($context, $user);
	}

	/**
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function IsAdmin(SecurityContext $security_context): bool
	{
		return $this->acl->IsAdmin($security_context);
	}

	/**
	 * Determines whether an Extranet Area is visible. If the second parameter is provided, this will be relative to
	 * that Extranet.
	 *
	 * @param int      $target_extranet_id
	 * @param int|null $viewers_extranet_id
	 * @return bool
	 */
	public function IsExtranetVisible(int $target_extranet_id, ?int $viewers_extranet_id = null): bool
	{
		return $this->acl->IsExtranetVisible($target_extranet_id, $viewers_extranet_id);
	}

	/**
	 * @param ThankYou $thank_you
	 */
	public function PopulateThankYouUsersFromThankables(ThankYou $thank_you)
	{
		$thankables = $thank_you->GetThankable();

		if (!isset($thankables))
		{
			$thank_you->SetUsers(null);

			return;
		}

		$owner_classes = [];
		foreach ($thankables as $thankable)
		{
			$id        = $thankable->GetId();
			$oclass_id = $thankable->GetOwnerClass();

			if (!isset($id) || !isset($oclass_id))
			{
				continue;
			}
			$owner_classes[] = ['oclass' => $oclass_id, 'id' => $id];
		}

		$user_ids = $this->GetDistinctUserIdsFromOwnerClasses($owner_classes);

		$users_list_provider = new UsersListProvider();
		$users_list_provider->SetFilterIds($user_ids);

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
	 * @param ThankYou $thank_you
	 * @throws ThankYouException
	 */
	public function Notify(ThankYou $thank_you)
	{
		$thanked_users = $thank_you->GetUsers();
		if (!isset($thanked_users))
		{
			throw new ThankYouException("Failed to Notify Thanked Users, Thanked Users haven't been set");
		}

		$all_users_ids = [];
		foreach ($thanked_users as $thanked_user)
		{
			$all_users_ids[] = $thanked_user->GetId();
		}

		$description = $thank_you->GetDescription();

		try
		{
			NotificationMessage::AddApplicationPrefix(Plugin::APPLICATION_NAME, Plugin::APPLICATION_NAME);

			$params = [
				'author'              => $thank_you->GetAuthor()->GetFullName(),
				'other_people_number' => count($all_users_ids) - 1,
				'description'         => $description
			];
			NotificationMessage::Send('thankyou.new_thanks', $params, $all_users_ids, self::IM_TYPE_THANKYOU);

			if ($this->config->Get('notify_line_manager'))
			{
				$thankables = $thank_you->GetThankable();
				if (!isset($thankables))
				{
					throw new ThankYouException("Failed to Notify Thanked User's Line Managers");
				}

				$user_ids = [];
				foreach ($thankables as $thankable)
				{
					$owner_class_id = $thankable->GetOwnerClass();
					$thanked_id     = $thankable->GetId();
					if ($owner_class_id === PermOClass::INDIVIDUAL && isset($id))
					{
						$user_ids[] = $thanked_id;
					}
				}
				$this->line_manager_notifier->SendMessage($description, $user_ids);
			}
		} catch (ThankYouException $exception)
		{
			throw $exception;
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by NotificationMessage library", null, $exception);
		}
	}
}
