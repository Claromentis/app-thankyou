<?php

namespace Claromentis\ThankYou;

use Claromentis\Core\Audit\ApplicationAuditConfig;
use Claromentis\Core\Audit\ApplicationAuditUrlConfig;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFound;

class AuditConfig implements ApplicationAuditConfig, ApplicationAuditUrlConfig
{
	/**
	 * @var Api\Tag $tag_api
	 */
	private $tag_api;

	/**
	 * @var Lmsg $lmsg
	 */
	private $lmsg;

	/**
	 * AuditConfig constructor.
	 *
	 * @param Lmsg    $lmsg
	 * @param Api\Tag $tag_api
	 */
	public function __construct(Lmsg $lmsg, Api\Tag $tag_api)
	{
		$this->tag_api = $tag_api;
		$this->lmsg    = $lmsg;
	}

	/**
	 * @inheritDoc
	 */
	public function GetAuditApplication(): array
	{
		return [Plugin::APPLICATION_NAME, ($this->lmsg)('thankyou.app_name')];
	}

	/**
	 * @inheritDoc
	 */
	public function GetAuditEvents(): array
	{
		return array_merge($this->GetThankYouAuditEvents(), $this->GetTagAuditEvents(), $this->GetLikeAuditEvents());
	}

	/**
	 * @inheritDoc
	 */
	public function GetAuditObjectName($event_code, $object_id): string
	{
		if (isset($this->GetThankYouAuditEvents()[$event_code]))
		{
			return ($this->lmsg)('thankyou.thankyou.name');
		} elseif (isset($this->GetTagAuditEvents()[$event_code]))
		{
			try
			{
				$tag = $this->tag_api->GetTag($object_id);

				return $tag->GetName();
			} catch (TagNotFound $exception)
			{
				return ($this->lmsg)('thankyou.tag.name');
			}
		} else
		{
			return '';
		}
	}

	/**
	 * Returns link to an object by its id and event code
	 *
	 * @param string $event_code
	 * @param int    $object_id
	 *
	 * @return string
	 */
	public function GetAuditObjectUrl($event_code, $object_id)
	{
		return '';
	}

	private function GetLikeAuditEvents(): array
	{
		return [
			"like" => "Like a thank you note",
			"unlike" => "Unlike a thank you note",
			"comment_like" => "Like a comment",
			"comment_unlike" => "Unlike a comment",
		];
	}

	/**
	 * Returns an array of Tag Audit Names, indexed by their Audit Events.
	 *
	 * @return array
	 */
	private function GetTagAuditEvents(): array
	{
		return [
			'tag_create' => ($this->lmsg)('thankyou.tag.audit.create'),
			'tag_edit'   => ($this->lmsg)('thankyou.tag.audit.edit'),
			'tag_delete' => ($this->lmsg)('thankyou.tag.audit.delete')
		];
	}

	/**
	 * Returns an array of Thank You Audit Names, indexed by their Audit Events.
	 *
	 * @return array
	 */
	private function GetThankYouAuditEvents(): array
	{
		return [
			'thank_you_create' => ($this->lmsg)('thankyou.audit.create_thank_you'),
			'thank_you_edit'   => ($this->lmsg)('thankyou.audit.edit_thank_you'),
			'thank_you_delete' => ($this->lmsg)('thankyou.audit.delete_thank_you')
		];
	}
}
