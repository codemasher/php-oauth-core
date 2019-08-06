<?php
/**
 * Interface ClientCredentials
 *
 * @filesource   ClientCredentials.php
 * @created      29.01.2018
 * @package      chillerlan\OAuth\Core
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\OAuth\Core;

interface ClientCredentials{

	/**
	 * Obtains an OAuth2 client credentials token and returns an AccessToken
	 *
	 * @link https://tools.ietf.org/html/rfc6749#section-4.4
	 *
	 * @param array $scopes
	 *
	 * @return \chillerlan\OAuth\Core\AccessToken
	 * @throws \chillerlan\OAuth\Core\ProviderException
	 */
	public function getClientCredentialsToken(array $scopes = null):AccessToken;

}
