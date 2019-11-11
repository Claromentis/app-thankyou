<?php

namespace Claromentis\ThankYou\ThankYous\Format;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\Tags\Format\TagFormatter;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use DateClaTimeZone;
use DateTimeZone;
use InvalidArgumentException;

class ThankYouFormatter
{
	private $acl;

	private $lmsg;

	private $tag_formatter;

	private $utility;

	public function __construct(Lmsg $lmsg, TagFormatter $tag_formatter, ThankYouAcl $thank_you_acl, ThankYouUtility $thank_you_utility)
	{
		$this->acl           = $thank_you_acl;
		$this->lmsg          = $lmsg;
		$this->tag_formatter = $tag_formatter;
		$this->utility       = $thank_you_utility;
	}

	/**
	 * @param ThankYou|ThankYou[]  $thank_yous
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array
	 */
	public function ConvertThankYousToArrays($thank_yous, ?DateTimeZone $time_zone = null, ?SecurityContext $security_context = null): array
	{
		if (!isset($time_zone))
		{
			$time_zone = DateClaTimeZone::GetCurrentTZ();
		}

		$array_return = true;
		if (!is_array($thank_yous))
		{
			$array_return = false;
			$thank_yous   = [$thank_yous];
		}

		$thank_yous_array = [];
		foreach ($thank_yous as $thank_you)
		{
			if (!($thank_you instanceof ThankYou))
			{
				throw new InvalidArgumentException("Failed to Convert Thank Yous to array, 1st argument must be an array of ThankYous");
			}
			$thank_yous_array[] = $this->ConvertThankYouToArray($thank_you, $time_zone, $security_context);
		}

		return $array_return ? $thank_yous_array : $thank_yous_array[0];
	}

	/**
	 * @param ThankYou             $thank_you
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array[
	 *         author => [
	 *         id => int,
	 *         name => string
	 *         ],
	 *         date_created => Date,
	 *         description => string,
	 *         id => int|null,
	 *         thanked => null|array(see ConvertThankableToArray),
	 *         users => null|array[
	 *         id => int,
	 *         name => string
	 *         ]
	 *         ]
	 */
	public function ConvertThankYouToArray(ThankYou $thank_you, DateTimeZone $time_zone, ?SecurityContext $security_context = null): array
	{
		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		if (isset($security_context) && !$this->acl->CanSeeThankYouAuthor($security_context, $thank_you))
		{
			$author_name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$author_name = $thank_you->GetAuthor()->GetFullname();
		}

		$output = [
			'author'       => [
				'id'   => $thank_you->GetAuthor()->GetId(),
				'name' => $author_name
			],
			'date_created' => $date_created,
			'description'  => $thank_you->GetDescription(),
			'id'           => $thank_you->GetId()
		];

		$thanked = $thank_you->GetThankable();
		if (isset($thanked))
		{
			foreach ($thanked as $offset => $thank)
			{
				$thanked[$offset] = $this->ConvertThankableToArray($thank, $security_context);
			}
		}
		$output['thanked'] = $thanked;

		$users = $thank_you->GetUsers();
		if (isset($users))
		{
			foreach ($users as $offset => $user)
			{
				$users[$offset] = ['id' => $user->GetId(), 'name' => $user->GetFullname()];
			}
		}
		$output['users'] = $users;

		$tags = $thank_you->GetTags();
		if (isset($tags))
		{
			$output['tags'] = $this->tag_formatter->FormatTags($tags);
		}

		return $output;
	}

	/**
	 * @param Thankable|Thankable[] $thankables
	 * @param SecurityContext|null  $security_context
	 * @return array
	 */
	public function ConvertThankablesToArrays($thankables, ?SecurityContext $security_context = null): array
	{
		$array_return = true;
		if (!is_array($thankables))
		{
			$array_return = false;
			$thankables   = [$thankables];
		}

		$thankables_array = [];
		foreach ($thankables as $thankable)
		{
			$thankables_array[] = $this->ConvertThankableToArray($thankable, $security_context);
		}

//TODO: Tighten inputs and outputs
		return $array_return ? $thankables_array : $thankables_array[0];
	}

	/**
	 * @param Thankable            $thankable
	 * @param SecurityContext|null $security_context
	 * @return array:
	 *         [
	 *         id => int|null,
	 *         extranet_area_id => int|null,
	 *         name    => string,
	 *         object_type => null|[
	 *         id  =>  int,
	 *         name => string
	 *         ]
	 *         ]
	 */
	public function ConvertThankableToArray(Thankable $thankable, ?SecurityContext $security_context = null): array
	{
		$object_type    = null;
		$object_type_id = $thankable->GetOwnerClass();
		if (isset($object_type_id))
		{
			try
			{
				$owner_class_name = $this->utility->GetOwnerClassNamesFromIds([$object_type_id])[0];
			} catch (ThankYouOClass $exception)
			{
				$owner_class_name = '';
			}
			$object_type = ['id' => $object_type_id, 'name' => $owner_class_name];
		}

		if (isset($security_context) && !$this->acl->CanSeeThankableName($security_context, $thankable))
		{
			$name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$name = $thankable->GetName();
		}

		$output = [
			'id'               => $thankable->GetId(),
			'extranet_area_id' => $thankable->GetExtranetId(),
			'name'             => $name,
			'object_type'      => $object_type
		];

		return $output;
	}
}
