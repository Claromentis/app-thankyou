<?php

namespace Claromentis\ThankYou;

use Claromentis\Core\Audit\ApplicationAuditConfig;
use Claromentis\Core\Audit\ApplicationAuditUrlConfig;

class AuditConfig implements ApplicationAuditConfig, ApplicationAuditUrlConfig
{

	/**
	 * Returns two-elements array where first element is application code,
	 * second is application name.
	 *
	 * @return array($app_code, $app_name)
	 */
	public function GetAuditApplication()
	{
		return ["thankyou", "Thank You component"];
	}

	/**
	 * Returns associative array of audit events within this application.
	 *
	 * Keys are events codes, values are events names
	 *
	 * @return array
	 */
	public function GetAuditEvents()
	{
		return [
			"like" => "Like a thankyou",
			"unlike" => "Unlike a thankyou",
			"comment_like" => "Like a comment",
			"comment_unlike" => "Unlike a comment",
		];
	}

	/**
	 * Returns object name by its id and event code
	 *
	 * @param string $event_code
	 * @param int    $object_id
	 *
	 * @return string
	 */
	public function GetAuditObjectName($event_code, $object_id)
	{
		return '';
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
}