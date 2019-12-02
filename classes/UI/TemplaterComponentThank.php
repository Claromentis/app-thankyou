<?php

namespace Claromentis\ThankYou\UI;

use Carbon\Carbon;
use Claromentis\Core\Application;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\Core\TextUtil\ClaText;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\ThankYous\ThankYou;
use DateClaTimeZone;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use User;

class TemplaterComponentThank extends TemplaterComponentTmpl
{
	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var ClaText
	 */
	private $cla_text;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		Api $api,
		ClaText $cla_text,
		Config $config,
		Lmsg $lmsg,
		LoggerInterface $logger
	) {
		$this->api      = $api;
		$this->cla_text = $cla_text;
		$this->config   = $config;
		$this->lmsg     = $lmsg;
		$this->logger   = $logger;
	}

	/**
	 * #Attributes
	 * ##Required
	 * * thank_you:
	 *     * int = The ID of a Thank You.
	 *     * \Claromentis\ThankYou\ThankYous\ThankYou = The Thank You to display.
	 *
	 * ##Optional
	 * * comments:
	 *     * 0 = Comments will not be accessible.(default)
	 *     * 1 = Comments will be accessible.
	 *     * 2 = Comments will be accessible and start displayed.
	 * * delete:
	 *     * 0 = Deleting the Thank You is disabled.(default)
	 *     * 1 = Deleting the Thank You is enabled (subject to permissions).
	 * * edit:
	 *     * 0 = Editing the Thank You is disabled.(default)
	 *     * 1 = Editing the Thank You is enabled (subject to permissions).
	 * * links:
	 *     * 0 = Thanked will never provide a link.(default)
	 *     * 1 = Thanked will provide a link if available.
	 * * thanked_images:
	 *     * 0 = Thanked will never display as an image.(default)
	 *     * 1 = Thanked will display as an image if available.
	 * * thank_link:
	 *     * 0 = The Thank will not provide a link to itself.(default)
	 *     * 1 = The Thank will provide a linke to iteslf.
	 * * form:
	 *     * 1 = The Form is included within the Thank You.(default)
	 *     * 0 = The Form is not included within the Thank You. This option allows Thank You's to be displayed within
	 *           the Thanks List Templater Component.
	 *
	 * @param             $attributes
	 * @param Application $app
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function Show($attributes, Application $app): string
	{
		/**
		 * @var SecurityContext $context
		 */
		$context        = $app[SecurityContext::class];
		$time_zone      = DateClaTimeZone::GetCurrentTZ();
		$can_delete     = (bool) ($attributes['delete'] ?? null);
		$can_edit       = (bool) ($attributes['edit'] ?? null);
		$form           = (bool) ($attributes['form'] ?? true);
		$links_enabled  = (bool) ($attributes['links'] ?? null);
		$thanked_images = (bool) ($attributes['thanked_images'] ?? null);

		$thank_you = $attributes['thank_you'] ?? null;
		if (!isset($thank_you))
		{
			throw new InvalidArgumentException("Failed to generate Thank You Templater Component, the attribute 'thank_you' must be supplied as either a Thank You ID, or a Thank You object.");
		}
		if (is_string($thank_you))
		{
			$thank_you = (int) $thank_you;
		}
		if (is_int($thank_you))
		{
			try
			{
				$thank_you = $this->api->ThankYous()->GetThankYou($thank_you, true, false, true);
			} catch (ThankYouNotFound $exception)
			{
				return ($this->lmsg)('thankyou.error.thanks_not_found');
			}
		}
		if (!($thank_you instanceof ThankYou))
		{
			throw new InvalidArgumentException("Failed to generate Thank You Templater Component, object of type \"\Claromentis\ThankYou\ThankYous\ThankYou\" must be given.");
		}

		$id = $thank_you->GetId();

		$display_comments_count = (bool) $this->config->Get('thank_you_comments') && isset($id);
		$access_comments        = $display_comments_count && (bool) ($attributes['comments'] ?? null);
		$display_comments       = ($access_comments && (int) $attributes['comments'] === 2);

		$total_comments = 0;
		if ($display_comments_count)
		{
			if ($thank_you->GetComment() === null)
			{
				$this->api->ThankYous()->LoadThankYousComments([$thank_you]);
			}

			$total_comments = $thank_you->GetComment()->GetTotalComments();
		}

		$can_edit_thank_you   = isset($id) && $can_edit && $this->api->ThankYous()->CanEditThankYou($thank_you, $context);
		$can_delete_thank_you = isset($id) && $can_delete && $this->api->ThankYous()->CanDeleteThankYou($thank_you, $context);

		$thank_link = ((bool) ($attributes['thank_link'] ?? null)) && isset($id);
		if ($thank_link)
		{
			$thank_you_url = $this->api->ThankYous()->GetThankYouUrl($thank_you);
		}

		$author_id   = $thank_you->GetAuthor()->GetId();
		$author_link = User::GetProfileUrl($author_id, true); // TODO: Replace with a non-static call when People API is available
		$author_name = User::GetNameById($author_id, true); // TODO: Replace with a non-static call when People API is available

		try
		{
			$author_image_url = User::GetPhotoUrl($thank_you->GetAuthor()->GetId(), true); // TODO: Replace with a non-static call when People API is available
		} catch (CDNSystemException $exception)
		{
			$this->logger->error("Error thrown when getting User's Photo's URL in Thank Templater Component: " . $exception->getMessage(), [$exception]);
			$author_image_url = null;
		}

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		$thanked_args = [];
		$thankables   = $thank_you->GetThankable();

		if (isset($thankables))
		{
			$total_thanked = count($thankables);

			$thanked_displayed = 0;
			foreach ($thankables as $thankable)
			{
				$thanked_displayed++;

				$thankable_hidden = !$this->api->ThankYous()->CanSeeThankableName($context, $thankable);

				$image_url             = $thankable_hidden ? null : $thankable->GetImageUrl();
				$thanked_link          = $thankable_hidden ? null : $thankable->GetProfileUrl();
				$display_thanked_image = !$thankable_hidden && $thanked_images && isset($image_url);
				$thanked_tooltip       = $display_thanked_image ? $thankable->GetName() : '';
				$thanked_link_enabled  = !$thankable_hidden && $links_enabled && isset($thanked_link);
				$thanked_name          = $thankable_hidden ? ($this->lmsg)('common.perms.hidden_name') : $thankable->GetName();

				$thanked_args[] = [
					'thanked_name.body'         => $thanked_name,
					'thanked_name.visible'      => !$display_thanked_image,
					'thanked_link.visible'      => $thanked_link_enabled,
					'thanked_no_link.visible'   => !$thanked_link_enabled,
					'thanked_link.href'         => $thanked_link,
					'thanked_link.title'        => $thanked_tooltip,
					'profile_image.src'         => $image_url,
					'profile_image.visible'     => $display_thanked_image,
					'delimiter_visible.visible' => !($thanked_displayed === $total_thanked)
				];
			}
		}

		$tags_args = [];
		foreach ($thank_you->GetTags() as $tag)
		{
			$tags_args[] = ['tag.tag' => $tag];
		}

		$thankable_object_types      = '';
		$first_thankable_object_type = true;
		foreach ($this->api->ThankYous()->GetThankableObjectTypes() as $object_type_id)
		{
			if ($first_thankable_object_type)
			{
				$thankable_object_types      = (string) $object_type_id;
				$first_thankable_object_type = false;
			} else
			{
				$thankable_object_types .= "," . $object_type_id;
			}
		}

		$args = [
			'thank_you.data-id'        => $id,
			'id.json'                  => $id,
			'thank_title.visible'      => !$thank_link,
			'thank_title_link.visible' => $thank_link,
			'thank_title_link.href'    => $thank_you_url ?? null,

			'thanked.datasrc' => $thanked_args,

			'tags.datasrc' => $tags_args,

			'author_name.body'  => $author_name,
			'author_link.href'  => $author_link,
			'profile_image.src' => $author_image_url,

			'description.body_html'   => $this->cla_text->ProcessPlain($thank_you->GetDescription()),
			'has_description.visible' => strlen($thank_you->GetDescription()) > 0,

			'thank_you_comment.object_id' => $id,
			'comments_count.body'         => $total_comments,
			'comments_link.visible'       => $display_comments_count,
			'comments_link.href'          => $access_comments ? 'javascript:void(0)' : ($thank_you_url ?? null),
			'comments_link.+class'        => $access_comments ? 'js-comments-reveal' : null,
			'comment_list.style'          => $display_comments ? '' : 'display:none;',

			'like_component.object_id' => $id,
			'like_component.visible'   => isset($id),

			'delete_thanks.visible'      => $can_delete_thank_you,
			'edit_thanks.visible'        => $can_edit_thank_you,
			'edit_thanks_link.data-id'   => $id,
			'delete_thanks_link.data-id' => $id,

			'date_created.body'  => Carbon::instance($date_created)->diffForHumans(),
			'date_created.title' => $date_created->getDate(DateFormatter::LONG_DATE),

			'thank_you_user.filter_perm_oclasses' => $thankable_object_types,
			'thank_you_user.placeholder'          => ($this->lmsg)('thankyou.thank.placeholder'),
			'thank_you_form.visible'              => $form
		];

		return $this->CallTemplater('thankyou/thank_you.html', $args);
	}
}
