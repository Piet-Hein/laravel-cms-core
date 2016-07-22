<?php
namespace Czim\CmsCore\Contracts\Auth;

interface UserAuthenticationInterface
{

    /**
     * Returns whether any user is currently logged in.
     *
     * @return bool
     */
    public function check();

    /**
     * Returns currently logged in user, if any.
     *
     * @return UserInterface|false
     */
    public function user();

    /**
     * Returns whether currently logged in user, if any, is (super) admin.
     *
     * @return bool
     */
    public function admin();

    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password);

    /**
     * @return bool
     */
    public function logout();

    /**
     * Returns whether the current user has the given role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role);

    /**
     * Returns whether the current user has the given permission(s).
     *
     * @param string|string[] $permission
     * @param bool            $allowAny     if true, allows if any is permitted
     * @return bool
     */
    public function can($permission, $allowAny = false);

    /**
     * Returns whether the current user has any of the given permissions.
     *
     * @param string[] $permissions
     * @return bool
     */
    public function canAnyOf(array $permissions);

}
