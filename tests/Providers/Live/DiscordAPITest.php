<?php
/**
 * Class DiscordAPITest
 *
 * @created      01.01.2018
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\OAuthTest\Providers\Live;

use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\OAuth\Core\AccessToken;
use chillerlan\OAuth\Providers\Discord;
use chillerlan\OAuth\Providers\ProviderException;
use chillerlan\OAuthTest\Providers\OAuth2APITestAbstract;

/**
 * @property  \chillerlan\OAuth\Providers\Discord $provider
 */
class DiscordAPITest extends OAuth2APITestAbstract{

	protected string $ENV = 'DISCORD';

	protected function getProviderFQCN():string{
		return Discord::class;
	}

	public function testRequestCredentialsToken():void{
		$token = $this->provider->getClientCredentialsToken([Discord::SCOPE_CONNECTIONS, Discord::SCOPE_IDENTIFY]);

		$this::assertInstanceOf(AccessToken::class, $token);
		$this::assertIsString($token->accessToken);

		if($token->expires !== AccessToken::EOL_NEVER_EXPIRES){
			$this::assertGreaterThan(time(), $token->expires);
		}

		$this->logger->debug('APITestSupportsOAuth2ClientCredentials', $token->toArray());
	}

	public function testMe():void{
		try{
			$this::assertSame($this->testuser, MessageUtil::decodeJSON($this->provider->me())->username);
		}
		catch(ProviderException){
			$this::markTestSkipped('token is missing or expired');
		}
	}

}
