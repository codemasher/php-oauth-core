<?php
/**
 * Class FoursquareAPITest
 *
 * @created      10.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\OAuthTest\Providers\Live;

use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\OAuth\Providers\Foursquare;
use chillerlan\OAuth\Providers\ProviderException;
use chillerlan\OAuthTest\Providers\OAuth2APITestAbstract;

/**
 * Foursquare API usage tests/examples
 *
 * @link https://developer.foursquare.com/docs
 *
 * @property \chillerlan\OAuth\Providers\Foursquare $provider
 */
class FoursquareAPITest extends OAuth2APITestAbstract{

	protected string $ENV = 'FOURSQUARE';

	protected function getProviderFQCN():string{
		return Foursquare::class;
	}

	public function testMe():void{
		try{
			$this::assertSame($this->testuser, MessageUtil::decodeJSON($this->provider->me())->response->user->id);
		}
		catch(ProviderException){
			$this::markTestSkipped('token is missing or expired');
		}
	}

}
