<?php

namespace Dev\Test\Inc;

use WP_User;
use Exception;
use InvalidArgumentException;

trait InteractsWithUserContext
{
    /**
	 * Create a new WordPress user for testing.
	 *
	 * @param array $userData Optional user data overrides, such as:
	 *                        - 'user_login'
	 *                        - 'user_pass'
	 *                        - 'user_email'
	 * @param array $options  Optional settings, e.g.:
	 *                        - 'role' => 'editor'
	 *                        - 'caps' => ['edit_posts' => true]
	 * 
	 * @return WP_User       The created WP_User object.
	 * 
	 * @throws Exception     If user creation fails.
	 */
    public function createWPUser($userData = [], $options = [])
    {
        $defaults = [
            'user_login' => 'testuser_' . uniqid(),
            'user_pass'  => 'password',
            'user_email' => 'testuser_' . uniqid() . '@example.com',
        ];

        $userData = array_merge($defaults, $userData);

        $userId = wp_create_user(
            $userData['user_login'],
            $userData['user_pass'],
            $userData['user_email']
        );

        if (is_wp_error($userId)) {
            throw new Exception(
                'Could not create user: ' . $userId->get_error_message()
            );
        }

        $user = new WP_User($userId);

        // Set role if specified
        if (!empty($options['role'])) {
            $user->set_role($options['role']);
        }

        // Add or remove custom capabilities if specified
        if (!empty($options['caps']) && is_array($options['caps'])) {
            $caps = $this->normalizeCaps($options['caps']);
            foreach ($caps as $cap => $value) {
                if ($value) {
                    $user->add_cap($cap);
                } else {
                    $user->remove_cap($cap);
                }
            }
        }

        return $user;
    }

    /**
	 * Normalize capabilities array to standard [capability => bool] format.
	 *
	 * @param array $caps Input capabilities list. Can be:
	 *                    - ['cap1', 'cap2'] (defaults to true)
	 *                    - ['cap1' => true, 'cap2' => false]
	 * 
	 * @return array      Normalized capabilities in [capability => bool] format.
	 */
    protected function normalizeCaps($caps)
    {
        $normalized = [];

        foreach ($caps as $key => $cap) {
            if (is_string($cap)) {
                $normalized[$cap] = true;
            } else {
                $normalized[$key] = (bool) $cap;
            }
        }

        return $normalized;
    }

    /**
	 * Set the current user context to a user with the specified role.
	 * Creates a new user if one doesn't exist.
	 *
	 * @param string $role WordPress user role slug
	 * (e.g. 'subscriber', 'administrator').
	 *
	 * @return $this
	 */
    public function actAs($role = 'subscriber')
    {
        $user = $this->createWPUser([], ['role' => $role]);
        $this->login($user);
        return $this;
    }

    /**
     * Convenience method to set current user as administrator.
     *
     * @return $this
     */
    public function actAsAdmin()
    {
        return $this->actAs('administrator');
    }

    /**
     * Set current user as guest (logged out).
     *
     * @return $this
     */
    public function actAsGuest()
    {
        return $this->logout();
    }

    /**
	 * Log in as the specified user or user ID.
	 *
	 * @param int|WP_User|object $id - User ID, WP_User instance, or a model
	 * representing a user in the wp_users table.
	 *
	 * @return $this
	 *
	 * @throws InvalidArgumentException If the user does not exist or invalid input.
	 */
    public function login($id)
    {
        $this->setUser($id);

        $currentUser = wp_get_current_user();
        if (!$currentUser || !$currentUser->ID) {
            throw new InvalidArgumentException('The specified user does not exist in the database.');
        }

        return $this;
    }

    /**
     * Log out the current user.
     *
     * @return $this
     */
    public function logout()
    {
        return $this->setUser(0);
    }

    /**
     * Set the current user context.
     *
     * Accepts WP_User object, user ID, or an object with getKey() method.
     *
     * @param int|WP_User|object $id User identifier or object.
     * @return $this
     * @throws InvalidArgumentException For invalid input types.
     */
    public function setUser($id)
    {
        $exception = new InvalidArgumentException(
            'The argument must be a valid WP_User object, an integer user ID, or a user model synced with the WordPress users table.'
        );

        if (is_object($id)) {
            if ($id instanceof WP_User) {
                wp_set_current_user($id->ID);
                return $this;
            }

            if (method_exists($id, 'getKey')) {
                $id = (int) $id->getKey();
            } else {
                throw $exception;
            }
        }

        if (is_int($id)) {
            wp_set_current_user($id);
            return $this;
        }

        throw $exception;
    }

    public function actLike($roleOrUserData = [], $capabilities = [])
    {
        global $current_user;

        if (is_string($roleOrUserData)) {
            $user = InMemoryWPUser::fromRole($roleOrUserData, $capabilities);
        } else {
            $userData = $roleOrUserData;
            $caps = $capabilities ?: ['read'];
            $user = new InMemoryWPUser(
                $userData, $caps, $userData['roles'] ?? ['subscriber']
            );
        }

        $current_user = $user;

        add_filter('determine_current_user', fn() => $user->ID);
    }

    public function actLikeAdmin()
    {
        $this->actLike('administrator');
    }
}
