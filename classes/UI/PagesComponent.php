<?php
namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Component\ComponentInterface;
use Claromentis\Core\Component\Exception\ComponentRuntimeException;
use Claromentis\Core\Component\MutatableOptionsInterface;
use Claromentis\Core\Component\OptionsInterface;
use Claromentis\Core\Component\TemplaterTrait;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\View\ThanksListView;
use DateClaTimeZone;

/**
 * 'Thank you' component for Pages application. Shows list of latest "thanks" and optionally
 * a button to allow adding a new "thank you"
 */
class PagesComponent implements ComponentInterface, MutatableOptionsInterface
{
	use TemplaterTrait;

	private $config;

	private $lmsg;

	/**
	 * PagesComponent constructor.
	 *
	 * @param Lmsg   $lmsg
	 * @param Config $config
	 */
	public function __construct(Lmsg $lmsg, Config $config)
	{
		$this->config = $config;
		$this->lmsg   = $lmsg;
	}

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
			'title'          => ['type' => 'string', 'title' => ($this->lmsg)('thankyou.component.options.custom_title'), 'default' => '', 'placeholder' => ($this->lmsg)('thankyou.component_heading')],
			'show_header'    => ['type' => 'bool', 'title' => ($this->lmsg)('thankyou.component.options.show_header'), 'default' => true, 'mutate_on_change' => true],
			'allow_new'      => ['type' => 'bool', 'default' => true, 'title' => ($this->lmsg)('thankyou.component.options.show_button')],
			'profile_images' => ['type' => 'bool', 'default' => false, 'title' => ($this->lmsg)('thankyou.component.options.profile_images')],
			'comments'       => ['type' => 'bool', 'default' => false, 'title' => ($this->lmsg)('common.show_comments')],
			//	'user_id' => ['type' => 'int', 'title' => ($this->lmsg)('thankyou.component.options.user_id'), 'default' => 0, 'input' => 'user_picker', 'width' => 'medium'],
			'limit'          => ['type' => 'int', 'title' => ($this->lmsg)('thankyou.component.options.num_items'), 'default' => 10, 'min' => 1, 'max' => 50]
		];
	}

	/**
	 * Render this component with the specified options
	 *
	 * @param string           $id_string
	 * @param OptionsInterface $options
	 * @param Application      $app
	 *
	 * @return string
	 */
	public function ShowBody($id_string, OptionsInterface $options, Application $app)
	{
		$args = [];

		$args['ty_list.limit'] = $options->Get('limit');
		if (!is_int($args['ty_list.limit']))
		{
			throw new ComponentRuntimeException("Failed to Show Thank You Component, Limit is not an integer");
		}

		$args['ty_list.create']         = (bool) $options->Get('allow_new') && !(bool) $options->Get('show_header');
		$args['ty_list.thanked_images'] = (bool) $options->Get('profile_images');
		$args['ty_list.comments']       = (bool) $options->Get('comments');

		return $this->CallTemplater('thankyou/pages_component.html', $args);
	}

	/**
	 * Render component header with the specified options.
	 * If null or empty string is returned, header is not displayed.
	 *
	 * @param string           $id_string
	 * @param OptionsInterface $options
	 * @param Application      $app
	 *
	 * @return string
	 */
	public function ShowHeader($id_string, OptionsInterface $options, Application $app)
	{
		if (!$options->Get('show_header'))
			return null;

		if ($options->Get('allow_new'))
		{
			/**
			 * @var ThanksListView $view
			 */
			$view = $app[ThanksListView::class];
			$args = $view->ShowAddNew();
		} else
		{
			$args = ['allow_new.visible' => 0];
		}

		if ($options->Get('title') !== '')
		{
			$args['custom_title.body']     = $options->Get('title');
			$args['custom_title.visible']  = 1;
			$args['default_title.visible'] = 0;
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
			'min_height' => 4,
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
		return 'tile-thank-you';
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
			'title'       => ($this->lmsg)('thankyou.component.cover_info.title'),
			'description' => ($this->lmsg)('thankyou.component.cover_info.desc'),
			'application' => 'thankyou',
			'icon_class'  => 'glyphicons glyphicons-donate'
		];
	}

	/**
	 * Allows the component to change it's option definitions to reflect a change of values
	 *
	 * Input and output are the same array that GetOptions() returns, plus each items current value -
	 *
	 * array(
	 *   'option_name' => ['type' => ...,
	 *                     'default' => ...,
	 *                     'title' => ...,
	 *                     'value' => ...,
	 *                    ],
	 *   'other_option' => ...
	 * )
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function MutateOptions($options)
	{
		if (empty($options['show_header']['value']))
		{
			unset($options['title']);
		}

		if ($this->config->Get('thank_you_comments') !== true)
		{
			unset($options['comments']);
		}

		return $options;
	}
}
