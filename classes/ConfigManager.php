<?php declare(strict_types = 1);

namespace Modules\AIIntegration\Classes;

use CProfile;
use CWebUser;

class ConfigManager {
	private const PROFILE_KEY_GLOBAL = 'modules.aiintegration.global';
	private const PROFILE_KEY_USER = 'modules.aiintegration.user';

	/**
	 * Load configuration merged from global and user settings.
	 */
	public static function load(): array {
		$global_config = self::getGlobalConfig();
		$user_config = self::getUserConfig();

		$config = $global_config;

		// Merge user-specific provider settings
		if (isset($user_config['providers'])) {
			foreach ($user_config['providers'] as $name => $u_provider) {
				if (isset($config['providers'][$name])) {
					// User can override api_key and preference, but 'enabled' status is global or merged
					if (!empty($u_provider['api_key'])) {
						$config['providers'][$name]['api_key'] = $u_provider['api_key'];
					}
					// If user has a specific model or other pref, we could merge it here too
					if (isset($u_provider['model']) && $u_provider['model'] !== '') {
						$config['providers'][$name]['model'] = $u_provider['model'];
					}
				}
			}
		}

		if (isset($user_config['default_provider'])) {
			$config['default_provider'] = $user_config['default_provider'];
		}

		return $config;
	}

	/**
	 * Get only global configuration.
	 */
	public static function getGlobalConfig(): array {
		$data = CProfile::get(self::PROFILE_KEY_GLOBAL);
		if ($data === null || $data === '') {
			return self::getDefaults();
		}

		$config = json_decode($data, true);
		return is_array($config) ? array_merge(self::getDefaults(), $config) : self::getDefaults();
	}

	/**
	 * Get only current user's configuration.
	 */
	public static function getUserConfig(): array {
		$data = CProfile::get(self::PROFILE_KEY_USER);
		if ($data === null || $data === '') {
			return ['providers' => []];
		}

		$config = json_decode($data, true);
		return is_array($config) ? $config : ['providers' => []];
	}

	/**
	 * Save global configuration.
	 */
	public static function saveGlobal(array $config): bool {
		if (CWebUser::getType() < USER_TYPE_SUPER_ADMIN) {
			return false;
		}

		$json = json_encode($config);
		CProfile::update(self::PROFILE_KEY_GLOBAL, $json, PROFILE_TYPE_STR);
		return true;
	}

	/**
	 * Save current user's configuration.
	 */
	public static function saveUser(array $config): bool {
		$json = json_encode($config);
		CProfile::update(self::PROFILE_KEY_USER, $json, PROFILE_TYPE_STR);
		return true;
	}

	private static function getDefaults(): array {
		return [
			'providers' => [
				'openai' => [
					'enabled' => false,
					'endpoint' => 'https://api.openai.com/v1/chat/completions',
					'model' => 'gpt-4o-mini',
					'temperature' => 0.7,
					'max_tokens' => 2048,
					'api_key' => ''
				],
				'anthropic' => [
					'enabled' => false,
					'endpoint' => 'https://api.anthropic.com/v1/messages',
					'model' => 'claude-3-haiku-20240307',
					'temperature' => 0.7,
					'max_tokens' => 2048,
					'api_key' => ''
				],
				'gemini' => [
					'enabled' => false,
					'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
					'model' => 'gemini-pro',
					'temperature' => 0.7,
					'max_tokens' => 2048,
					'api_key' => ''
				],
				'custom' => [
					'enabled' => false,
					'endpoint' => '',
					'model' => '',
					'temperature' => 0.7,
					'max_tokens' => 2048,
					'headers' => '{}',
					'api_key' => ''
				]
			],
			'default_provider' => 'openai',
			'quick_actions' => [
				'problems' => true,
				'triggers' => true,
				'items' => true,
				'hosts' => true
			]
		];
	}
}
