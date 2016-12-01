<?php
namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Component\ComponentInterface;
use Claromentis\Core\Component\OptionsInterface;
use Claromentis\Core\Component\TemplaterTrait;
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
			'allow_new' => ['type' => 'bool', 'default' => true, 'title' => 'Show "Say thank you" button'],
			'show_header' => ['type' => 'bool', 'title' => 'Show header', 'default' => true],
			'limit' => ['type' => 'int', 'title' => 'Number of items to show', 'default' => 10],
			'user_id' => ['type' => 'int', 'title' => 'User ID to show thanks for one user only', 'default' => 0, 'input' => 'user_picker'],
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
	public function ShowBody($id_string, OptionsInterface $options, Application $app)
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

	/**
	 * Returns CSS class name to be set on component tile when it's displayed.
	 * This class then can be used to change the display style.
	 *
	 * Recommended class name is 'tile-' followed by full component code.
	 *
	 * It also can be empty.
	 *
	 * @return string
	 */
	public function GetCssClass()
	{
		return '';
	}

	/**
	 * Returns associative array with description of this component to be displayed for users in the
	 * components list.
	 *
	 * Result array has these keys:
	 *   title       - Localized component title, up to 40 characters
	 *   description - A paragraph-size plain text description of the component, without linebreaks or HTML
	 *   image       - Image URL
	 *   application - One-word lowercase application CODE (same as folder name and admin panel code)
	 *
	 * Some values may be missing - reasonable defaults will be used. But it's strongly recommended to have
	 * at least title.
	 *
	 * @return array
	 */
	public function GetCoverInfo()
	{
		return [
			'title' => 'Thank you',
			'description' => 'Allows users to tag someone and publicly say thank you. Displays list of recent "thanks"',
			'application' => 'thankyou',
			'icon_class' => 'glyphicons glyphicons-handshake'
		];
	}
}