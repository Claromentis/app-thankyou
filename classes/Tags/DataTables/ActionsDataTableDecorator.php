<?php

namespace Claromentis\ThankYou\Tags\DataTables;

use Claromentis\Core\DataTable\Decorator\Decorator;
use Claromentis\Core\Services;

class ActionsDataTableDecorator extends Decorator
{
	/**
	 * @inheritDoc
	 * @return array
	 */
	public function Decorate($content): array
	{
		$lmsg = Services::I()->lmsg;

		return [
			'id'               => $content['id'],
			'new_active_state' => (int) (!$content['active']),
			'icon'             => $content['active'] ? 'glyphicons-eye-open' : 'glyphicons-eye-close',
			'active_tooltip'   => $content['active'] ? $lmsg('thankyou.common.disable') : $lmsg('thankyou.common.enable')
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
