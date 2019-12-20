<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Audit\Audit;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Plugin;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagForbidden;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Date;
use InvalidArgumentException;
use LogicException;
use User;

class Api
{
	/**
	 * @var TagAcl $acl
	 */
	private $acl;

	/**
	 * @var Audit $audit
	 */
	private $audit;

	/**
	 * @var TagFactory $factory
	 */
	private $factory;

	/**
	 * @var TagRepository $repository
	 */
	private $repository;

	/**
	 * Api constructor.
	 *
	 * @param Audit         $audit
	 * @param TagRepository $tag_repository
	 * @param TagFactory    $tag_factory
	 * @param TagAcl        $tag_acl
	 */
	public function __construct(Audit $audit, TagRepository $tag_repository, TagFactory $tag_factory, TagAcl $tag_acl)
	{
		$this->acl        = $tag_acl;
		$this->audit      = $audit;
		$this->factory    = $tag_factory;
		$this->repository = $tag_repository;
	}

	/**
	 * @param int $id
	 * @return Tag
	 * @throws TagNotFound - If the Tag could not be found in the Repository.
	 */
	public function GetTag(int $id): Tag
	{
		try
		{
			$tags = $this->repository->GetTags([$id]);
		} catch (InvalidArgumentException $exception)
		{
			throw new LogicException("Failed to Get Tag, unexpected Exception thrown when Loading Tag", null, $exception);
		}

		if (!isset($tags[$id]))
		{
			throw new TagNotFound("Failed to Get Tag, Tag with ID '" . $id . "' could not be found");
		}

		return $tags[$id];
	}

	/**
	 * @param int|null    $limit
	 * @param int|null    $offset
	 * @param string|null $name
	 * @param array|null  $orders
	 * @return Tag[]
	 */
	public function GetTags(?int $limit = null, ?int $offset = null, ?string $name = null, ?array $orders = null): array
	{
		return $this->repository->GetFilteredTags($limit, $offset, $name, $orders);
	}

	/**
	 * Returns an array of Tags indexed by their ID.
	 *
	 * @param int[] $ids
	 * @return Tag[]
	 */
	public function GetTagsById(array $ids): array
	{
		return $this->repository->GetTags($ids);
	}

	/**
	 * Given a Tagged ID and Aggregation ID, returns an array of Tags, indexed by the Tagging's ID.
	 *
	 * @param int $tagged_id
	 * @param int $aggregation_id
	 * @return Tag[]
	 */
	public function GetTaggedTags(int $tagged_id, int $aggregation_id): array
	{
		return $this->GetTaggedsTags([$tagged_id], $aggregation_id)[$tagged_id];
	}

	/**
	 * Given an Aggregation ID, and Tagged IDs, returns an array of Taggings, indexed by the Tagged's ID.
	 *
	 * @param int[] $tagged_ids
	 * @param int   $aggregation_id
	 * @return array[]
	 */
	public function GetTaggedsTags(array $tagged_ids, int $aggregation_id): array
	{
		$taggeds_tags = $this->repository->GetTaggedsTags($tagged_ids, $aggregation_id);
		foreach ($tagged_ids as $tagged_id)
		{
			if (!isset($taggeds_tags[$tagged_id]))
			{
				$taggeds_tags[$tagged_id] = [];
			}
		}

		return $taggeds_tags;
	}

	/**
	 * Returns the total number of Tags in the Repository.
	 *
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return $this->repository->GetTotalTags();
	}

	/**
	 * Filters for Tags and returns an array of the number of times they've been used for Tagging, indexed by their IDs.
	 *
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @param bool|null  $active
	 * @param array|null $orders
	 * @return array
	 */
	public function GetTagsTaggedTotals(?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $orders = null): array
	{
		return $this->repository->GetTagsTaggedTotals($limit, $offset, $active, $orders);
	}

	/**
	 * Given an array of Tag IDs,
	 * returns the number of times the Tags have been used for Tagging, indexed by the Tag's IDs.
	 *
	 * @param int[] $ids
	 * @return array
	 */
	public function GetTagsTaggedTotalsFromIds(array $ids): array
	{
		return $this->repository->GetTagsTaggedTotalsFromIds($ids);
	}

	/**
	 * Creates a Tag with defaults. Used for creating Tags not in the Repository.
	 *
	 * @param User   $user
	 * @param string $name
	 * @return Tag
	 * @throws TagInvalidNameException
	 */
	public function Create(User $user, string $name): Tag
	{
		$tag = $this->factory->Create($name);
		$tag->SetCreatedBy($user);
		$tag->SetCreatedDate(new Date());
		$tag->SetModifiedBy($user);
		$tag->SetModifiedDate(new Date());

		return $tag;
	}

	/**
	 * Saves a Tag to the Repository. If the Tag does not have an ID, one will be set.
	 *
	 * @param Tag $tag
	 * @throws TagDuplicateNameException - If the Tag's Name is not unique to the Repository.
	 */
	public function Save(Tag $tag)
	{
		$new = ($tag->GetId() === null);

		$this->repository->Save($tag);

		$id   = $tag->GetId();
		$name = $tag->GetName();

		if ($new)
		{
			$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_create', $id, $name);
		} else
		{
			$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_edit', $id, $name);
		}
	}

	/**
	 * @param int             $id
	 * @param SecurityContext $context
	 * @throws TagForbidden - If the SecurityContext does not allow the Tag to be deleted.
	 * @throws TagNotFound - If the Tag cannot be found in the Repository.
	 */
	public function Delete(int $id, SecurityContext $context)
	{
		if (!$this->acl->CanDeleteTag($context))
		{
			throw new TagForbidden("User does not have Permission to Delete a Tag");
		}

		$tag = $this->GetTag($id);

		$this->repository->Delete($id);

		$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_delete', $id, $tag->GetName());
	}

	/**
	 * Saves a Tagged's Tag to the Repository.
	 * Returns the ID of the Tagging.
	 *
	 * @param int $tagged_id
	 * @param int $aggregation_id
	 * @param Tag $tag
	 * @return int
	 * @throws TagNotFound If the Tag could not be found in the Repository.
	 */
	public function AddTaggedTag(int $tagged_id, int $aggregation_id, Tag $tag): int
	{
		$tag_id = $tag->GetId();

		if (!isset($tag_id))
		{
			throw new InvalidArgumentException("Failed to Add Tagged's Tag, Tag's ID unknown");
		}

		return $this->repository->SaveTaggedTag($tagged_id, $aggregation_id, $tag_id);
	}

	/**
	 * Saves a Tagged's Tags to the Repository.
	 * Returns an array of the Tagging IDs.
	 *
	 * @param int   $tagged_id
	 * @param int   $aggregation_id
	 * @param Tag[] $tags
	 * @return int[]
	 * @throws TagNotFound If one or more of the Tags could not be found in the Repository.
	 */
	public function AddTaggedTags(int $tagged_id, int $aggregation_id, array $tags): array
	{
		$tagging_ids = [];
		foreach ($tags as $tag)
		{
			$tagging_ids[] = $this->AddTaggedTag($tagged_id, $aggregation_id, $tag);
		}

		return $tagging_ids;
	}

	/**
	 * Deletes a Tagged's Tag from the Repository.
	 *
	 * @param int $tagged_id
	 * @param int $aggregation_id
	 * @param Tag $tag
	 */
	public function RemoveTaggedTag(int $tagged_id, int $aggregation_id, Tag $tag)
	{
		$tag_id = $tag->GetId();

		if (!isset($tag_id))
		{
			throw new InvalidArgumentException("Failed to Remove Tagged's Tag, Tag's ID unknown");
		}

		$this->repository->DeleteTaggedTags($tagged_id, $aggregation_id, $tag_id);
	}

	/**
	 * Deletes a Tagged's Tags from the Repository.
	 *
	 * @param int   $tagged_id
	 * @param int   $aggregation_id
	 * @param Tag[] $tags
	 */
	public function RemoveTaggedTags(int $tagged_id, int $aggregation_id, array $tags)
	{
		foreach ($tags as $tag)
		{
			$this->RemoveTaggedTag($tagged_id, $aggregation_id, $tag);
		}
	}

	/**
	 * Deletes all of a Tagged's Tags from the Repository.
	 *
	 * @param int $tagged_id
	 * @param int $aggregation_id
	 */
	public function RemoveAllTaggedTags(int $tagged_id, int $aggregation_id)
	{
		$this->repository->DeleteTaggedTags($tagged_id, $aggregation_id);
	}
}
