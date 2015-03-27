<?php
namespace Claromentis\ThankYou;

use Claromentis\Core\DAL;

/**
 * Description of ThanksRepository.php
 *
 * @author Alexander Polyanskikh
 */
class ThanksRepository
{
	/**
	 * @param int $number
	 *
	 * @return ThanksItem[]
	 */
	public function GetRecent($number)
	{
		$items = \ObjectsStorage::I()->GetMultiple(new ThanksItem(), '', 'date_created DESC', $number);
		return $items;
	}

}