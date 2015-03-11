<?php namespace Sofa\Revisionable\Adapters;

use Sofa\Revisionable\UserProvider;
use Tymon\JWTAuth\JWTAuth as BaseJWTAuth;

class JWTAuth implements UserProvider {

	/**
	 * Auth provider instance.
	 *
	 * @var \Tymon\JWTAuth\JWTAuth
	 */
	protected $provider;

	/**
	 * Field from the user to be saved as author of the action.
	 *
	 * @var string
	 */
	protected $field;

	/**
	 * Create adapter instance for JWTAuth.
	 *
	 * @param \Tymon\JWTAuth\JWTAuth
	 * @param string $field
	 */
	public function __construct(BaseJWTAuth $provider, $field = null)
	{
		$this->provider = $provider;
		$this->field    = $field;
	}
	/**
	 * Get identifier of the currently logged in user.
	 *
	 * @return string|null
	 */
	public function getUser()
	{
		return $this->getUserFieldValue();
	}
	/**
	 * Get value from the user to be saved as the author.
	 *
	 * @return string|null
	 */
	protected function getUserFieldValue()
	{
		if ($user = $this->provider->parseToken()->toUser()) {
			return ($field = $this->field) ? (string) $user->{$field} : $user->getAuthIdentifier();
		}
	}
}