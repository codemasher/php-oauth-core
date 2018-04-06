<?php
/**
 * Class GenericOAuth2Test
 *
 * @filesource   GenericOAuth2Test.php
 * @created      06.04.2018
 * @package      chillerlan\OAuthTest\Providers
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\OAuthTest\Providers;

use chillerlan\OAuthExamples\OAuth2Testprovider;

/**
 * @property \chillerlan\OAuthExamples\OAuth2Testprovider $provider
 */
class GenericOAuth2Test extends OAuth2Test{
	use SupportsOAuth2ClientCredentials, SupportsOAuth2TokenRefresh;

	protected $FQCN = OAuth2Testprovider::class;
}
