<?php

namespace Claromentis\ThankYou\Tags\DataTables;

use Claromentis\Core\DataTable\Decorator\Decorator;

class ActionsDataTableDecorator extends Decorator
{
	/**
	 * Returns an associative array where the keys will be accessible in the angular template.
	 *
	 * @param mixed $content
	 * @return mixed
	 */
	public function Decorate($content): array
	{
		return [
			'id' => $content['id'],
			'new_active_state' => (int) (!$content['active']),
			'icon' => $content['active'] ? 'glyphicons-eye-close' : 'glyphicons-eye-open'
		];
	}

	/**
	 * Returns the contents of the cell being decorated as a scalar value.
	 *
	 * @param mixed $content
	 * @return string
	 */
	public function Basic($content)
	{
		return '';
	}

	/**
	 * Returns the contents of the template file as a string. Can be processed by process_cla_template first.
	 *
	 * @return string
	 */
	public function GetTemplate()
	{
		return process_cla_template('thankyou/admin/actions_data_table_decorator.html', [], [], '', false);
	}
}
