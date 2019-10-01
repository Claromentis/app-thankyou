<?php

namespace Claromentis\ThankYou\ThankYous;

class Thankable
{
	private $name;

	private $obj_type_id;

	private $id;

	private $extranet_area_id;

	public function __construct(string $name, ?int $obj_type_id = null, ?int $id = null, ?int $extranet_area_id = null)
	{
		$this->name = $name;
		$this->obj_type_id = $obj_type_id;
		$this->id = $id;
		$this->extranet_area_id = $extranet_area_id;
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
}
