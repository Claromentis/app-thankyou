<?php
namespace Claromentis\ThankYou\Controller;

use Claromentis\Core\Application;
use Claromentis\Core\Csv\Csv;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Http\gpc;
use Claromentis\Core\Http\TemplaterCallResponse;
use Claromentis\ThankYou\ThanksRepository;
use Date;
use DateInterval;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use User;

/**
 * The admin panel controller.
 */
class AdminExportController
{
	/**
	 * Show the export admin panel.
	 *
	 * @param Application $app
	 * @param ServerRequestInterface $request
	 *
	 * @return TemplaterCallResponse
	 * @throws Exception
	 */
	public function ShowExportPanel(Application $app, ServerRequestInterface $request)
	{
		// Set the initial start date to 1 year ago
		$start_date = new Date();
		$start_date->sub(new DateInterval('P1Y'));

		// Set the inital end date to today
		$end_date = new Date();

		$args = [
			'nav_export.+class' => 'active',
			'start_date.value' => $start_date->getDate(DateFormatter::SHORT_DATE),
			'end_date.value' => $end_date->getDate(DateFormatter::SHORT_DATE),
		];

		return new TemplaterCallResponse('thankyou/admin/export.html', $args, lmsg('thankyou.app_name'));
	}

	/**
	 * Export the thank you notes in the given date range as a CSV file.
	 *
	 * @param Application $app
	 * @param ServerRequestInterface $request
	 *
	 * @return Response
	 * @throws \Claromentis\Core\Csv\Exception\FilesystemException
	 * @throws \Claromentis\Core\Csv\Exception\NoDataException
	 * @throws Exception
	 */
	public function ExportCsv(Application $app, ServerRequestInterface $request)
	{
		/**
		 * @var ThanksRepository $repository
		 */
		$repository = $app['thankyou.repository'];

		// Get the thank you notes within the given date range
		$start_date = Date::CreateFrom(gpc::get($request, 'start_date'));

		if (!$start_date)
			throw new InvalidArgumentException('Invalid start date');

		$start_date = $start_date->getStartOfDay();

		$end_date = Date::CreateFrom(gpc::get($request, 'end_date'));

		if ($end_date)
			$end_date = $end_date->getEndOfDay();

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
				$date_created->getDate(DATE_FORMAT_ISO),
				$thanked_user_names,
				$thank->description,
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