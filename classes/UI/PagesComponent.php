<?php
namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Pages\Api\ComponentInterface;
use Claromentis\Pages\Api\OptionsInterface;
use Claromentis\Pages\Api\TemplaterTrait;
use Claromentis\ThankYou\ThanksRepository;
use Claromentis\ThankYou\View\ThanksListView;

/**
 * 'Thank you' component for Pages application. Shows list of latest "thanks" and optionally
 * a button to allow adding a new "thank you"
 */
class PagesComponent implements ComponentInterface
{
	use TemplaterTrait;

	/**
	 * Returns information about supported options for this component as array
	 *
	 * array(
	 *   'option_name' => ['type' => ...,
	 *                     'default' => ...,
	 *                     'title' => ...,
	 *                    ],
	 *   'other_option' => ...
	 * )
	 *
	 * @return array
	 */
	public function GetOptions()
	{
		return [
			'allow_new' => ['type' => 'bool', 'title' => 'Show "Say thank you" button', 'default' => true],
			'show_header' => ['type' => 'bool', 'title' => 'Show header', 'default' => true],
			'limit' => ['type' => 'int', 'title' => 'Number of items to show', 'default' => 10],
			'user_id' => ['type' => 'int', 'title' => 'User ID to show thanks for one user only', 'default' => null],
		];
	}

	/**
	 * Render this component with the specified options
	 *
	 * @param string $id_string
	 * @param OptionsInterface $options
	 * @param Application $app
	 *
	 * @return string
	 */
	public function Show($id_string, OptionsInterface $options, Application $app)
	{
		$args = array();

		/** @var ThanksRepository $repository */
		$repository = $app['thankyou.repository'];

		$user_id = $options->Get('user_id');
		$limit = $options->Get('limit');

		if ($user_id)
			$thanks = $repository->GetForUser($user_id, $limit);
		else
			$thanks = $repository->GetRecent($limit);

		$view = new ThanksListView();
		$args['items.datasrc'] = $view->Show($thanks);

		// show "say thank you" within body if header is hidden
		if ($options->Get('allow_new') && !$options->Get('show_header'))
		{
			$args = $view->ShowAddNew($user_id) + $args;
		} else
		{
			$args['allow_new.visible'] = 0;
		}

		$template = 'thankyou/pages_component.html';
		return $this->CallTemplater($template, $args);
	}

	/**
	 * Render component header with the specified options.
	 * If null or empty string is returned, header is not displayed.
	 *
	 * @param string $id_string
	 * @param OptionsInterface $options
	 * @param Application $app
	 *
	 * @return string
	 */
	public function ShowHeader($id_string, OptionsInterface $options, Application $app)
	{
		if (!$options->Get('show_header'))
			return null;

		$user_id = $options->Get('user_id');

		if ($options->Get('allow_new'))
		{
			$view = new ThanksListView();
			$args = $view->ShowAddNew($user_id);
		} else
		{
			$args = ['allow_new.visible' => 0];
		}

		$template = 'thankyou/pages_component_header.html';
		return $this->CallTemplater($template, $args);
	}

	/**
	 * Define any minimum or maximum size constraints that this component has.
	 * Widths are measured in 12ths of the page as with Bootstrap.
	 * Heights are measured in multiples of the grid row height (around 47 pixels currently?)
	 *
	 * @return array should contain any combination of min_width, max_width, min_height and max_height.
	 */
	public function GetSizeConstraints()
	{
		return [
			'min_height' => 2,
		];
	}
}