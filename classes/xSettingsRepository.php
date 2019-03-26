<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;
use DBVar;
use \InvalidArgumentException;

class xSettingsRepository
{
	const DB_VAR_DATA = [
		'notify_line_manager' => [
			'key'		=> 'thankyou:notify_line_manager',
			'type'		=> 'bool',
			'default'	=> false
		]
	];

	/** @var DAL\Db */
	protected $db;

	/**
	 * @param DAL\Db $db
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Retrieve any of the DB_VAR_DATA variables.
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function Get($key)
	{
		$var_data = $this->GetVarData($key);

		if (($value = DBVar::Get($var_data['key'])) === false) {
			// DBVar has never been set - get the default value
			$value = $var_data['default'];
		}

		if ($var_data['type'] == 'bool') {
			// implicitly set boolean to 0|1
			$value = (bool)$value;
		}

		return $value;
	}

	/**
	 * Set any of the DB_VAR_DATA variables to the given value; implicitly setting booleans to 0|1.
	 *
	 * @param String	$key
	 * @param mixed		$value (type according to $key in DB_VAR_DATA)
	 *
	 * @throws InvalidArgumentException
	 */
	public function Set($key, $value)
	{
		if (is_bool($value)) {
			$value = $value ? '1' : '0';
		}

		$var_data = $this->GetVarData($key);

		$this->db->DisableTokenCheck();
		{
			DBVar::Set($var_data['key'], $value);
		}
		$this->db->EnableTokenCheck();
	}

	/**
	 * @param $key
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function GetVarData($key)
	{
		if (!$var_data = self::DB_VAR_DATA[$key] ?? null) {
			throw new InvalidArgumentException("Unknown Thankyou config key '{$key}'.");
		}

		return $var_data;
	}
}