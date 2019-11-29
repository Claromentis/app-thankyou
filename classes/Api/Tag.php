<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Audit\Audit;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Plugin;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagForbidden;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;
use Claromentis\ThankYou\Tags\TagAcl;
use Claromentis\ThankYou\Tags\TagFactory;
use Claromentis\ThankYou\Tags\TagRepository;
use Date;
use InvalidArgumentException;
use LogicException;
use User;

class Tag
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
	 * Tag constructor.
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
	 * @return \Claromentis\ThankYou\Tags\Tag
	 * @throws TagNotFound - If the Tag could not be found in the Repository.
	 */
	public function GetTag(int $id): \Claromentis\ThankYou\Tags\Tag
	{
		try
		{
			$tag = $this->repository->Load([$id]);
		} catch (InvalidArgumentException $exception)
		{
			throw new LogicException("Failed to Get Tag, unexpected Exception thrown when Loading Tag", null, $exception);
		}

		if (!isset($tag[$id]))
		{
			throw new TagNotFound("Failed to Get Tag, Tag with ID '" . $id . "' could not be found");
		}

		return $tag[$id];
	}

	/**
	 * @param int|null    $limit
	 * @param int|null    $offset
	 * @param string|null $name
	 * @param array|null  $orders
	 * @return \Claromentis\ThankYou\Tags\Tag[]
	 */
	public function GetTags(?int $limit = null, ?int $offset = null, ?string $name = null, ?array $orders = null): array
	{
		return $this->repository->GetFilteredTags($limit, $offset, $name, $orders);
	}

	/**
	 * Returns an array of Tags indexed by their ID.
	 *
	 * @param int[] $ids
	 * @return \Claromentis\ThankYou\Tags\Tag[]
	 */
	public function GetTagsById(array $ids): array
	{
		return $this->repository->Load($ids);
	}

	/**
	 * @return int
	 */
	public function GetTotalTags(): int
	{
		return $this->repository->GetTotalTags();
	}

	public function GetTagsTaggedTotals(?int $limit = null, ?int $offset = null, ?bool $active = null, ?array $orders = null): array
	{
		return $this->repository->GetTagsTaggedTotals($limit, $offset, $active, $orders);
	}

	/**
	 * @param int[] $ids
	 * @return array
	 */
	public function GetTagsTaggedTotalsFromIds(array $ids): array
	{
		return $this->repository->GetTagsTaggedTotalsFromIds($ids);
	}

	/**
	 * @param User   $user
	 * @param string $name
	 * @return \Claromentis\ThankYou\Tags\Tag
	 * @throws TagInvalidNameException
	 */
	public function Create(User $user, string $name): \Claromentis\ThankYou\Tags\Tag
	{
		$tag = $this->factory->Create($name);
		$tag->SetCreatedBy($user);
		$tag->SetCreatedDate(new Date());
		$tag->SetModifiedBy($user);
		$tag->SetModifiedDate(new Date());

		return $tag;
	}

	/**
	 * Saves a Tag to the database. If the Tag does not have an ID, one will be set.
	 *
	 * @param \Claromentis\ThankYou\Tags\Tag $tag
	 * @throws TagDuplicateNameException - If the Tag's Name is not unique to the Repository.
	 */
	public function Save(\Claromentis\ThankYou\Tags\Tag $tag)
	{
		$new = ($tag->GetId() === null) ? true : false;

		$this->repository->Save($tag);

		$id   = $tag->GetId();
		$name = $tag->GetName();

		if ($new)
		{
			$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_create', $id, $name);
		} else
		{
			$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_edit', $id, $name);
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

		$this->audit->Store(AUDIT_SUCCESS, Plugin::APPLICATION_NAME, 'tag_delete', $id, $tag->GetName());
	}
}
