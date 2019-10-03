<?php

namespace Claromentis\ThankYou\ThankYous;

class Thankable
{
	private $name;

	private $obj_type_id;

	private $id;

	private $image_url;

	private $extranet_area_id;

	private $profile_url;

	public function __construct(string $name, ?int $obj_type_id = null, ?int $id = null, ?int $extranet_area_id = null, ?string $image_url = null, ?string $profile_url = null)
	{
		$this->extranet_area_id = $extranet_area_id;
		$this->id               = $id;
		$this->image_url        = $image_url;
		$this->name             = $name;
		$this->obj_type_id      = $obj_type_id;
		$this->profile_url      = $profile_url;
	}

	/**
	 * @return int|null
	 */
	public function GetExtranetAreaId(): ?int
	{
		return $this->extranet_area_id;
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
	public function GetObjectTypeId(): ?int
	{
		return $this->obj_type_id;
	}

	/**
	 * @return string|null
	 */
	public function GetProfileUrl(): ?string
	{
		return $this->profile_url;
	}
}
