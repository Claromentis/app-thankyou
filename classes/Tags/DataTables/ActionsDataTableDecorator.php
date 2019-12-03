<?php

namespace Claromentis\ThankYou\Tags\DataTables;

use Claromentis\Core\DataTable\Decorator\Decorator;

class ActionsDataTableDecorator extends Decorator
{
	/**
	 * @inheritDoc
	 * @return array
	 */
	public function Decorate($content): array
	{
		return [
			'id'               => $content['id'],
			'new_active_state' => (int) (!$content['active']),
			'icon'             => $content['active'] ? 'glyphicons-eye-close' : 'glyphicons-eye-open'
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
		return process_cla_template('thankyou/admin/actions_data_table_decorator.html', [], [], '', false);
	}
}
