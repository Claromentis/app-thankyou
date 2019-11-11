<?php

namespace Claromentis\ThankYou\Tags\Format;

use Claromentis\ThankYou\Tags\Tag;
use DateClaTimeZone;
use RestFormat;

class TagFormatter
{
	/**
	 * @var RestFormat $rest_format
	 */
	private $rest_format;

	/**
	 * TagFormatter constructor.
	 *
	 * @param RestFormat $rest_format
	 */
	public function __construct(RestFormat $rest_format)
	{
		$this->rest_format = $rest_format;
	}

	/**
	 * @param Tag[] $tags
	 * @return array
	 */
	public function FormatTags(array $tags): array
	{
		$tags_array = [];
		foreach ($tags as $tag)
		{
			$tags_array[] = $this->FormatTag($tag);
		}

		return $tags_array;
	}

	/**
	 * @param Tag $tag
	 * @return array
	 */
	public function FormatTag(Tag $tag): array
	{
		$created_date = clone $tag->GetCreatedDate();
		$created_date->setTimezone(DateClaTimeZone::GetCurrentTZ());
		$created_date = $this->rest_format->Date($created_date);

		$modified_date = clone $tag->GetModifiedDate();
		$modified_date->setTimezone(DateClaTimeZone::GetCurrentTZ());
		$modified_date = $this->rest_format->Date($modified_date);

		$created_by_name = null;
		$created_by      = $tag->GetCreatedBy();
		if ($created_by)
		{
			$created_by_name = $created_by->GetFullname();
		}

		$modified_by_name = null;
		$modified_by      = $tag->GetModifiedBy();
		if ($modified_by)
		{
			$modified_by_name = $modified_by->GetFullname();
		}

		$formatted_tag = [
			'id'            => $tag->GetId(),
			'active'        => $tag->GetActive(),
			'name'          => $tag->GetName(),
			'created_by'    => $created_by_name,
			'created_date'  => $created_date,
			'modified_by'   => $modified_by_name,
			'modified_date' => $modified_date,
			'bg_colour'     => $tag->GetBackgroundColour()
		];

		return $formatted_tag;
	}
}
