<?php
namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Application;
use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\ThankYou\ThanksRepository;
use Date;
use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use RestExNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * A rest controller for thank you items.
 */
class ThanksRestController
{
	/**
	 * @var ThanksRepository
	 */
	protected $repository;

	/**
	 * Create a new thanks rest controller.
	 *
	 * @param ThanksRepository $repository
	 */
	public function __construct(ThanksRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Get a single thanks item by ID.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param int $id
	 * @return JsonResponse
	 * @throws RestExNotFound
	 * @throws Exception
	 */
	public function GetThanksItem(Application $app, Request $request, $id)
	{
		$id = (int) $id;
		$item = $this->repository->GetById($id);

		if (!$item)
			throw new RestExNotFound("Thank you item not found");

		// Load each user's full name for the response
		$users = [];

		foreach ($item->GetUsers() ?: [] as $user_id)
		{
			$user = new \User($user_id);
			$user->Load();

			if (!$user->GetId())
				continue;

			$users[] = [
				'id'   => $user->GetId(),
				'name' => $user->GetFullname()
			];
		}

		return new JsonPrettyResponse([
			'id'           => (int) $item->id,
			'author'       => (int) $item->author,
			'date_created' => new Date($item->date_created),
			'description'  => $item->description,
			'users'        => $users
		]);
	}
}
