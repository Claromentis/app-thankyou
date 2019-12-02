<?php

namespace Claromentis\ThankYou\ThankYous\DataTables\ThankYou;

use Claromentis\Core\DataTable\Decorator\Decorator;

class DescriptionDecorator extends Decorator
{
	/**
	 * @inheritDoc
	 */
	public function Decorate($content)
	{
		return [
			'description' => $content['description'],
			'url'         => $content['thank_you_url']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function Basic($content)
	{
		return $content['description'];
	}

	/**
	 * @inheritDoc
	 */
	public function GetTemplate()
	{
		return process_cla_template('thankyou/DataTables/ThankYou/description_decorator.html', [], [], '', false);
	}
}
