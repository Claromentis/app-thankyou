<?php

namespace Claromentis\ThankYou\ThankYous;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Comments\CommentsRepository;
use Claromentis\Core\Acl\AclRepository;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Audit\Audit;
use Claromentis\Core\Like\LikesRepository;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\People\Entity\User;
use Claromentis\People\Repository\UserRepository;
use Claromentis\People\Service\UserExtranetService;
use Claromentis\ThankYou\Comments;
use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Exception\ThankYouForbiddenException;
use Claromentis\ThankYou\Exception\ThankYouNotFoundException;
use Claromentis\ThankYou\Exception\UnsupportedOwnerClassException;
use Claromentis\ThankYou\Exception\ValidationException;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\Plugin;
use Claromentis\ThankYou\Tags;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFoundException;
use Claromentis\ThankYou\Thankable\Thankable;
use DateTime;
use Exception;
use InvalidArgumentException;
use NotificationMessage;
use Psr\Log\LoggerInterface;

class Api
{
	const IM_TYPE_THANKYOU = 0x1A0;

	/**
	 * @var ThankYouAcl
	 */
	private $acl;

	/**
	 * @var AclRepository
	 */
	private $acl_repository;

	/**
	 * @var Audit
	 */
	private $audit;

	/**
	 * @var CommentsRepository
	 */
	private $comments_repository;

	/**
	 * @var Configuration\Api
	 */
	private $config_api;

	/**
	 * @var UserExtranetService
	 */
	private $extranet_service;

	/**
	 * @var Comments\Factory
	 */
	private $comments_factory;

	/**
	 * @var LikesRepository
	 */
	private $likes_repository;

	/**
	 * @var LineManagerNotifier
	 */
	private $line_manager_notifier;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var Tags\Api
	 */
	private $tag_api;

	/**
	 * @var ThankYouFactory
	 */
	private $thank_you_factory;

	/**
	 * @var ThankYousRepository
	 */
	private $thank_yous_repository;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * @var Validator
	 */
	private $validator;

	/**
	 * ThankYous constructor.
	 *
	 * @param Audit               $audit
	 * @param LineManagerNotifier $line_manager_notifier
	 * @param ThankYousRepository $thank_yous_repository
	 * @param ThankYouFactory     $thank_you_factory
	 * @param Configuration\Api   $config_api
	 * @param Lmsg                $lmsg
	 * @param ThankYouAcl         $acl
	 * @param ThankYouUtility     $thank_you_utility
	 * @param Validator           $validator
	 * @param CommentsRepository  $comments_repository
	 * @param Comments\Factory    $comments_factory
	 * @param LikesRepository     $likes_repository
	 * @param AclRepository       $acl_repository
	 * @param UserExtranetService $user_extranet_service
	 * @param UserRepository      $user_repository
	 * @param Tags\Api            $tag_api
	 * @param LoggerInterface     $logger
	 */
	public function __construct(
		Audit $audit,
		LineManagerNotifier $line_manager_notifier,
		ThankYousRepository $thank_yous_repository,
		ThankYouFactory $thank_you_factory,
		Configuration\Api $config_api,
		Lmsg $lmsg,
		ThankYouAcl $acl,
		ThankYouUtility $thank_you_utility,
		Validator $validator,
		CommentsRepository $comments_repository,
		Comments\Factory $comments_factory,
		LikesRepository $likes_repository,
		AclRepository $acl_repository,
		UserExtranetService $user_extranet_service,
		UserRepository $user_repository,
		Tags\Api $tag_api,
		LoggerInterface $logger
	) {
		$this->acl                   = $acl;
		$this->acl_repository        = $acl_repository;
		$this->audit                 = $audit;
		$this->thank_you_factory     = $thank_you_factory;
		$this->comments_factory      = $comments_factory;
		$this->comments_repository   = $comments_repository;
		$this->config_api            = $config_api;
		$this->extranet_service      = $user_extranet_service;
		$this->likes_repository      = $likes_repository;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->lmsg                  = $lmsg;
		$this->logger                = $logger;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->utility               = $thank_you_utility;
		$this->user_repository       = $user_repository;
		$this->validator             = $validator;
		$this->tag_api               = $tag_api;
	}

	/**
	 * @param int  $id
	 * @param bool $thanked
	 * @param bool $users
	 * @param bool $tags
	 * @return ThankYou
	 * @throws ThankYouNotFoundException - If the Thank You could not be found.
	 * @throws MappingException
	 */
	public function GetThankYou(int $id, bool $thanked = false, bool $users = false, bool $tags = false): ThankYou
	{
		$thank_yous = $this->GetThankYous([$id], $thanked, $users, $tags);
		if (!isset($thank_yous[$id]))
		{
			throw new ThankYouNotFoundException("Failed to load Thank You, the Thank You could not be found");
		}

		return $thank_yous[$id];
	}

	/**
	 * Given an array of Thank You IDs, returns the associated objects.
	 *
	 * @param int[] $ids
	 * @param bool  $thanked
	 * @param bool  $users
	 * @param bool  $tags
	 * @return ThankYou[]
	 * @throws MappingException
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
	 * @throws MappingException
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
			if (isset($thankeds[$id]))
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
	 * @throws MappingException
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
			if (isset($thank_you_users[$id]))
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
			if (isset($id))
			{
				$ids[$id] = true;
			}
		}
		$ids = array_keys($ids);

		$taggeds_tags = $this->tag_api->GetTaggablesTags($ids, ThankYousRepository::AGGREGATION_ID);

		foreach ($thank_yous as $thank_you)
		{
			$id = $thank_you->GetId();
			if (isset($taggeds_tags[$id]))
			{
				$thank_you->SetTags($taggeds_tags[$id]);
			} else
			{
				$thank_you->SetTags([]);
			}
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
	 * @throws MappingException
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
	 * Given an array of arrays with offset id = int, oclass = int, returns a distinct list of User IDs.
	 *
	 * @param array $owner_classes
	 * @return int[]
	 */
	public function GetOwnersUserIds(array $owner_classes)
	{
		$acl = $this->acl_repository->Get(0, 0);

		foreach ($owner_classes as $owner_class_member)
		{
			$id        = $owner_class_member['id'] ?? null;
			$oclass_id = $owner_class_member['oclass'] ?? null;

			$acl->Add(0, $oclass_id, $id);
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
				continue;
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
	 */
	public function LoadThankYousCommentsTotalComments(array $thank_yous_comments)
	{
		foreach ($thank_yous_comments as $thank_you_comments)
		{
			if (!$this->comments_factory->IsCommentInstance($thank_you_comments))
			{
				continue;
			}

			$comments_total_count = (int) $this->comments_repository->GetCommentsCount($thank_you_comments);
			$thank_you_comments->SetTotalComments($comments_total_count);
		}
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
	 * Given an array, attempts to create a Thank You and save it to the Repository.
	 * If successful, returns the Thank You's ID.
	 *
	 * @param array $data
	 * @return int
	 * @throws ValidationException - If the Thank You could not be created from the parameter provided.
	 * @throws TagNotFoundException - If one or more Tags could not be found.
	 * @throws MappingException
	 */
	public function CreateAndSave(array $data): int
	{
		$author      = $data['author'] ?? null;
		$description = $data['description'] ?? null;

		$thanked = $data['thanked'] ?? null;
		$tag_ids = $data['tags'] ?? null;

		$errors = [];

		if (!isset($description))
		{
			$errors[] = ['name' => 'description', 'reason' => ($this->lmsg)('thankyou.thankyou.description.error.empty')];
		} elseif (!is_string($description))
		{
			$errors[] = ['name' => 'description', 'reason' => ($this->lmsg)('thankyou.thankyou.description.error.not_string')];
		}

		if (!isset($author))
		{
			$errors[] = ['name' => 'author', 'reason' => ($this->lmsg)('thankyou.thankyou.author.error.undefined')];
		} elseif (!is_int($author) && !($author instanceof User))
		{
			$errors[] = ['name' => 'author', 'reason' => ($this->lmsg)('thankyou.thankyou.author.error.invalid')];
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}

		$thank_you = $this->thank_you_factory->Create($author, $description, null);

		if (isset($thanked))
		{
			$this->SetThankedFromArray($thank_you, $thanked);
		}

		if (isset($tag_ids))
		{
			$this->SetTagsFromArray($thank_you, $tag_ids);
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}

		$this->PopulateThankYouUsersFromThankables($thank_you);

		return $this->SaveNew($thank_you);
	}

	/**
	 * @param SecurityContext $context
	 * @param int             $id
	 * @param array           $data
	 * @return int
	 * @throws TagNotFoundException
	 * @throws ThankYouForbiddenException
	 * @throws ThankYouNotFoundException
	 * @throws ValidationException
	 * @throws MappingException
	 */
	public function UpdateAndSave(SecurityContext $context, int $id, array $data): int
	{
		$description = $data['description'] ?? null;

		$thanked = $data['thanked'] ?? null;
		$tag_ids = $data['tags'] ?? null;

		$errors = [];

		$thank_you = $this->GetThankYou($id);

		if (isset($description))
		{
			if (!is_string($description))
			{
				$errors[] = ['name' => 'description', 'reason' => ($this->lmsg)('thankyou.thankyou.description.error.not_string')];
			} else
			{
				$thank_you->SetDescription($description);
			}
		}

		if (isset($thanked))
		{
			try
			{
				$this->SetThankedFromArray($thank_you, $thanked);
				$this->PopulateThankYouUsersFromThankables($thank_you);
			} catch (ValidationException $exception)
			{
				$errors = array_merge($errors, $exception->GetErrors());
			}
		}

		if (isset($tag_ids))
		{
			try
			{
				$this->SetTagsFromArray($thank_you, $tag_ids);
			} catch (ValidationException $exception)
			{
				$errors = array_merge($errors, $exception->GetErrors());
			}
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}

		return $this->SaveUpdate($context, $thank_you);
	}

	/**
	 * @param int $o_class
	 * @param int $id
	 * @return Thankable
	 * @throws UnsupportedOwnerClassException - If the Owner Class given is not supported.
	 * @throws MappingException
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
	 * @throws UnsupportedOwnerClassException - If one or more of the Owner Classes given is not supported.
	 * @throws MappingException
	 */
	public function CreateThankablesFromOClasses(array $oclasses)
	{
		return $this->thank_yous_repository->CreateThankablesFromOClasses($oclasses);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param int             $id
	 * @throws ThankYouNotFoundException - If the Thank You could not be found.
	 * @throws ThankYouForbiddenException - If the Security Context's User does not have permission.
	 * @throws StorageException - If the Thank You could not be deleted from the repository.
	 * @throws MappingException
	 */
	public function Delete(SecurityContext $security_context, int $id)
	{
		$thank_you = $this->GetThankYou($id);

		if (!$this->CanDeleteThankYou($security_context, $thank_you))
		{
			throw new ThankYouForbiddenException("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$this->tag_api->RemoveAllTaggableTaggings($id, ThankYousRepository::AGGREGATION_ID);
		$this->thank_yous_repository->Delete($id);

		$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_delete', $id, $thank_you->GetDescription());
	}

	/**
	 * @param SecurityContext $security_context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanDeleteThankYou(SecurityContext $security_context, ThankYou $thank_you)
	{
		return $this->acl->CanDeleteThankYou($security_context, $thank_you);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanEditThankYou(SecurityContext $security_context, ThankYou $thank_you)
	{
		return $this->acl->CanEditThankYou($security_context, $thank_you);
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
		return $this->acl->CanSeeThankedName($security_context, $thankable);
	}

	/**
	 * Determines whether a Security Context can view a User's details.
	 *
	 * @param SecurityContext $context
	 * @param User            $user
	 * @return bool
	 */
	public function CanSeeThankedUser(SecurityContext $context, User $user): bool
	{
		return $this->acl->CanSeeThankedUser($context, $user);
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
	 * @param ThankYou $thank_you
	 * @throws MappingException
	 */
	public function PopulateThankYouUsersFromThankables(ThankYou $thank_you)
	{
		$thankables = $thank_you->GetThankables();

		if (!isset($thankables))
		{
			$thank_you->SetUsers(null);

			return;
		}

		$owner_classes = [];
		foreach ($thankables as $thankable)
		{
			$id        = $thankable->GetItemId();
			$oclass_id = $thankable->GetOwnerClass();

			if (!isset($id) || !isset($oclass_id))
			{
				continue;
			}
			$owner_classes[] = ['oclass' => $oclass_id, 'id' => $id];
		}

		$user_ids = $this->GetOwnersUserIds($owner_classes);

		$users_entity_collection = $this->user_repository->find($user_ids);

		$thank_you->SetUsers($users_entity_collection->getDictionary());
	}

	/**
	 * Save a new Thank You to the Repository and generate an Audit. The Thank You's ID will also be set.
	 *
	 * @param ThankYou $thank_you
	 * @return int
	 * @throws TagNotFoundException - If one or more of the Thank You's Tags could not be found in the Repository.
	 * @throws ValidationException - If the Thank You is not in a Valid state to be saved.
	 * @throws MappingException
	 */
	private function SaveNew(ThankYou $thank_you): int
	{
		$thank_you->SetId(null);

		$this->validator->ValidateThankYou($thank_you);
		$this->thank_yous_repository->Save($thank_you);

		$id = $thank_you->GetId();

		$tags = $thank_you->GetTags();
		if (isset($tags))
		{
			$this->tag_api->RemoveAllTaggableTaggings($id, ThankYousRepository::AGGREGATION_ID);
			$this->tag_api->AddTaggings($id, ThankYousRepository::AGGREGATION_ID, $tags);
		}

		$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_create', $id, $thank_you->GetDescription());
		$this->Notify($thank_you);

		return $id;
	}

	/**
	 * Updates a Thank You in the Repository and generates an Audit.
	 * Returns the Thank You's ID.
	 *
	 * @param SecurityContext $security_context
	 * @param ThankYou        $thank_you
	 * @return int
	 * @throws TagNotFoundException
	 * @throws ThankYouForbiddenException
	 * @throws ValidationException
	 * @throws ThankYouNotFoundException - If the Thank You does not have an ID set.
	 * @throws MappingException
	 */
	private function SaveUpdate(SecurityContext $security_context, ThankYou $thank_you): int
	{
		$id = $thank_you->GetId();

		if (!isset($id))
		{
			throw new ThankYouNotFoundException("Failed to Update Thank You, ID undefined");
		}

		if (!$this->CanEditThankYou($security_context, $thank_you))
		{
			throw new ThankYouForbiddenException("The given User does not have Permission to Edit this Thank You");
		}

		$this->validator->ValidateThankYou($thank_you);

		$this->thank_yous_repository->Save($thank_you);

		$tags = $thank_you->GetTags();
		if (isset($tags))
		{
			$this->tag_api->RemoveAllTaggableTaggings($id, ThankYousRepository::AGGREGATION_ID);
			$this->tag_api->AddTaggings($id, ThankYousRepository::AGGREGATION_ID, $tags);
		}

		$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'thank_you_edit', $id, $thank_you->GetDescription());

		return $id;
	}

	/**
	 * Given a Thank You, sends Notifications to its Thanked Users. Depending on Configuration, it may also send
	 * Notifications to the Users' Line Managers.
	 *
	 * @param ThankYou $thank_you
	 */
	private function Notify(ThankYou $thank_you)
	{
		$thanked_users = $thank_you->GetUsers();
		if (!isset($thanked_users))
		{
			return;
		}

		$all_users_ids = [];
		foreach ($thanked_users as $thanked_user)
		{
			$all_users_ids[] = $thanked_user->id;
		}

		$description = $thank_you->GetDescription();

		try
		{
			NotificationMessage::AddApplicationPrefix(Plugin::APPLICATION_NAME, Plugin::APPLICATION_NAME);

			$params = [
				'author'              => $thank_you->GetAuthor()->getFullnameAttribute(),
				'other_people_number' => count($all_users_ids) - 1,
				'description'         => $description
			];
			NotificationMessage::Send('thankyou.new_thanks', $params, $all_users_ids, self::IM_TYPE_THANKYOU);

			if ($this->config_api->IsLineManagerNotificationEnabled())
			{
				$thankables = $thank_you->GetThankables();
				if (!isset($thankables))
				{
					return;
				}

				$user_ids = [];
				foreach ($thankables as $thankable)
				{
					$owner_class_id = $thankable->GetOwnerClass();
					$thanked_id     = $thankable->GetItemId();
					if ($owner_class_id === PermOClass::INDIVIDUAL && isset($id))
					{
						$user_ids[] = $thanked_id;
					}
				}
				$this->line_manager_notifier->SendMessage($description, $user_ids);
			}
		} catch (Exception $exception)
		{
			$this->logger->error("Failed to send Thank You Notifications", [$exception]);
		}
	}

	/**
	 * Given a Thank You and an array, tries to populate the Thank You's Thanked.
	 *
	 * @param ThankYou $thank_you
	 * @param array    $thanked
	 * @throws ValidationException - If the Thank You's Thanked could not be set from the given array.
	 * @throws MappingException
	 */
	private function SetThankedFromArray(ThankYou $thank_you, array $thanked)
	{
		if (!is_array($thanked))
		{
			$errors[] = ['name' => 'thanked', 'reason' => ($this->lmsg)('thankyou.thanked.error.invalid')];
		} else
		{
			foreach ($thanked as $offset => $oclass)
			{
				$thanked[$offset] = ['oclass' => (int) ($oclass['oclass'] ?? null), 'id' => (int) ($oclass['id'] ?? null)];
			}
			try
			{
				$thankables = $this->CreateThankablesFromOClasses($thanked);
				$thank_you->SetThanked($thankables);
			} catch (UnsupportedOwnerClassException $exception)
			{
				$errors[] = [
					'name'   => 'thanked',
					'reason' => ($this->lmsg)('thankyou.thankable.owner_class.error.not_supported',
						implode(', ', $this->GetThankableObjectTypes())
					)
				];
			}
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}
	}

	/**
	 * Given a Thank You and array of Tag IDs, tries to set the Thank You's Tags.
	 *
	 * @param ThankYou $thank_you
	 * @param array    $tag_ids
	 * @throws ValidationException - If the Thank You's Tags could not be set.
	 */
	private function SetTagsFromArray(ThankYou $thank_you, array $tag_ids)
	{
		foreach ($tag_ids as $offset => $tag_id)
		{
			if (!is_int($tag_id))
			{
				$errors[] = [
					'name'   => 'tags',
					'reason' => ($this->lmsg)('thankyou.tag.error.id.invalid', (string) $tag_id)
				];
				unset($tag_ids[$offset]);
			}
		}
		$tags = $this->tag_api->GetTagsById($tag_ids);
		foreach ($tag_ids as $tag_id)
		{
			if (!isset($tags[$tag_id]))
			{
				$errors[] = [
					'name'   => 'tags',
					'reason' => ($this->lmsg)('thankyou.tag.error.id.not_found', $tag_id)
				];
			}
		}

		if (!empty($errors))
		{
			throw new ValidationException($errors);
		}

		$thank_you->SetTags($tags);
	}
}
