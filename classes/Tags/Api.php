<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Audit\Audit;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Plugin;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagForbiddenException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFoundException;
use Date;
use InvalidArgumentException;
use User;

class Api
{
	/**
	 * @var TagAcl
	 */
	private $acl;

	/**
	 * @var Audit
	 */
	private $audit;

	/**
	 * @var TagFactory
	 */
	private $factory;

	/**
	 * @var TagRepository
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
	 * @throws TagNotFoundException - If the Tag could not be found in the Repository.
	 */
	public function GetTag(int $id): Tag
	{
		$tags = $this->repository->GetTags([$id]);

		if (!isset($tags[$id]))
		{
			throw new TagNotFoundException("Failed to Get Tag, Tag with ID '" . $id . "' could not be found");
		}

		return $tags[$id];
	}

	/**
	 * @param int|null    $limit
	 * @param int|null    $offset
	 * @param string|null $name
	 * @param bool|null   $active
	 * @param array|null  $orders
	 * @return Tag[]
	 */
	public function GetTags(?int $limit = null, ?int $offset = null, ?string $name = null, ?bool $active = null, ?array $orders = null): array
	{
		return $this->repository->GetFilteredTags($limit, $offset, $name, $active, $orders);
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
	 * Given a Taggable's ID and its Aggregation ID, returns an array of Tags, indexed by the Tagging's ID.
	 *
	 * @param int $taggable_id
	 * @param int $aggregation_id
	 * @return Tag[]
	 */
	public function GetTaggableTags(int $taggable_id, int $aggregation_id): array
	{
		return $this->GetTaggablesTags([$taggable_id], $aggregation_id)[$taggable_id];
	}

	/**
	 * Given an array of Taggables' IDs, and the Taggables' Aggregation ID, returns an array of Taggings, indexed by the Taggable's ID.
	 *
	 * @param int[] $taggable_ids
	 * @param int   $aggregation_id
	 * @return array[]
	 */
	public function GetTaggablesTags(array $taggable_ids, int $aggregation_id): array
	{
		$taggeds_tags = $this->repository->GetTaggablesTags($taggable_ids, $aggregation_id);
		foreach ($taggable_ids as $taggable_id)
		{
			if (!isset($taggeds_tags[$taggable_id]))
			{
				$taggeds_tags[$taggable_id] = [];
			}
		}

		return $taggeds_tags;
	}

	/**
	 * Returns the total number of Tags in the Repository.
	 *
	 * @param bool|null $active
	 *                         - null: count all Tags.
	 *                         - true: only count Active Tags.
	 *                         - false: only count Inactive Tags.
	 * @return int
	 */
	public function GetTotalTags(?bool $active = null): int
	{
		return $this->repository->GetTotalTags($active);
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
	public function GetTagsTaggingTotals(?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $orders = null): array
	{
		return $this->repository->GetTagsTaggingTotals($limit, $offset, $active, $orders);
	}

	/**
	 * Given an array of Tag IDs,
	 * returns the number of times the Tags have been used for Tagging, indexed by the Tag's IDs.
	 *
	 * @param int[] $ids
	 * @return array
	 */
	public function GetTagsTaggingTotalsFromIds(array $ids): array
	{
		return $this->repository->GetTagsTaggingTotalsFromIds($ids);
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
	 * @throws TagForbiddenException - If the SecurityContext does not allow the Tag to be deleted.
	 * @throws TagNotFoundException - If the Tag cannot be found in the Repository.
	 * @throws StorageException - If the Tag could not be Deleted from the Repository.
	 */
	public function Delete(int $id, SecurityContext $context)
	{
		if (!$this->acl->CanDeleteTag($context))
		{
			throw new TagForbiddenException("User does not have Permission to Delete a Tag");
		}

		$tag = $this->GetTag($id);

		$this->repository->Delete($id);

		$this->audit->Store(Audit::AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_delete', $id, $tag->GetName());
	}

	/**
	 * Saves a Tagging to the Repository.
	 * Returns the ID of the Tagging.
	 *
	 * @param int $taggable_id
	 * @param int $aggregation_id
	 * @param Tag $tag
	 * @return int
	 * @throws TagNotFoundException If the Tag could not be found in the Repository.
	 */
	public function AddTagging(int $taggable_id, int $aggregation_id, Tag $tag): int
	{
		$tag_id = $tag->GetId();

		if (!isset($tag_id))
		{
			throw new InvalidArgumentException("Failed to Add Tagging, Tag's ID unknown");
		}

		return $this->repository->SaveTagging($taggable_id, $aggregation_id, $tag_id);
	}

	/**
	 * Saves Taggings for a single Taggable to the Repository.
	 * Returns an array of the Tagging IDs.
	 *
	 * @param int   $taggable_id
	 * @param int   $aggregation_id
	 * @param Tag[] $tags
	 * @return int[]
	 * @throws TagNotFoundException If one or more of the Tags could not be found in the Repository.
	 */
	public function AddTaggings(int $taggable_id, int $aggregation_id, array $tags): array
	{
		$tagging_ids = [];
		foreach ($tags as $tag)
		{
			$tagging_ids[] = $this->AddTagging($taggable_id, $aggregation_id, $tag);
		}

		return $tagging_ids;
	}

	/**
	 * Deletes a Tagging from the Repository.
	 *
	 * @param int $taggable_id
	 * @param int $aggregation_id
	 * @param Tag $tag
	 */
	public function RemoveTagging(int $taggable_id, int $aggregation_id, Tag $tag)
	{
		$tag_id = $tag->GetId();

		if (!isset($tag_id))
		{
			throw new InvalidArgumentException("Failed to Remove Tagging, Tag's ID unknown");
		}

		$this->repository->DeleteTaggableTaggings($taggable_id, $aggregation_id, $tag_id);
	}

	/**
	 * Deletes specific Taggings for a single Taggable from the Repository.
	 *
	 * @param int   $taggable_id
	 * @param int   $aggregation_id
	 * @param Tag[] $tags
	 */
	public function RemoveTaggings(int $taggable_id, int $aggregation_id, array $tags)
	{
		foreach ($tags as $tag)
		{
			$this->RemoveTagging($taggable_id, $aggregation_id, $tag);
		}
	}

	/**
	 * Deletes all of a Taggable's Taggings from the Repository.
	 *
	 * @param int $taggable_id
	 * @param int $aggregation_id
	 */
	public function RemoveAllTaggableTaggings(int $taggable_id, int $aggregation_id)
	{
		$this->repository->DeleteTaggableTaggings($taggable_id, $aggregation_id);
	}
}
