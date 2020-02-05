<?php

namespace Claromentis\ThankYou\Tags\Exceptions;

use Throwable;

class ValidationException extends TagException
{
	/**
	 * @var array
	 */
	private $errors;

	/**
	 * ValidationException constructor.
	 *
	 * @param array          $errors
	 * @param string|null    $message
	 * @param int            $code
	 * @param Throwable|null $previous
	 */
	public function __construct(array $errors, string $message = "", int $code = 0, Throwable $previous = null)
	{
		$this->errors = $errors;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return array
	 */
	public function GetErrors(): array
	{
		return $this->errors;
	}
}
