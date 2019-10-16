<?php

namespace Claromentis\ThankYou\Configuration;

use Claromentis\Core\Localization\Lmsg;

class Configuration
{
	private $lmsg;

	public function __construct(Lmsg $lmsg)
	{
		$this->lmsg = $lmsg;
	}

	public function GetOptions(): array
	{
		//TODO: Use generalised ConfigOptions Object (once it exists)
		return [
			'notify_line_manager' => [
				'title'   => ($this->lmsg)('thankyou.configurations.notify_line_manager.title'),
				'type'    => 'bool',
				'default' => true
			],
			'thank_you_comments'  => [
				'title'   => ($this->lmsg)('thankyou.configurations.thank_you_comments.title'),
				'type'    => 'bool',
				'default' => true
			]
		];
	}
}
