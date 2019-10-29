<?php

namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Tag;
use Date;
use DateClaTimeZone;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;
use RestExBadRequest;
use RestExError;
use RestExNotFound;
use RestFormat;

class ThanksRestV2
{
	//TODO: Catch the exceptions
	private $api;

	private $lmsg;

	private $rest_format;

	public function __construct(Api $api, RestFormat $rest_format, Lmsg $lmsg)
	{
		$this->api         = $api;
		$this->lmsg        = $lmsg;
		$this->rest_format = $rest_format;
	}

	/**
	 * @param int             $id
	 * @param SecurityContext $security_context
	 * @return JsonPrettyResponse
	 * @throws InvalidArgumentException
	 * @throws LogicException
	 * @throws \Claromentis\ThankYou\Exception\ThankYouInvalidThankable
	 * @throws \Claromentis\ThankYou\Exception\ThankYouNotFound
	 * @throws \Claromentis\ThankYou\Exception\ThankYouRuntimeException
	 */
	public function GetThankYou(int $id, SecurityContext $security_context): JsonPrettyResponse
	{
		$extranet_area_id  = (int) $security_context->GetExtranetAreaId();
		$thank_you         = $this->api->ThankYous()->GetThankYous($id, true);
		$display_thank_you = $this->api->ThankYous()->ConvertThankYousToArrays($thank_you, DateClaTimeZone::GetCurrentTZ(), $extranet_area_id);

		return new JsonPrettyResponse($display_thank_you);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws LogicException
	 * @throws \Claromentis\ThankYou\Exception\ThankYouInvalidThankable
	 * @throws \Claromentis\ThankYou\Exception\ThankYouRuntimeException
	 */
	public function GetThankYous(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$extranet_area_id = (int) $security_context->GetExtranetAreaId();
		$query_params     = $request->getQueryParams();
		$limit            = $query_params['limit'] ?? 20;
		$offset           = $query_params['offset'] ?? 0;
		$thanked          = (bool) (int) ($query_params['thanked'] ?? null);

		$thank_yous         = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, $thanked, $extranet_area_id);
		$display_thank_yous = $this->api->ThankYous()->ConvertThankYousToArrays($thank_yous, DateClaTimeZone::GetCurrentTZ(), $extranet_area_id);

		return new JsonPrettyResponse($display_thank_yous);
	}

	/**
	 * @param int $id
	 * @return JsonPrettyResponse
	 * @throws InvalidArgumentException
	 * @throws LogicException
	 * @throws RestExNotFound
	 */
	public function GetTag(int $id): JsonPrettyResponse
	{
		try
		{
			$tag = $this->api->Tag()->GetTag($id);
		} catch (OutOfBoundsException $exception)
		{
			throw new RestExNotFound(($this->lmsg)('thankyou.tag.error.id.not_found'));
		}

		$tag_display = $this->ConvertTagsToArray([$tag])[0];

		return new JsonPrettyResponse($tag_display);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return JsonPrettyResponse
	 * @throws LogicException
	 */
	public function GetTags(ServerRequestInterface $request): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$limit        = (int) ($query_params['limit'] ?? 20);
		$offset       = (int) ($query_params['offset'] ?? 0);

		$tags = $this->api->Tag()->GetActiveAlphabeticTags($limit, $offset);

		$tags_display = $this->ConvertTagsToArray($tags);

		return new JsonPrettyResponse($tags_display);
	}

	/**
	 * @return JsonPrettyResponse
	 */
	public function GetTotalTags(): JsonPrettyResponse
	{
		return new JsonPrettyResponse($this->api->Tag()->GetTotalTags());
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 * @throws LogicException
	 */
	public function CreateTag(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		if (!isset($post['name']) || !is_string($post['name']))
		{
			throw new RestExBadRequest(($this->lmsg)('thankyou.tag.error.name.undefined'));
		}

		if (isset($post['metadata']) && !is_array($post['metadata']))
		{
			throw new RestExBadRequest(($this->lmsg)('thankyou.tag.error.metadata.invalid'));
		}

		try
		{
			$tag      = $this->api->Tag()->Create($security_context->GetUser(), $post['name'], $post['metadata'] ?? null);
			$this->api->Tag()->Save($tag);
			$response = $this->ConvertTagsToArray([$tag->GetId() => $tag]);
		} catch (TagDuplicateNameException $exception)
		{
			$response['errors']['name'][] = ($this->lmsg)('thankyou.tag.error.name.not_unique');
		} catch (TagInvalidNameException $exception)
		{
			$response['errors']['name'][] = ($this->lmsg)('thankyou.tag.error.name.invalid');
		} catch (InvalidArgumentException $exception)
		{
			throw new LogicException("Failed to Create Tag, an unexpected Exception was thrown when saving", null, $exception);
		}

		return new JsonPrettyResponse($response);
	}

	/**
	 * @param int                    $id
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws LogicException
	 * @throws RestExBadRequest
	 * @throws RestExNotFound
	 */
	public function UpdateTag(int $id, ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		$response=[];

		try
		{
			$tag = $this->api->Tag()->GetTag($id);
		} catch (OutOfBoundsException $exception)
		{
			throw new RestExNotFound(($this->lmsg)('thankyou.tag.error.id.not_found'));
		}

		if (isset($post['active']) && is_bool($post['active']))
		{
			$tag->SetActive($post['active']);
		}

		if (isset($post['name']) && is_string($post['name']))
		{
			try
			{
				$tag->SetName($post['name']);
			} catch (TagInvalidNameException $exception)
			{
				$response['errors']['name'] = ($this->lmsg)('thankyou.tag.error.name.invalid');
			}
		}

		if (array_key_exists('metadata', $post) && (!isset($post['metadata']) || is_array($post['metadata'])))
		{
			$tag->SetMetadata($post['metadata']);
		}

		$tag->SetModifiedBy($security_context->GetUser());
		$tag->SetModifiedDate(new Date());

		if (!isset($response['errors']))
		{
			try
			{
				$this->api->Tag()->Save($tag);

				$response = $this->ConvertTagsToArray([$tag->GetId() => $tag]);
			} catch (TagDuplicateNameException $exception)
			{
				$response['errors']['name'] = ($this->lmsg)('thankyou.tag.error.name.not_unique');
			}
		}

		return new JsonPrettyResponse($response);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 * @throws RestExError
	 */
	public function ListableItemsAdminSave(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		$response = [];
		try
		{
			if (isset($post['created']))
			{
				foreach ($post['created'] as $form_id => $item)
				{
					$name = $item['name'] ?? null;
					$bg_colour = $item['bg_colour'] ?? null;
					$active = $item['active'] ?? null;

					if (!isset($name) || !is_string($name))
					{
						$response['errors'][$form_id]['name'] = ($this->lmsg)('thankyou.tag.error.name.invalid');
						continue;
					}

					if (!isset($bg_colour) || !is_string($bg_colour))
					{
						$response['errors'][$form_id]['bg_colour'] = ($this->lmsg)('thankyou.tag.error.background.undefined');
						continue;
					}

					try
					{
						//TODO read Active also!
						$tag = $this->api->Tag()->Create($security_context->GetUser(), $name, ['bg_colour' => $item['bg_colour']]);

						if (isset($active))
						{
							if (!is_bool($active))
							{
								$response['errors'][$form_id]['active'] = ($this->lmsg)('thankyou.tag.error.active.invalid');
								continue;
							}
							$tag->SetActive($active);
						}

						$this->api->Tag()->Save($tag);
					} catch (TagDuplicateNameException $exception)
					{
						$response['errors'][$form_id]['name'] = ($this->lmsg)('thankyou.tag.error.name.not_unique');
					} catch (TagInvalidNameException $exception)
					{
						$response['errors'][$form_id]['name'] = ($this->lmsg)('thankyou.tag.error.name.invalid');
					} catch (InvalidArgumentException $exception)
					{
						throw new RestExError($exception->getMessage(), 500, "Internal Server Error", $exception);
					}
				}
			}

			if (isset($post['modified']))
			{
				foreach ($post['modified'] as $id => $item)
				{
					try
					{
						$tag = $this->api->Tag()->GetTag($id);
					} catch (OutOfBoundsException $exception)
					{
						$response['errors'][$id]['name'] = ($this->lmsg)('thankyou.tag.error.id.not_found');
						continue;
					}

					$active = $item['active'] ?? null;
					$name   = $item['name'] ?? null;
					$bg_colour   = $item['bg_colour'] ?? null;

					if (isset($active))
					{
						if (!is_bool($active))
						{
							$response['errors'][$id]['active'] = ($this->lmsg)('thankyou.tag.error.active.invalid');
							continue;
						}
						$tag->SetActive($active);
					}

					if (isset($name))
					{
						if (!is_string($name))
						{
							$response['errors'][$id]['name'] = ($this->lmsg)('thankyou.tag.error.name.invalid');
							continue;
						}
						try
						{
							$tag->SetName($name);
						} catch (TagInvalidNameException $exception)
						{
							$response['errors'][$id]['name'] = ($this->lmsg)('thankyou.tag.error.name.invalid');
							continue;
						}
					}

					if (isset($bg_colour))
					{
						if(!is_string($bg_colour))
						{
							$response['errors'][$id]['bg_colour'] = ($this->lmsg)('thankyou.tag.error.background.undefined');
							continue;
						}
						$tag->SetMetadata(['bg_colour' => $bg_colour]);
					}

					$tag->SetModifiedBy($security_context->GetUser());
					$tag->SetModifiedDate(new Date());

					try
					{
						$this->api->Tag()->Save($tag);
					} catch (TagDuplicateNameException $exception)
					{
						$response['errors'][$id]['name'] = ($this->lmsg)('thankyou.tag.error.name.not_unique');
						continue;
					}
				}
			}

			if (isset($post['deleted']))
			{
				foreach ($post['deleted'] as $id)
				{
					$this->api->Tag()->Delete($id);
				}
			}
		} catch (LogicException $exception)
		{
			throw new RestExError($exception->getMessage(), 500, "Internal Server Error", $exception);
		}

		return new JsonPrettyResponse($response);
	}

	/**
	 * @param Tag[] $tags
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function ConvertTagsToArray(array $tags): array
	{
		$display_tags = [];
		foreach ($tags as $offset => $tag)
		{
			if (!($tag instanceof Tag))
			{
				throw new InvalidArgumentException("Failed to Convert Tags to an array, input must be an array of Tags only");
			}

			$created_date = clone $tag->GetCreatedDate();
			$created_date->setTimezone(DateClaTimeZone::GetCurrentTZ());
			$created_date = $this->rest_format->Date($created_date);

			$modified_date = clone $tag->GetModifiedDate();
			$modified_date->setTimezone(DateClaTimeZone::GetCurrentTZ());
			$modified_date = $this->rest_format->Date($modified_date);

			$metadata = $tag->GetMetadata();

			$display_tags[$offset] = [
				'id'            => $tag->GetId(),
				'active'        => $tag->GetActive(),
				'name'          => $tag->GetName(),
				'created_by'    => $tag->GetCreatedBy()->GetFullname(),
				'created_date'  => $created_date,
				'modified_by'   => $tag->GetModifiedBy()->GetFullname(),
				'modified_date' => $modified_date,
				'bg_colour'     => $metadata['bg_colour'] ?? null
			];
		}

		return $display_tags;
	}
}
