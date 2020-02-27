<?php

namespace Claromentis\ThankYou\Thanked;

interface ThankedInterface
{
	/**
	 * @return string
	 */
	public function GetName(): string;

	/**
	 * @return int|null
	 */
	public function GetExtranetId(): ?int;

	/**
	 * Returns the Repository ID.
	 *
	 * @return int|null
	 */
	public function GetId(): ?int;

	/**
	 * @return string|null
	 */
	public function GetImageUrl(): ?string;

	/**
	 * @return int|null
	 */
	public function GetItemId(): ?int;

	/**
	 * @return string|null
	 */
	public function GetObjectUrl(): ?string;

	/**
	 * @return int|null
	 */
	public function GetOwnerClass(): ?int;

	/**
	 * @return string|null
	 */
	public function GetOwnerClassName(): ?string;

	/**
	 * Set the Repository ID.
	 *
	 * @param int|null $id
	 */
	public function SetId(?int $id): void;
}
