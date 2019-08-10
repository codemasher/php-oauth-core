<?php
/**
 * Class OAuthTestHttpClient
 *
 * @filesource   OAuthTestHttpClient.php
 * @created      26.07.2019
 * @package      chillerlan\OAuthTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\OAuthTest;

use chillerlan\HTTP\{Psr18\CurlClient, Psr18\LoggingClient};
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Psr\Log\{LoggerAwareInterface, LoggerInterface, NullLogger};

use function usleep;

class OAuthTestHttpClient implements ClientInterface, LoggerAwareInterface{

	/**
	 * @var \chillerlan\Settings\SettingsContainerInterface
	 */
	protected $options;

	/**
	 * @var \Psr\Http\Client\ClientInterface
	 */
	protected $client;

	/**
	 * OAuthTestHttpClient constructor.
	 *
	 * @param \chillerlan\Settings\SettingsContainerInterface $options
	 * @param \Psr\Http\Client\ClientInterface|null           $http
	 * @param \Psr\Log\LoggerInterface|null                   $logger
	 */
	public function __construct(SettingsContainerInterface $options, ClientInterface $http = null, LoggerInterface $logger = null){
		$this->options = $options;
		$this->client  = new LoggingClient(
			$http ?? new CurlClient($this->options),
			$logger ?? new NullLogger
		);
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 *
	 * @return void
	 */
	public function setLogger(LoggerInterface $logger):void{
		$this->client->setLogger($logger);
	}

	/**
	 * @param \Psr\Http\Message\RequestInterface $request
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		usleep($this->options->sleep * 1000000);

		return $this->client->sendRequest($request);
	}

}
