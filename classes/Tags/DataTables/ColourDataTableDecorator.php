<?php

namespace Claromentis\ThankYou\Tags\DataTables;

use Claromentis\Core\DataTable\Decorator\Decorator;

class ColourDataTableDecorator extends Decorator
{
	/**
	 * @inheritDoc
	 * @return array
	 */
	public function Decorate($content): array
	{
		return [
			'bg_colour' => $content['bg_colour']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function Basic($content): string
	{
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function GetTemplate(): string
	{
		return process_cla_template('thankyou/admin/colour_data_table_decorator.html', [], [], '', false);
	}
}
