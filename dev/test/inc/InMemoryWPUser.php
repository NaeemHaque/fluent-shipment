<?php

namespace Dev\Test\Inc;

use WP_User;
use BadMethodCallException;

class InMemoryWPUser extends WP_User
{
    protected $meta = [];

    public function __construct(
        $data = [], $capabilities = [], $roles = ['subscriber']
    )
    {
        $this->ID = $data['ID'] ?? -1;

        $this->data = (object) array_merge([
            'ID' => $this->ID,
            'user_login' => 'fake_user',
            'user_email' => 'fake@example.com',
            'user_nicename' => 'Fake User',
        ], $data);

        $this->caps = ['read' => true];
        $this->roles = $roles;
        $this->allcaps = array_merge(['read' => true], array_fill_keys($capabilities, true));
    }

    public static function fromRole($role, $additionalCaps = [])
    {
        $defaultCaps = [
            'subscriber' => ['read'],
            'editor' => ['read', 'edit_posts', 'edit_pages', 'delete_posts'],
            'author' => ['read', 'edit_posts'],
            'contributor' => ['read'],
            'administrator' => [
                'read',
                'edit_posts', 'edit_pages', 'delete_posts',
                'manage_options', 'delete_users', 'install_plugins',
                'activate_plugins', 'update_plugins',
            ],
        ];

        $caps = array_merge($defaultCaps[$role] ?? ['read'], $additionalCaps);

        $data = [
            'ID' => -1 * rand(10, 9999),
            'user_login' => $role,
            'user_email' => $role . '@example.com',
            'user_nicename' => ucfirst($role),
            'roles' => [$role],
        ];

        return new self($data, $caps, $data['roles']);
    }

    public function exists()
    {
        return true;
    }

    public function get($key, $single = true)
    {
        return $this->data->$key ?? null;
    }

    public function get_meta($key, $single = true)
    {
        if (!isset($this->meta[$key])) {
            return null;
        }

        return $single ? $this->meta[$key][0] : $this->meta[$key];
    }

    public function update_meta($key, $value)
    {
        $this->meta[$key] = [$value];
        return true;
    }

    public function delete_meta($key)
    {
        if (isset($this->meta[$key])) {
            unset($this->meta[$key]);
            return true;
        }

        return false;
    }

    public function add_cap($cap, $grant = true)
    {
        $this->allcaps[$cap] = $grant;

        if ($grant && !in_array($cap, $this->caps)) {
            $this->caps[$cap] = true;
        } elseif (!$grant && isset($this->caps[$cap])) {
            unset($this->caps[$cap]);
        }

        return true;
    }

    public function remove_cap($cap)
    {
        return $this->add_cap($cap, false);
    }

    public function has_cap($cap, ...$args)
    {
        if (in_array($cap, $this->roles, true)) {
            return true;
        }

        if (in_array($cap, array_map('strtolower', $this->roles), true)) {
            return true;
        }

        $caps = map_meta_cap($cap, $this->ID, ...$args);

        if (is_multisite() && is_super_admin($this->ID)) {
            return !in_array('do_not_allow', $caps, true);
        }

        $args = array_merge([$cap, $this->ID], $args);

        $capabilities = apply_filters(
            'user_has_cap', $this->allcaps, $caps, $args, $this
        );

        $capabilities['exist'] = true;
        
        unset($capabilities['do_not_allow']);

        foreach ($caps as $cap_check) {
            if (empty($capabilities[$cap_check])) {
                return false;
            }
        }

        return true;
    }

    public function get_role_caps()
    {
        $role_caps = [];

        foreach ($this->roles as $role) {
            switch ($role) {
                case 'administrator':
                    $role_caps = array_merge($role_caps, [
                        'read' => true,
                        'edit_posts' => true,
                        'edit_pages' => true,
                        'delete_posts' => true,
                        'manage_options' => true,
                        'delete_users' => true,
                        'install_plugins' => true,
                        'activate_plugins' => true,
                        'update_plugins' => true,
                    ]);
                    break;
                case 'editor':
                    $role_caps = array_merge($role_caps, [
                        'read' => true,
                        'edit_posts' => true,
                        'edit_pages' => true,
                        'delete_posts' => true,
                    ]);
                    break;
                case 'author':
                    $role_caps = array_merge($role_caps, [
                        'read' => true,
                        'edit_posts' => true,
                    ]);
                    break;
                case 'contributor':
                    $role_caps = array_merge($role_caps, [
                        'read' => true,
                    ]);
                    break;
                case 'subscriber':
                default:
                    $role_caps = array_merge($role_caps, [
                        'read' => true,
                    ]);
                    break;
            }
        }

        return $role_caps;
    }

    public function get_roles()
    {
        return $this->roles;
    }

    public function __call($method, $args)
    {
        if (method_exists( $this, $method)) {
            return $this->$method(...$args);
        }

        $snake = strtolower(
            preg_replace('/([a-z])([A-Z])/', '$1_$2', $method)
        );

        if (method_exists($this, $snake)) {
            return $this->$snake(...$args);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
