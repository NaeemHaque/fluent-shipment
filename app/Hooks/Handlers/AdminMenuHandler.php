<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\App\App;
use FluentShipment\App\Utils\Enqueuer\Enqueue;

class AdminMenuHandler
{
    /**
     * $app Application instance
     * @var \FluentShipment\Framework\Foundation\Application
     */
    protected $app;

    /**
     * $slug Plugin slug defined in config/app
     * @var string
     */
    protected $slug;

    /**
     * $baseUrl Plugin base url
     * @var string
     */
    protected $baseUrl;
    
    /**
     * $app Config instance
     * @var \FluentShipment\Framework\Foundation\Config
     */
    protected $config;

    /**
     * $position Menu Position
     * @var int|float
     */
    protected $position = 6;

    /**
     * Construct the instance
     */
    public function __construct()
    {
        $this->app = App::make();
        $this->config = $this->app->config;
        $this->slug = $this->config->get('app.slug');
        $this->baseUrl = $this->app->applyFilters(
            'fluent_connector_base_url',
            admin_url('admin.php?page=' . $this->slug . '#/')
        );
    }

    /**
     * Add Custom Menu
     * 
     * @return null
     */
    public function add()
    {
        add_menu_page(
            __('Fluent Shipment', 'fluentshipment'),
            __('Fluent Shipment', 'fluentshipment'),
            'manage_options',
            $this->slug,
            [$this, 'render'],
            $this->getMenuIcon(),
            $this->position
        );
    }

    /**
     * Render the menu page
     * 
     * @return null
     */
    public function render()
    {   
        $this->enqueueAssets($this->slug);

        // @phpstan-ignore-next-line
        $this->app->view->render('admin.menu', [
            'slug' => $this->slug,
        ]);
    }

    /**
     * Enqueue all the scripts and styles
     * 
     * @param  string $slug
     * 
     * @return null
     */
    public function enqueueAssets($slug)
    {
        $handle = $slug . '_admin_app';

        $this->triggerAdminBootingHooks();

        $this->enqueueAdminStyles($handle);

        $this->enqueueAdminScripts($handle);

        $this->triggerAdminBootedHooks();

        $this->enqueueStartScript($handle);

        $this->localizeScript($slug);
    }

    /**
     * Trigger actions before enqueuing admin assets.
     * 
     * @return null
     */
    protected function triggerAdminBootingHooks()
    {
        $this->app->doCustomAction('_admin_booting');
    }

    /**
     * Enqueue admin CSS styles.
     *
     * @param string $handle Script/style handle.
     * 
     * @return null
     */
    protected function enqueueAdminStyles($handle)
    {
        Enqueue::style($handle, 'scss/admin.scss');
    }

    /**
     * Trigger actions after enqueuing main admin app assets.
     *
     * @return null
     */
    protected function triggerAdminBootedHooks()
    {
        $this->app->doCustomAction('_admin_booted');
    }

    /**
     * Enqueue the main admin JavaScript application.
     *
     * @param string $handle Script handle.
     */
    protected function enqueueAdminScripts($handle)
    {
        Enqueue::script(
            $handle,
            'admin/app.js',
            [],
            '1.0',
            true
        );
    }

    /**
     * Enqueue the admin startup script that boots the app.
     *
     * @param string $handle Script handle prefix.
     *
     * @return null
     */
    protected function enqueueStartScript($handle)
    {
        Enqueue::script(
            $handle . '_start',
            'admin/start.js',
            ['wp-api-fetch', $handle],
            '1.0',
            true
        );
    }

    /**
     * Push/Localize the JavaScript variables
     * 
     * to the browser using wp_localize_script.
     * 
     * @param  string $slug
     * 
     * @return null
     */
    protected function localizeScript($slug)
    {
        $authUser = get_user_by('ID', get_current_user_id());

        $theme = null;
        if ($themes = $this->config->get('theme.themes')) {
            $theme = $themes[$this->config->get('theme.default')] ?? null;
        }

        wp_localize_script($slug . '_admin_app', 'fluentFrameworkAdmin', [
            'env'           => $this->app->env(),
            'slug'          => $slug,
            'theme'         => $theme,
            'user_locale'   => get_locale(),
            'brand_logo'    => $this->getMenuIcon(),
            'nonce'         => wp_create_nonce($slug),
            'asset_url'     => $this->app['url.assets'],
            'rest'          => $r = $this->getRestInfo(),
            'endpoints'     => $this->getRestEndpoinds($r),
            'baseUrl'       => $this->baseUrl,
            'routes'        => $this->getAdminRoutes(),
            'name'          => $this->config->get('app.name'),
            'hook_prefix'   => $this->config->get('app.hook_prefix'),
            'logoUrl'       => Enqueue::getStaticFilePath('images/logo.png'),
            'me'            => [
                'id'        => $authUser->ID ?? null,
                'email'     => $authUser->user_email ?? null,
                'full_name' => $authUser->display_name ?? null,
                'is_admin'  => current_user_can('administrator'),
            ],
        ]);
    }

    /**
     * Get and map menu items for main nav.
     * 
     * @return array
     */
    protected function getAdminRoutes()
    {
        return $this->getMenuItems($this->baseUrl);
    }

    /**
     * Get and map menu items for main nav.
     * 
     * @param  string $baseUrl
     * 
     * @return array|\FluentShipment\App\Utils\Vue\Router
     */
    protected function getMenuItems($baseUrl)
    {
        return $this->registerRoutesAndMenu();

        // $routes = [
        //     [
        //         'key'       => 'dashboard',
        //         'label'     => __('Dashboard', 'fluentvueicon'),
        //         'permalink' => $baseUrl,
        //         'path'      => '/',
        //         'component' => 'modules/dashboard'
        //     ],
        //     [
        //         'key'       => 'posts',
        //         'label'     => __('Posts', 'fluentvueicon'),
        //         'permalink' => $baseUrl . 'posts',
        //         'path'      => '/posts',
        //         'component' => 'modules/posts',
        //         'children'  => [
        //             [
        //                 'path' => ':id/view',
        //                 'name' => 'posts.view',
        //                 'component' => 'modules/posts/components/View',
        //                 'props' => true
        //             ]
        //         ]
        //     ],
        // ];

        // return $this->app->applyCustomFilters(
        //     'admin_menu_items', $routes
        // );
    }

    /**
     * Register frontend routes and menu items.
     * @return \FluentShipment\App\Utils\Vue\Router
     */
    protected function registerRoutesAndMenu()
    {
        $app = $this->app;
        
        $router = (function() {
            // @phpstan-ignore-next-line
            $ns = $this->app->__namespace__;
            
            $class = "$ns\App\Utils\Vue\Router";

            return new $class;
        })();

        require($this->app['path.http'] . '/Routes/vue.php');

        return $router;
    }

    /**
     * Gether rest info/settings for http client.
     * 
     * @return array
     */
    protected function getRestInfo()
    {
        $ns = $this->app->config->get('app.rest_namespace');
        $ver = $this->app->config->get('app.rest_version');

        return [
            'base_url'  => $this->getBaseRestUrl(),
            'url'       => $this->getFullRestUrl($ns, $ver),
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $ver
        ];
    }

    /**
     * Get base rest url by examining the permalink.
     * 
     * @see https://wordpress.stackexchange.com/questions/273144/can-i-use-rest-api-on-plain-permalink-format
     * 
     * @return string
     */
    protected function getBaseRestUrl()
    {
        if (get_option('permalink_structure')) {
            return esc_url_raw(rest_url());
        }

        return esc_url_raw(
            rtrim(get_site_url(), '/') . "/?rest_route=/"
        );
    }

    /**
     * Get the full rest url by examining the permalink
     * (full means, including the namespace/version).
     * 
     * @param $ns Rest Namespace
     * @param $ver Rest Version
     * @see https://wordpress.stackexchange.com/questions/273144/can-i-use-rest-api-on-plain-permalink-format
     * 
     * @return string
     */
    protected function getFullRestUrl($ns, $ver)
    {
        if (get_option('permalink_structure')) {
            return esc_url_raw(rest_url($ns . '/' . $ver));
        }

        return esc_url_raw(
            rtrim(get_site_url(), '/') . "/?rest_route=/{$ns}/{$ver}"
        );
    }

    /**
     * Retrieve rest endpoints for client.
     * 
     * @param  array $r Rest Info
     * 
     * @return array
     */
    protected function getRestEndpoinds($r)
    {
        $url = $r['url'] . '/' . $r['namespace'] . '/__endpoints';

        $result = wp_remote_get($url, [
            'sslverify'   => false,
            'cookies'     => $_COOKIE,
            'user-agent'  => "wpfluent.{$this->slug}.__endpoints",
            'headers'     => [
                'X-Wp-Nonce' => $r['nonce']
            ],
        ]);

        $code = wp_remote_retrieve_response_code($result);
        $body = wp_remote_retrieve_body($result);

        if (is_wp_error($result)) {
            error_log('HTTP request failed: ' . $result->get_error_message());
            return [];
        }

        if ($code === 200) {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error: ' . json_last_error_msg());
                error_log('Response body: ' . substr($body, 0, 500));
                return [];
            }
            return $data;
        }

        // Handle non-200 responses
        error_log("HTTP {$code} from {$url}");
        error_log('Response body : ' . $body);
        return [];
    }

    /**
     * Get the default icon for custom menu
     * added by the add_menu in the WP menubar.
     * 
     * @return string
     */
    protected function getMenuIcon()
    {
        if (str_starts_with($this->app->env(), 'dev')) {
            $path = 'resources/images/favicon.png';
        } else {
            $path = 'assets/images/favicon.png';
        }

        $file = plugin_dir_path($this->app->__pluginfile__) . $path;
        
        if (file_exists($file)) {
            return plugin_dir_url($this->app->__pluginfile__) . $path;
        }

        return 'dashicons-wordpress-alt';

    }

    /**
     * Makes the class invokable.
     * 
     * @return null
     */
    public function __invoke()
    {
        $this->add();
    }
}

