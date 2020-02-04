<?php

namespace Claromentis\ThankYou\Controllers\Rest;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\Core\Http\ResponseFactory;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Repository\Exception\StorageException;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouForbiddenException;
use Claromentis\ThankYou\Exception\ThankYouNotFoundException;
use Claromentis\ThankYou\Exception\ValidationException;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagForbiddenException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagNotFoundException;
use Claromentis\ThankYou\Tags\Format\TagFormatter;
use Claromentis\ThankYou\ThankYous\Format\ThankYouFormatter;
use Date;
use DateClaTimeZone;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RestExBadRequest;
use RestExError;
use RestExNotFound;
use RestFormat;

class ThanksRestV2
{
	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ResponseFactory
	 */
	private $response;

	/**
	 * @var RestFormat
	 */
	private $rest_format;

	/**
	 * @var TagFormatter
	 */
	private $tag_formatter;

	/**
	 * @var ThankYouFormatter
	 */
	private $thank_you_formatter;

	/**
	 * ThanksRestV2 constructor.
	 *
	 * @param Api               $api
	 * @param ResponseFactory   $response_factory
	 * @param LoggerInterface   $logger
	 * @param RestFormat        $rest_format
	 * @param Lmsg              $lmsg
	 * @param ThankYouFormatter $thank_you_formatter
	 * @param TagFormatter      $tag_formatter
	 */
	public function __construct(
		Api $api,
		ResponseFactory $response_factory,
		LoggerInterface $logger,
		RestFormat $rest_format,
		Lmsg $lmsg,
		ThankYouFormatter $thank_you_formatter,
		TagFormatter $tag_formatter
	) {
		$this->api                 = $api;
		$this->lmsg                = $lmsg;
		$this->logger              = $logger;
		$this->response            = $response_factory;
		$this->rest_format         = $rest_format;
		$this->tag_formatter       = $tag_formatter;
		$this->thank_you_formatter = $thank_you_formatter;
	}

	/**
	 * @param int                    $id
	 * @param SecurityContext        $security_context
	 * @param ServerRequestInterface $request
	 * @return JsonPrettyResponse
	 * @throws RestExError - If the Thank You could not be created.
	 * @throws RestExNotFound - If the Thank You could not be found.
	 */
	public function GetThankYou(int $id, SecurityContext $security_context, ServerRequestInterface $request): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$thanked      = (bool) ($query_params['thanked'] ?? null);
		$users        = (bool) ($query_params['users'] ?? null);
		$tags         = (bool) ($query_params['tags'] ?? null);

		try
		{
			$thank_you = $this->api->ThankYous()->GetThankYou($id, $thanked, $users, $tags);
		} catch (ThankYouNotFoundException $exception)
		{
			throw new RestExNotFound(($this->lmsg)('thankyou.error.thanks_not_found'), "Not found", $exception);
		} catch (MappingException $exception)
		{
			throw new RestExError('Internal Server Error', 500, 'Internal Server Error', $exception);
		}

		$display_thank_you = $this->thank_you_formatter->ConvertThankYousToArrays($thank_you, DateClaTimeZone::GetCurrentTZ(), $security_context);

		return $this->response->GetJsonPrettyResponse($display_thank_you);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws RestExError - If a Repository error occurred.
	 */
	public function GetThankYous(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$limit        = $query_params['limit'] ?? 20;
		$offset       = $query_params['offset'] ?? 0;
		$get_thanked  = (bool) (int) ($query_params['thanked'] ?? null);
		$get_users    = (bool) (int) ($query_params['users'] ?? null);
		$get_tags     = (bool) (int) ($query_params['tags'] ?? null);

		try
		{
			$thank_yous = $this->api->ThankYous()->GetRecentThankYous($security_context, $get_thanked, $get_users, $get_tags, $limit, $offset);
		} catch (MappingException $exception)
		{
			throw new RestExError('Internal Server Error', 500, 'Internal Server Error', $exception);
		}

		$display_thank_yous = $this->thank_you_formatter->ConvertThankYousToArrays($thank_yous, DateClaTimeZone::GetCurrentTZ(), $security_context);

		return $this->response->GetJsonPrettyResponse($display_thank_yous);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 * @throws RestExError - If a Repository error occurred.
	 */
	public function CreateThankYou(ServerRequestInterface $request, SecurityContext $context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		$post['author'] = $context->GetUserId();

		try
		{
			$this->api->ThankYous()->CreateAndSave($post);
		} catch (ValidationException $validation_exception)
		{
			return $this->response->GetJsonPrettyResponse(
				[
					'type'           => 'https://developer.claromentis.com',
					'title'          => ($this->lmsg)('thankyou.thankyou.error.create'),
					'status'         => 400,
					'invalid-params' => $validation_exception->GetErrors()
				], 400);
		} catch (TagNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.thankyou.tags.error.not_found'),
				'status' => 404
			], 404);
		} catch (MappingException $exception)
		{
			throw new RestExError('Internal Server Error', 500, 'Internal Server Error', $exception);
		}

		return $this->response->GetJsonPrettyResponse(true);
	}

	/**
	 * @param int                    $id
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 * @throws RestExError - If a Repository error occurred.
	 */
	public function UpdateThankYou(int $id, ServerRequestInterface $request, SecurityContext $context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		$post['id'] = $id;

		try
		{
			$this->api->ThankYous()->UpdateAndSave($context, $id, $post);
		} catch (ValidationException $validation_exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'           => 'https://developer.claromentis.com',
				'title'          => ($this->lmsg)('thankyou.thankyou.error.create'),
				'status'         => 400,
				'invalid-params' => $validation_exception->GetErrors()
			], 400);
		} catch (ThankYouForbiddenException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.error.no_edit_permission'),
				'status' => 401
			], 401);
		} catch (ThankYouNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.error.thanks_not_found'),
				'status' => 404
			], 404);
		} catch (TagNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.thankyou.tags.error.not_found'),
				'status' => 404
			], 404);
		} catch (MappingException $exception)
		{
			throw new RestExError('Internal Server Error', 500, 'Internal Server Error', $exception);
		}

		return $this->response->GetJsonPrettyResponse(true);
	}

	/**
	 * @param int             $id
	 * @param SecurityContext $context
	 * @return JsonPrettyResponse
	 * @throws RestExError - If a Repository error occurred.
	 */
	public function DeleteThankYou(int $id, SecurityContext $context): JsonPrettyResponse
	{
		try
		{
			$this->api->ThankYous()->Delete($context, $id);
		} catch (ThankYouForbiddenException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.error.no_edit_permission'),
				'status' => 401
			], 401);
		} catch (ThankYouNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.error.thanks_not_found'),
				'status' => 404
			], 404);
		} catch (StorageException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.delete.error.repository'),
				'status' => 500
			], 500);
		} catch (MappingException $exception)
		{
			throw new RestExError('Internal Server Error', 500, 'Internal Server Error', $exception);
		}

		return $this->response->GetJsonPrettyResponse(true, 200);
	}

	/**
	 * @param int $id
	 * @return JsonPrettyResponse
	 * @throws RestExNotFound
	 */
	public function GetTag(int $id): JsonPrettyResponse
	{
		try
		{
			$tag = $this->api->Tag()->GetTag($id);
		} catch (TagNotFoundException $exception)
		{
			throw new RestExNotFound(($this->lmsg)('thankyou.tag.error.id.not_found', $id), "Not Found", $exception);
		}

		return $this->response->GetJsonPrettyResponse($this->tag_formatter->FormatTag($tag));
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return JsonPrettyResponse
	 */
	public function GetTags(ServerRequestInterface $request): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$limit        = (int) ($query_params['limit'] ?? 20);
		$name         = $query_params['name'] ?? null;
		$active       = isset($query_params['active']) ? (bool) $query_params['active'] : null;
		$offset       = (int) ($query_params['offset'] ?? 0);

		$tags = $this->api->Tag()->GetTags($limit, $offset, $name, $active, [['column' => 'name']]);

		return $this->response->GetJsonPrettyResponse($this->tag_formatter->FormatTags($tags));
	}

	/**
	 * @return JsonPrettyResponse
	 */
	public function GetTotalTags(): JsonPrettyResponse
	{
		return $this->response->GetJsonPrettyResponse($this->api->Tag()->GetTotalTags());
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 */
	public function CreateTag(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		$invalid_params = [];

		$name      = $post['name'] ?? null;
		$bg_colour = (isset($post['bg_colour']) && $post['bg_colour'] !== '') ? $post['bg_colour'] : null;

		if (!isset($name))
		{
			$invalid_params[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.undefined')];
		} elseif (!is_string($name))
		{
			$invalid_params[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.invalid')];
		}

		if (isset($bg_colour) && !is_string($bg_colour))
		{
			$invalid_params[] = ['name' => 'background_colour', 'reason' => ($this->lmsg)('thankyou.tag.error.background.invalid')];
		}

		if (count($invalid_params) > 0)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'           => 'https://developer.claromentis.com',
				'title'          => ($this->lmsg)('thankyou.tag.error.create'),
				'status'         => 400,
				'invalid-params' => $invalid_params
			], 400);
		}

		try
		{
			$tag = $this->api->Tag()->Create($security_context->GetUser(), $name);
			$tag->SetBackgroundColour($bg_colour);
			$this->api->Tag()->Save($tag);
		} catch (TagDuplicateNameException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'           => 'https://developer.claromentis.com',
				'title'          => ($this->lmsg)('thankyou.tag.error.create'),
				'status'         => 400,
				'invalid-params' => [['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.not_unique')]]
			], 400);
		} catch (TagInvalidNameException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'           => 'https://developer.claromentis.com',
				'title'          => ($this->lmsg)('thankyou.tag.error.create'),
				'status'         => 400,
				'invalid-params' => [['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.invalid')]]
			], 400);
		}

		return $this->response->GetJsonPrettyResponse($this->tag_formatter->FormatTag($tag), 201);
	}

	/**
	 * @param int                    $id
	 * @param ServerRequestInterface $request
	 * @param SecurityContext        $security_context
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 */
	public function UpdateTag(int $id, ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		if (!isset($post))
		{
			throw new RestExBadRequest();
		}

		$invalid_params = [];

		try
		{
			$tag = $this->api->Tag()->GetTag($id);

			$active            = $post['active'] ?? null;
			$name              = $post['name'] ?? null;
			$bg_colour_defined = array_key_exists('bg_colour', $post);
			$bg_colour         = (isset($post['bg_colour']) && $post['bg_colour'] !== '') ? $post['bg_colour'] : null;

			if (isset($active) && is_bool($active))
			{
				$tag->SetActive($active);
			}

			if (isset($name))
			{
				if (!is_string($name))
				{
					$invalid_params[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.invalid')];
				} else
				{
					try
					{
						$tag->SetName($name);
					} catch (TagInvalidNameException $exception)
					{
						$invalid_params[] = ['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.not_empty')];
					}
				}
			}

			if (isset($bg_colour) && !is_string($bg_colour))
			{
				$invalid_params[] = ['name' => 'bg_colour', 'reason' => ($this->lmsg)('thankyou.tag.error.background.invalid')];
			}

			if ($bg_colour_defined)
			{
				$tag->SetBackgroundColour($bg_colour);
			}

			$tag->SetModifiedBy($security_context->GetUser());
			$tag->SetModifiedDate(new Date());

			if (count($invalid_params) > 0)
			{
				return $this->response->GetJsonPrettyResponse([
					'type'           => 'https://developer.claromentis.com',
					'title'          => ($this->lmsg)('thankyou.tag.error.modify'),
					'status'         => 400,
					'invalid-params' => $invalid_params
				], 400);
			}

			$this->api->Tag()->Save($tag);
		} catch (TagNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.tag.error.id.not_found', $id),
				'status' => 404
			], 404);
		} catch (TagDuplicateNameException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'           => 'https://developer.claromentis.com',
				'title'          => ($this->lmsg)('thankyou.tag.error.modify'),
				'status'         => 400,
				'invalid-params' => [['name' => 'name', 'reason' => ($this->lmsg)('thankyou.tag.error.name.not_unique')]]
			], 400);
		}

		return $this->response->GetJsonPrettyResponse($this->tag_formatter->FormatTag($tag), 200);
	}

	public function DeleteTag(int $id, SecurityContext $context): JsonPrettyResponse
	{
		try
		{
			$this->api->Tag()->Delete($id, $context);
		} catch (TagForbiddenException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.tag.delete.error.permission'),
				'status' => 401
			], 401);
		} catch (TagNotFoundException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.tag.error.id.not_found', $id),
				'status' => 404
			], 404);
		} catch (StorageException $exception)
		{
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.tag.delete.error.repository'),
				'status' => 500
			], 500);
		}

		return $this->response->GetJsonPrettyResponse(true, 200);
	}

	/**
	 * Update the value of a Thank You Configuration.
	 *
	 * @param ServerRequestInterface $request
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 */
	public function SetConfig(ServerRequestInterface $request): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		$options = $this->api->Configuration()->GetConfigOptions();

		foreach ($post as $config_name => $value)
		{
			if (!isset($options[$config_name]))
			{
				throw new RestExBadRequest();
			}

			$config = $options[$config_name];

			if (isset($config['type']) && $config['type'] === 'bool' && !is_bool($value))
			{
				throw new RestExBadRequest();
			}

			$this->api->Configuration()->SetConfigValue($config_name, $value);
		}

		$this->api->Configuration()->SaveConfig();

		return $this->response->GetJsonPrettyResponse(true);
	}
}
