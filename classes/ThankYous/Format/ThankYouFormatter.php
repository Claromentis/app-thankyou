<?php

namespace Claromentis\ThankYou\ThankYous\Format;

use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Tags\Format\TagFormatter;
use Claromentis\ThankYou\Thanked\Thanked;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use DateClaTimeZone;
use DateTimeZone;
use InvalidArgumentException;

class ThankYouFormatter
{
	/**
	 * @var ThankYouAcl
	 */
	private $acl;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var TagFormatter
	 */
	private $tag_formatter;

	public function __construct(Lmsg $lmsg, TagFormatter $tag_formatter, ThankYouAcl $thank_you_acl)
	{
		$this->acl           = $thank_you_acl;
		$this->lmsg          = $lmsg;
		$this->tag_formatter = $tag_formatter;
	}

	/**
	 * @param ThankYou[]           $thank_yous
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array
	 */
	public function ConvertThankYousToArrays(array $thank_yous, ?DateTimeZone $time_zone = null, ?SecurityContext $security_context = null): array
	{
		if (!isset($time_zone))
		{
			$time_zone = $this->GetDefaultTimeZone();
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

		return $thank_yous_array;
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
	 *         thanked => null|array(see ConvertThankedToArray),
	 *         users => null|array[
	 *         id => int,
	 *         name => string
	 *         ]
	 *         ]
	 */
	public function ConvertThankYouToArray(ThankYou $thank_you, ?DateTimeZone $time_zone = null, ?SecurityContext $security_context = null): array
	{
		if (!isset($time_zone))
		{
			$time_zone = $this->GetDefaultTimeZone();
		}

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		if (isset($security_context) && !$this->acl->CanSeeThankYouAuthor($security_context, $thank_you))
		{
			$author_name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$author_name = $thank_you->GetAuthor()->getFullname();
		}

		$output = [
			'author'       => [
				'id'   => $thank_you->GetAuthor()->id,
				'name' => $author_name
			],
			'date_created' => $date_created,
			'description'  => $thank_you->GetDescription(),
			'id'           => $thank_you->GetId()
		];

		$thanked = $thank_you->GetThanked();
		if (isset($thanked))
		{
			$thankeds_array = [];
			foreach ($thanked as $thank)
			{
				$thankeds_array[] = $this->ConvertThankedToArray($thank, $security_context);
			}
			$output['thanked'] = $thankeds_array;
		}

		$users = $thank_you->GetUsers();
		if (isset($users))
		{
			$users_array = [];
			foreach ($users as $user)
			{
				$user_name     = (isset($security_context) && !$this->acl->CanSeeThankedUserName($security_context, $user)) ? ($this->lmsg)('common.perms.hidden_name') : $user->getFullname();
				$users_array[] = ['id' => $user->id, 'name' => $user_name];
			}
			$output['users'] = $users_array;
		}

		$tags = $thank_you->GetTags();
		if (isset($tags))
		{
			$output['tags'] = $this->tag_formatter->FormatTags($tags);
		}

		return $output;
	}

	/**
	 * @param Thanked[]            $thankeds
	 * @param SecurityContext|null $security_context
	 * @return array
	 */
	public function ConvertThankedsToArrays(array $thankeds, ?SecurityContext $security_context = null): array
	{
		$thankeds_array = [];
		foreach ($thankeds as $thanked)
		{
			$thankeds_array[] = $this->ConvertThankedToArray($thanked, $security_context);
		}

		return $thankeds_array;
	}

	/**
	 * @param Thanked              $thanked
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
	public function ConvertThankedToArray(Thanked $thanked, ?SecurityContext $security_context = null): array
	{
		$object_type    = null;
		$object_type_id = $thanked->GetOwnerClass();
		if (isset($object_type_id))
		{
			$owner_class_name = $thanked->GetOwnerClassName() ?? '';

			$object_type = ['id' => $object_type_id, 'name' => $owner_class_name];
		}

		if (isset($security_context) && !$this->acl->CanSeeThankedName($security_context, $thanked))
		{
			$name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$name = $thanked->GetName();
		}

		return [
			'id'               => $thanked->GetItemId(),
			'extranet_area_id' => $thanked->GetExtranetId(),
			'name'             => $name,
			'object_type'      => $object_type
		];
	}

	/**
	 * @return DateTimeZone
	 */
	private function GetDefaultTimeZone()
	{
		return DateClaTimeZone::GetCurrentTZ();
	}
}
