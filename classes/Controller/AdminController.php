<?php
namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Csv\Csv;
use Claromentis\Core\Http\gpc;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\ThankYou\ThanksRepository;
use Date;
use DateInterval;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\HttpFoundation\Response;
use User;

/**
 * The admin panel controller.
 */
class AdminController
{
	/**
	 * Show the messages admin panel.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return TemplaterCallResponse
	 */
	public function ShowMessagesPanel(Application $app, Request $request)
	{
		$arguments = [
			'nav_messages.+class' => 'active'
		];

		return new TemplaterCallResponse('thankyou/admin/admin.html', $arguments, lmsg('thankyou.app_name'));
	}

	/**
	 * Show the export admin panel.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return TemplaterCallResponse
	 * @throws Exception
	 */
	public function ShowExportPanel(Application $app, Request $request)
	{
		// Set the initial start date to 1 year ago
		$start_date = new Date();
		$start_date->sub(new DateInterval('P1Y'));

		$arguments = [
			'nav_export.+class' => 'active',
			'start_date.value' => $start_date->getDate(DATE_FORMAT_CLA_SHORT_DATE)
		];

		return new TemplaterCallResponse('thankyou/admin/export.html', $arguments, lmsg('thankyou.app_name'));
	}

	/**
	 * Export the thank you notes in the given date range as a CSV file.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return Response
	 * @throws \Claromentis\Core\Csv\Exception\FilesystemException
	 * @throws \Claromentis\Core\Csv\Exception\NoDataException
	 * @throws Exception
	 */
	public function ExportCsv(Application $app, Request $request)
	{
		/**
		 * @var ThanksRepository $repository
		 */
		$repository = $app['thankyou.repository'];

		// Get the thank you notes within the given date range
		$start_date = Date::CreateFrom(gpc::get($request, 'start_date'));

		if (!$start_date)
			throw new InvalidArgumentException('Invalid start date');

		$end_date = Date::CreateFrom(gpc::get($request, 'end_date')) ?: null;

		$thanks = $repository->GetByDate($start_date, $end_date);

		// Process them into an array of values for the CSV
		$thanks_array = [];

		foreach ($thanks as $thank)
		{
			$author_name = User::GetNameById($thank->author);
			$date_created = new Date($thank->date_created);
			$thanked_user_names = implode(', ', array_map(function ($user_id) {
				return User::GetNameById($user_id);
			}, $thank->GetUsers()));

			$item = [
				$thank->id,
				$author_name,
				$date_created->getDate(DATE_FORMAT_CLA_LONG_DATE),
				$thanked_user_names,
				$thank->description
			];

			$thanks_array[] = $item;
		}

		// Create the CSV
		$csv = new Csv();
		$csv->SetHeaders(['id', 'author', 'date_created', 'thanked_users', 'description']);
		$csv->ImportFromArray($thanks_array);

		// Send it back to the user
		return $csv->Export("thankyou.csv");
	}
}