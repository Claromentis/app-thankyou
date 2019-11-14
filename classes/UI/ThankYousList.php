<?php

namespace Claromentis\ThankYou\UI;

use Claromentis\Core\Application;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\ThankYous\Thankable;
use Psr\Log\LoggerInterface;

/**
 * Templater Component for displaying a list of recent Thank Yous and for submitting a new one.
 * To load add "<component class_key='thankyou.list'/>"
 * #Attributes
 * * comments:
 *     * 0 = Thank Yous Comments are hidden.
 *     * 1 = Thank Yous Comments are shown.
 * * create:
 *     * 0 = Creating Thank Yous is disabled.
 *     * 1 = Creating Thank Yous is enabled.
 *     * Thankable[] = Creating ThankYous is locked to the array of Thankables given.
 * * delete:
 *     * 0 = Deleting Thank Yous is disabled.
 *     * 1 = Deleting Thank Yous is enabled (subject to permissions).
 * * edit:
 *     * 0 = Editing Thank Yous is disabled.
 *     * 1 = Editing Thank Yous is enabled (subject to permissions).
 * * thanked_images:
 *     * 0 = Thanked will never display as an image.
 *     * 1 = Thanked will display as an image if available.
 * * thanks_links:
 *     * 0 = Thanks will not provide a link to themselves.(default)
 *     * 1 = Thanks will provide a link to themselves.
 * * links:
 *     * 0 = Thanked will never provide a link.
 *     * 1 = Thanked will provide a link if available.
 * * limit:
 *     * int = How many Thank Yous to display.
 * * offset:
 *     * int = Offset of Thank Yous.
 * * user_id:
 *     * int  = Only display Thank Yous associated with this User.
 *
 **/
//TODO: Add AJAX callback to populate template. Add pagination supported by it.
class ThankYousList extends TemplaterComponentTmpl
{
	/**
	 * @var Api $api
	 */
	private $api;

	/**
	 * @var Lmsg $lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface $log
	 */
	private $log;

	/**
	 * ThankYousList constructor.
	 *
	 * @param Api             $api
	 * @param Lmsg            $lmsg
	 * @param LoggerInterface $logger
	 */
	public function __construct(Api $api, Lmsg $lmsg, LoggerInterface $logger)
	{
		$this->api  = $api;
		$this->lmsg = $lmsg;
		$this->log  = $logger;
	}

	/**
	 * #Attributes
	 * * comments:
	 *     * 0 = Thank Yous Comments are hidden.
	 *     * 1 = Thank Yous Comments are shown.
	 * * create:
	 *     * 0 = Creating Thank Yous is disabled.
	 *     * 1 = Creating Thank Yous is enabled.
	 *     * array = Creating ThankYous is locked to the Thankable array given (Created with \Claromentis\ThankYou\View\ThanksListView::ConvertThankableToArray).
	 * * delete:
	 *     * 0 = Deleting Thank Yous is disabled.
	 *     * 1 = Deleting Thank Yous is enabled (subject to permissions or admin_mode).
	 * * edit:
	 *     * 0 = Editing Thank Yous is disabled.
	 *     * 1 = Editing Thank Yous is enabled (subject to permissions or admin_mode).
	 * * thanked_images:
	 *     * 0 = Thanked will never display as an image.
	 *     * 1 = Thanked will display as an image if available.
	 * * thanks_links:
	 *     * 0 = Thanks will not provide a link to themselves.(default)
	 *     * 1 = Thanks will provide a link to themselves.
	 * * links:
	 *     * 0 = Thanked will never provide a link.
	 *     * 1 = Thanked will provide a link if available.
	 * * limit:
	 *     * int = How many Thank Yous to display.
	 * * offset:
	 *     * int = Offset of Thank Yous.
	 * * user_id:
	 *     * int  = Only display Thank Yous associated with this User.
	 *
	 * @param array       $attributes
	 * @param Application $app
	 * @return string
	 */
	public function Show($attributes, Application $app): string
	{
		$can_create = (bool) ($attributes['create'] ?? null);
		$can_delete = (bool) ($attributes['delete'] ?? null);
		$can_edit   = (bool) ($attributes['edit'] ?? null);
		/**
		 * @var Thankable[] $create_thankables
		 */
		$create_thankables = (isset($attributes['create']) && is_array($attributes['create'])) ? $attributes['create'] : null;
		$display_comments  = (bool) ($attributes['comments'] ?? null);
		$thanked_images    = (bool) ($attributes['thanked_images'] ?? null);
		$links             = (bool) ($attributes['links'] ?? null);
		$limit             = (int) ($attributes['limit'] ?? 20);
		$offset            = (int) ($attributes['offset'] ?? null);
		$thanks_links      = (bool) ($attributes['thanks_links'] ?? null);
		$user_id           = (isset($attributes['user_id'])) ? (int) $attributes['user_id'] : null;

		$thank_yous = [];
		try
		{
			if (isset($user_id))
			{
				$thank_yous = $this->api->ThankYous()->GetUsersRecentThankYous($user_id, $limit, $offset, true, false, true);
			} else
			{
				$thank_yous = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, true, false, true);
			}
		} catch (ThankYouOClass $exception)
		{
			$this->log->error("Failed to display Thank Yous in Templater Component 'thankyou.list'", [$exception]);
		}

		$args            = [];
		$view_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			$view_thank_yous[] = [
				'thank_you.comments'       => $display_comments,
				'thank_you.delete'         => $can_delete,
				'thank_you.edit'           => $can_edit,
				'thank_you.links'          => $links,
				'thank_you.thanked_images' => $thanked_images,
				'thank_you.thank_link'     => $thanks_links,
				'thank_you.thank_you'      => $thank_you
			];
		}

		$args['thank_yous.datasrc'] = $view_thank_yous;
		$class                      = uniqid();
		$args['list.+class']        = $class;
		$args['class.json']         = $class;

		if (count($args['thank_yous.datasrc']) === 0)
		{
			$args['no_thanks.body'] = ($this->lmsg)('thankyou.thanks_list.no_thanks');
		}

		if ($can_create)
		{
			$args['create_container.visible'] = 1;
			if (isset($create_thankables))
			{
				$args['create.thankables'] = $create_thankables;
			}
		} else
		{
			$args['create.visible'] = 0;
		}

		return $this->CallTemplater('thankyou/thank_yous_list.html', $args);
	}
}
