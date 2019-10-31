<?php

namespace Claromentis\ThankYou\ThankYous;

class Thankable
{
	private $name;

	/**
	 * PermOClass constant.
	 *
	 * @var int|null
	 */
	private $owner_class;

	private $id;

	private $extranet_id;

	private $image_url;

	private $profile_url;

	public function __construct(string $name, ?int $owner_class = null, ?int $id = null, ?int $extranet_id = null, ?string $image_url = null, ?string $profile_url = null)
	{
		$this->name        = $name;
		$this->owner_class = $owner_class;
		$this->id          = $id;
		$this->extranet_id = $extranet_id;
		$this->image_url   = $image_url;
		$this->profile_url = $profile_url;
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
		return $this->owner_class;
	}

	/**
	 * @return string|null
	 */
	public function GetProfileUrl(): ?string
	{
		return $this->profile_url;
	}
}
