<?php

namespace Fast\Eloquent;

use Hash;
use Firebase\JWT\JWT;
use Fast\Eloquent\Model;
use ReflectionException;
use Fast\Auth\AuthenticationException;
use Fast\Http\Exceptions\AppException;
use React\Cache\ArrayCache;

abstract class Authenticate extends Model {
	/**
	 * Set password before saving
	 *
	 * @param string $password
	 *
	 * @return string
	 */
	public function setPasswordAttribute(string $password): string {
		return Hash::make($password);
	}

	/**
	 * Create token for this user bound
	 *
	 * @param array $customClaims
	 * @return array
	 *
	 * @throws AuthenticationException
	 * @throws AppException
	 * @throws ReflectionException
	 */
	public function createToken(array $customClaims = []): array {
		$key = config('jwt.secret');
		$hash = config('jwt.hash');

		if (empty($key)) {
			throw new AuthenticationException('Please install the JWT authentication');
		}

		if (empty($hash)) {
			throw new AuthenticationException('Please set hash type in config/jwt.php');
		}

		$modelId = $this->primaryKey();

		if (is_null($this->{$modelId})) {
			throw new AuthenticationException('Cannot generate tokens for the class that are not yet bound');
		}

		$jwt = app()->make(JWT::class);

		$minutes = $customClaims['exp'] ?? config('jwt.exp');

		$exp = strtotime('+ ' . $minutes . ' minutes');

		$payload = [
			'object' => $this->getData(),
			'exp' => $exp,
		];

		$token = $jwt->encode($payload, $this->trueFormatKey($key), $hash);

		$this->token = $token;

		return [
			'token' => $token,
			'exp' => $exp,
			'type' => 'Bearer',
		];
	}

	/**
	 * Make true format for jwt key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function trueFormatKey(string $key): string {
		return base64_decode(strtr($key, '-_', '+/'));
	}
}
