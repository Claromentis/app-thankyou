<?php

namespace Claromentis\ThankYou\Thankable;

class Thankable
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
	 * PermOClass constant.
	 *
	 * @var int|null
	 */
	private $owner_class_id;

	/**
	 * @var string|null
	 */
	private $owner_class_name;

	/**
	 * @var string|null
	 */
	private $object_url;

	/**
	 * Thankable constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->SetName($name);
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
	public function GetObjectUrl(): ?string
	{
		return $this->object_url;
	}

	/**
	 * @param string $name
	 */
	public function SetName(string $name)
	{
		$this->name = $name;
	}

	public function SetExtranetId(?int $id)
	{
		$this->extranet_id = $id;
	}

	/**
	 * @param int|null $id
	 */
	public function SetId(?int $id)
	{
		$this->id = $id;
	}

	public function SetImageUrl(?string $url)
	{
		$this->image_url = $url;
	}

	/**
	 * @param int|null $owner_class_id
	 */
	public function SetOwnerClassId(?int $owner_class_id)
	{
		$this->owner_class_id = $owner_class_id;
	}

	/**
	 * @param string|null $name
	 */
	public function SetOwnerClassName(?string $name)
	{
		$this->owner_class_name = $name;
	}

	public function SetObjectUrl(?string $url)
	{
		$this->object_url = $url;
	}
}
