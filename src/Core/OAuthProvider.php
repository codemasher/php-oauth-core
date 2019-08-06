<?php
/**
 * Class OAuthProvider
 *
 * @filesource   OAuthProvider.php
 * @created      09.07.2017
 * @package      chillerlan\OAuth\Core
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\OAuth\Core;

use chillerlan\HTTP\MagicAPI\{ApiClientException, ApiClientInterface, EndpointMap, EndpointMapInterface};
use chillerlan\HTTP\Psr17\{RequestFactory, StreamFactory, UriFactory};
use chillerlan\OAuth\Storage\OAuthStorageInterface;
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{
	RequestFactoryInterface, RequestInterface, ResponseInterface,
	StreamFactoryInterface, StreamInterface, UriFactoryInterface
};
use Psr\Log\{LoggerAwareTrait, LoggerInterface, NullLogger};
use ReflectionClass;

use function array_slice, class_exists, count, http_build_query, implode,
	in_array, is_array, json_encode, sprintf, strpos, strtolower;
use function chillerlan\HTTP\Psr7\{clean_query_params, merge_query};

use const PHP_QUERY_RFC1738;
use const chillerlan\HTTP\Psr7\{BOOLEANS_AS_INT_STRING, BOOLEANS_AS_BOOL};

/**
 * @property string                                         $accessTokenURL
 * @property string                                         $authURL
 * @property string                                         $apiDocs
 * @property string                                         $apiURL
 * @property string                                         $applicationURL
 * @property \chillerlan\HTTP\MagicAPI\EndpointMapInterface $endpoints
 * @property string                                         $revokeURL
 * @property string                                         $serviceName
 * @property string                                         $userRevokeURL
 */
abstract class OAuthProvider implements OAuthInterface{
	use LoggerAwareTrait;

	protected const ALLOWED_PROPERTIES = [
		'accessTokenURL', 'apiDocs', 'apiURL', 'applicationURL', 'authURL', 'endpoints', 'revokeURL', 'serviceName', 'userRevokeURL'
	];

	/**
	 * @var \Psr\Http\Client\ClientInterface
	 */
	protected $http;

	/**
	 * @var \chillerlan\OAuth\Storage\OAuthStorageInterface
	 */
	protected $storage;

	/**
	 * @var \chillerlan\OAuth\OAuthOptions
	 */
	protected $options;

	/**
	 * @var \chillerlan\HTTP\MagicAPI\EndpointMapInterface
	 */
	protected $endpoints;

	/**
	 * @var \Psr\Http\Message\RequestFactoryInterface
	 */
	protected $requestFactory;

	/**
	 * @var \Psr\Http\Message\StreamFactoryInterface
	 */
	protected $streamFactory;

	/**
	 * @var \Psr\Http\Message\UriFactoryInterface
	 */
	protected $uriFactory;

	/**
	 * @var string
	 */
	protected $serviceName;

	/**
	 * @var string
	 */
	protected $authURL;

	/**
	 * @var string
	 */
	protected $apiDocs;

	/**
	 * @var string
	 */
	protected $apiURL = '';

	/**
	 * @var string
	 */
	protected $applicationURL;

	/**
	 * @var string
	 */
	protected $userRevokeURL;

	/**
	 * @var string
	 */
	protected $revokeURL;

	/**
	 * @var string
	 */
	protected $accessTokenURL;

	/**
	 * @var string FQCN
	 */
	protected $endpointMap;

	/**
	 * @var array
	 */
	protected $authHeaders = [];

	/**
	 * @var array
	 */
	protected $apiHeaders = [];

	/**
	 * OAuthProvider constructor.
	 *
	 * @param \Psr\Http\Client\ClientInterface                $http
	 * @param \chillerlan\OAuth\Storage\OAuthStorageInterface $storage
	 * @param \chillerlan\Settings\SettingsContainerInterface $options
	 * @param \Psr\Log\LoggerInterface|null                   $logger
	 *
	 * @throws \chillerlan\HTTP\MagicAPI\ApiClientException
	 */
	public function __construct(ClientInterface $http, OAuthStorageInterface $storage, SettingsContainerInterface $options, LoggerInterface $logger = null){
		$this->http    = $http;
		$this->storage = $storage;
		$this->options = $options;
		$this->logger  = $logger ?? new NullLogger;

		$this->requestFactory = new RequestFactory;
		$this->streamFactory  = new StreamFactory;
		$this->uriFactory     = new UriFactory;

		$this->serviceName = (new ReflectionClass($this))->getShortName();

		if($this instanceof ApiClientInterface && !empty($this->endpointMap) && class_exists($this->endpointMap)){
			$this->endpoints = new $this->endpointMap;

			if(!$this->endpoints instanceof EndpointMapInterface){
				throw new ApiClientException('invalid endpoint map'); // @codeCoverageIgnore
			}

		}

	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function __get(string $name){

		if(in_array($name, $this::ALLOWED_PROPERTIES, true)){
			return $this->{$name};
		}

		return null;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setStorage(OAuthStorageInterface $storage):OAuthInterface{
		$this->storage = $storage;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setRequestFactory(RequestFactoryInterface $requestFactory):OAuthInterface{
		$this->requestFactory = $requestFactory;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setStreamFactory(StreamFactoryInterface $streamFactory):OAuthInterface{
		$this->streamFactory = $streamFactory;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setUriFactory(UriFactoryInterface $uriFactory):OAuthInterface{
		$this->uriFactory = $uriFactory;

		return $this;
	}

	/**
	 * ugly, isn't it?
	 *
	 * @todo WIP
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \chillerlan\HTTP\MagicAPI\ApiClientException
	 * @codeCoverageIgnore
	 */
	public function __call(string $name, array $arguments):ResponseInterface{

		if(!$this instanceof ApiClientInterface || !$this->endpoints instanceof EndpointMap){
			throw new ApiClientException('MagicAPI not available');
		}

		if(!$this->endpoints->__isset($name)){
			throw new ApiClientException('endpoint not found: "'.$name.'"');
		}

		$m = $this->endpoints->{$name};

		$endpoint      = $this->endpoints->API_BASE.$m['path'];
		$method        = $m['method'] ?? 'GET';
		$body          = [];
		$headers       = isset($m['headers']) && is_array($m['headers']) ? $m['headers'] : [];
		$path_elements = $m['path_elements'] ?? [];
		$params_in_url = count($path_elements);
		$params        = $arguments[$params_in_url] ?? [];
		$urlparams     = array_slice($arguments,0 , $params_in_url);

		if($params_in_url > 0){

			if(count($urlparams) < $params_in_url){
				throw new APIClientException('too few URL params, required: '.implode(', ', $path_elements));
			}

			$endpoint = sprintf($endpoint, ...$urlparams);
		}

		if(in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'])){
			$body = $arguments[$params_in_url + 1] ?? $params;

			if($params === $body){
				$params = [];
			}

			$body = $this->cleanBodyParams($body);
		}

		$params = $this->cleanQueryParams($params);

		$this->logger->debug('OAuthProvider::__call() -> '.$this->serviceName.'::'.$name.'()', [
			'$endpoint' => $endpoint, '$params' => $params, '$method' => $method, '$body' => $body, '$headers' => $headers,
		]);

		return $this->request($endpoint, $params, $method, $body, $headers);
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 * @codeCoverageIgnore
	 */
	protected function cleanQueryParams(array $params):array{
		return clean_query_params($params, BOOLEANS_AS_INT_STRING, true);
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 * @codeCoverageIgnore
	 */
	protected function cleanBodyParams(array $params):array{
		return clean_query_params($params, BOOLEANS_AS_BOOL, true);
	}

	/**
	 * @inheritDoc
	 */
	public function request(string $path, array $params = null, string $method = null, $body = null, array $headers = null):ResponseInterface{

		$request = $this->requestFactory
			->createRequest($method ?? 'GET', merge_query($this->apiURL.$path, $params ?? []));

		foreach(array_merge($this->apiHeaders, $headers ?? []) as $header => $value){
			$request = $request->withAddedHeader($header, $value);
		}

		if(is_array($body) && $request->hasHeader('content-type')){
			$contentType = strtolower($request->getHeaderLine('content-type'));

			// @todo: content type support
			if($contentType === 'application/x-www-form-urlencoded'){
				$body = $this->streamFactory->createStream(http_build_query($body, '', '&', PHP_QUERY_RFC1738));
			}
			elseif($contentType === 'application/json'){
				$body = $this->streamFactory->createStream(json_encode($body));
			}

		}

		if($body instanceof StreamInterface){
			$request = $request
				->withBody($body)
				->withHeader('Content-length', $body->getSize())
			;
		}

		return $this->sendRequest($request);
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{

		// get authorization only if we request the provider API
		if(strpos((string)$request->getUri(), $this->apiURL) === 0){
			$token = $this->storage->getAccessToken($this->serviceName);

			// attempt to refresh an expired token
			if($this instanceof TokenRefresh && $this->options->tokenAutoRefresh && ($token->isExpired() || $token->expires === $token::EOL_UNKNOWN)){
				$token = $this->refreshAccessToken($token);
			}

			$request = $this->getRequestAuthorization($request, $token);
		}

		return $this->http->sendRequest($request);
	}

}
