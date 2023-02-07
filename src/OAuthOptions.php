<?php
/**
 * Class OAuthOptions
 *
 * @created      09.07.2017
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\OAuth;

use chillerlan\HTTP\HTTPOptionsTrait;
use chillerlan\Settings\SettingsContainerAbstract;

/**
 * This class holds all settings related to the OAuth provider as well as the default HTTP client.
 */
class OAuthOptions extends SettingsContainerAbstract{
	use OAuthOptionsTrait, HTTPOptionsTrait;
}
