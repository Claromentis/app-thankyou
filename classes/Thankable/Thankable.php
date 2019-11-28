<?php

namespace Claromentis\ThankYou\Thankable;

class Thankable
{
	/**
	 * @var string $name
	 */
	private $name;

	/**
	 * @var int|null $id
	 */
	private $id;

	/**
	 * @var string|null $owner_class_name
	 */
	private $owner_class_name;

	/**
	 * PermOClass constant.
	 *
	 * @var int|null $owner_class_id
	 */
	private $owner_class_id;

	/**
	 * @var int|null $extranet_id
	 */
	private $extranet_id;

	/**
	 * @var string|null $image_url
	 */
	private $image_url;

	/**
	 * @var string|null $profile_url
	 */
	private $profile_url;

	public function __construct(string $name, ?int $id = null, ?string $owner_class_name = null, ?int $owner_class_id = null, ?int $extranet_id = null, ?string $image_url = null, ?string $profile_url = null)
	{
		$this->name             = $name;
		$this->id               = $id;
		$this->owner_class_name = $owner_class_name;
		$this->owner_class_id   = $owner_class_id;
		$this->extranet_id      = $extranet_id;
		$this->image_url        = $image_url;
		$this->profile_url      = $profile_url;
	}

	/**
	 * @return int|null
	 */
	public function GetExtranetId(): ?int
	{
		return $this->extranet_id;
	}

	/**
	 * @return int|null
	 */
	public function GetId(): ?int
	{
		return $this->id;
	}

	/**
	 * @return string|null
	 */
	public function GetImageUrl(): ?string
	{
		return $this->image_url;
	}

	/**
	 * @return string
	 */
	public function GetName(): string
	{
		return $this->name;
	}

	/**
	 * @return int|null
	 */
	public function GetOwnerClass(): ?int
	{
		return $this->owner_class_id;
	}

	/**
	 * @return string|null
	 */
	public function GetOwnerClassName(): ?string
	{
		return $this->owner_class_name;
	}

	/**
	 * @return string|null
	 */
	public function GetProfileUrl(): ?string
	{
		return $this->profile_url;
	}
}
