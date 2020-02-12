<?php

namespace Claromentis\ThankYou\Thanked;

class Thanked implements ThankedInterface
{
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int|null
	 */
	private $extranet_id;

	/**
	 * @var int|null
	 */
	private $id;

	/**
	 * @var string|null
	 */
	private $image_url;

	/**
	 * @var int|null
	 */
	private $item_id;

	/**
	 * @var string|null
	 */
	private $object_url;

	/**
	 * @var int|null
	 */
	private $owner_class_id;

	/**
	 * @var string|null
	 */
	private $owner_class_name;

	public function __construct(
		string $name,
		?int $extranet_id,
		?int $id,
		?string $image_url,
		?int $item_id,
		?string $object_url,
		?int $owner_class_id,
		?string $owner_class_name
	) {
		$this->name        = $name;
		$this->extranet_id = $extranet_id;
		$this->SetId($id);
		$this->image_url        = $image_url;
		$this->item_id          = $item_id;
		$this->object_url       = $object_url;
		$this->owner_class_id   = $owner_class_id;
		$this->owner_class_name = $owner_class_name;
	}

	/**
	 * @inheritDoc
	 */
	public function GetName(): string
	{
		return $this->name;
	}

	/**
	 * @inheritDoc
	 */
	public function GetExtranetId(): ?int
	{
		return $this->extranet_id;
	}

	/**
	 * @inheritDoc
	 */
	public function GetId(): ?int
	{
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function GetImageUrl(): ?string
	{
		return $this->image_url;
	}

	/**
	 * @inheritDoc
	 */
	public function GetItemId(): ?int
	{
		return $this->item_id;
	}

	/**
	 * @inheritDoc
	 */
	public function GetObjectUrl(): ?string
	{
		return $this->object_url;
	}

	/**
	 * @inheritDoc
	 */
	public function GetOwnerClass(): ?int
	{
		return $this->owner_class_id;
	}

	/**
	 * @inheritDoc
	 */
	public function GetOwnerClassName(): ?string
	{
		return $this->owner_class_name;
	}

	/**
	 * @inheritDoc
	 */
	public function SetId(?int $id): void
	{
		$this->id = $id;
	}
}
