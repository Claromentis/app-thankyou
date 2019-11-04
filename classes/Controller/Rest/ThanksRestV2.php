<?php

namespace Claromentis\ThankYou\Controller\Rest;

use Claromentis\Core\Config\WritableConfig;
use Claromentis\Core\Http\JsonPrettyResponse;
use Claromentis\Core\Http\ResponseFactory;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Configuration\Configuration;
use Claromentis\ThankYou\Tags\Exceptions\TagDuplicateNameException;
use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Tag;
use Date;
use DateClaTimeZone;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RestExBadRequest;
use RestExError;
use RestExNotFound;
use RestFormat;

class ThanksRestV2
{
	//TODO: Catch the exceptions
	private $api;

	private $config;

	private $lmsg;

	private $log;

	private $response;

	private $rest_format;

	public function __construct(Api $api, ResponseFactory $response_factory, LoggerInterface $logger, RestFormat $rest_format, Lmsg $lmsg, WritableConfig $config)
	{
		$this->api         = $api;
		$this->config      = $config;
		$this->lmsg        = $lmsg;
		$this->log         = $logger;
		$this->response    = $response_factory;
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
		$display_thank_you = $this->api->ThankYous()->ConvertThankYousToArrays($thank_you, DateClaTimeZone::GetCurrentTZ(), $security_context);

		return $this->response->GetJsonPrettyResponse($display_thank_you);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return JsonPrettyResponse
	 * @throws LogicException
	 * @throws \Claromentis\ThankYou\Exception\ThankYouInvalidThankable
	 * @throws \Claromentis\ThankYou\Exception\ThankYouRuntimeException
	 */
	public function GetThankYous(ServerRequestInterface $request, SecurityContext $security_context): JsonPrettyResponse
	{
		$query_params = $request->getQueryParams();
		$limit        = $query_params['limit'] ?? 20;
		$offset       = $query_params['offset'] ?? 0;
		$thanked      = (bool) (int) ($query_params['thanked'] ?? null);

		$thank_yous         = $this->api->ThankYous()->GetRecentThankYous($limit, $offset, $thanked);
		$display_thank_yous = $this->api->ThankYous()->ConvertThankYousToArrays($thank_yous, DateClaTimeZone::GetCurrentTZ(), $security_context);

		return $this->response->GetJsonPrettyResponse($display_thank_yous);
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

		return $this->response->GetJsonPrettyResponse($tag_display);
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

		$tags = $this->api->Tag()->GetAlphabeticTags($limit, $offset);

		$tags_display = $this->ConvertTagsToArray($tags);

		return $this->response->GetJsonPrettyResponse($tags_display);
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
	 * @throws LogicException
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
		$bg_colour = $post['bg_colour'] ?? null;

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
			$response = $this->ConvertTagsToArray([$tag->GetId() => $tag]);
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
		} catch (InvalidArgumentException $exception)
		{
			$this->log->error("An unexpected Exception was thrown", [$exception]);
			return $this->response->GetJsonPrettyResponse([
				'type'   => 'https://developer.claromentis.com',
				'title'  => ($this->lmsg)('thankyou.tag.error.create'),
				'status' => 500
			], 500);
		}

		return $this->response->GetJsonPrettyResponse($response, 200);
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

		$response = [];

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

		if (isset($post['bg_colour']) && is_string($post['bg_colour']))
		{
			$tag->SetBackgroundColour($post['bg_colour']);
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

		return $this->response->GetJsonPrettyResponse($response);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param Configuration          $configuration
	 * @return JsonPrettyResponse
	 * @throws RestExBadRequest
	 */
	public function SetConfig(ServerRequestInterface $request, Configuration $configuration): JsonPrettyResponse
	{
		$post = $this->rest_format->GetJson($request);

		$options = $configuration->GetOptions();

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

			$this->config->Set($config_name, $value);
		}

		$this->api->Configuration()->SaveConfig($this->config);

		return $this->response->GetJsonPrettyResponse(true);
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

			$display_tags[$offset] = [
				'id'            => $tag->GetId(),
				'active'        => $tag->GetActive(),
				'name'          => $tag->GetName(),
				'created_by'    => $tag->GetCreatedBy()->GetFullname(),
				'created_date'  => $created_date,
				'modified_by'   => $tag->GetModifiedBy()->GetFullname(),
				'modified_date' => $modified_date,
				'bg_colour'     => $tag->GetBackgroundColour()
			];
		}

		return $display_tags;
	}
}
