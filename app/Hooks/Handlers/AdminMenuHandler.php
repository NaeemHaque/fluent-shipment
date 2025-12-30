<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\App\App;
use FluentShipment\App\Utils\Enqueuer\Enqueue;

class AdminMenuHandler
{
    protected $app;
    protected $slug;
    protected $baseUrl;
    protected $config;
    protected $position = 6;

    public function __construct()
    {
        $this->app     = App::make();
        $this->config  = $this->app->config;
        $this->slug    = $this->config->get('app.slug');
        $this->baseUrl = $this->app->applyFilters(
            'fluent_connector_base_url',
            admin_url('admin.php?page=' . $this->slug . '#/')
        );
    }

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

    public function render()
    {
        $this->enqueueAssets($this->slug);

        $this->app->view->render('admin.menu', [
            'slug' => $this->slug,
        ]);
    }

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

    protected function triggerAdminBootingHooks()
    {
        $this->app->doCustomAction('_admin_booting');
    }

    protected function enqueueAdminStyles($handle)
    {
        Enqueue::style($handle, 'scss/admin.scss');
    }

    protected function triggerAdminBootedHooks()
    {
        $this->app->doCustomAction('_admin_booted');
    }

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

    protected function localizeScript($slug)
    {
        $authUser = get_user_by('ID', get_current_user_id());

        $theme = null;
        if ($themes = $this->config->get('theme.themes')) {
            $theme = $themes[$this->config->get('theme.default')] ?? null;
        }

        wp_localize_script($slug . '_admin_app', 'fluentFrameworkAdmin', [
            'env'         => $this->app->env(),
            'slug'        => $slug,
            'theme'       => $theme,
            'user_locale' => get_locale(),
            'brand_logo'  => $this->getMenuIcon(),
            'nonce'       => wp_create_nonce($slug),
            'asset_url'   => $this->app['url.assets'],
            'rest'        => $r = $this->getRestInfo(),
            'endpoints'   => $this->getRestEndpoinds($r),
            'baseUrl'     => $this->baseUrl,
            'routes'      => $this->getAdminRoutes(),
            'name'        => $this->config->get('app.name'),
            'hook_prefix' => $this->config->get('app.hook_prefix'),
            'logoUrl'     => Enqueue::getStaticFilePath('images/logo.png'),
            'me'          => [
                'id'        => $authUser->ID ?? null,
                'email'     => $authUser->user_email ?? null,
                'full_name' => $authUser->display_name ?? null,
                'is_admin'  => current_user_can('administrator'),
            ],
        ]);
    }

    protected function getAdminRoutes()
    {
        return $this->getMenuItems($this->baseUrl);
    }

    protected function getMenuItems($baseUrl)
    {
        return $this->registerRoutesAndMenu();
    }

    protected function registerRoutesAndMenu()
    {
        $app = $this->app;

        $router = (function () {
            // @phpstan-ignore-next-line
            $ns = $this->app->__namespace__;

            $class = "$ns\App\Utils\Vue\Router";

            return new $class;
        })();

        require($this->app['path.http'] . '/Routes/vue.php');

        return $router;
    }

    protected function getRestInfo()
    {
        $ns  = $this->app->config->get('app.rest_namespace');
        $ver = $this->app->config->get('app.rest_version');

        return [
            'base_url'  => $this->getBaseRestUrl(),
            'url'       => $this->getFullRestUrl($ns, $ver),
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $ver
        ];
    }

    protected function getBaseRestUrl()
    {
        if (get_option('permalink_structure')) {
            return esc_url_raw(rest_url());
        }

        return esc_url_raw(
            rtrim(get_site_url(), '/') . "/?rest_route=/"
        );
    }

    protected function getFullRestUrl($ns, $ver)
    {
        if (get_option('permalink_structure')) {
            return esc_url_raw(rest_url($ns . '/' . $ver));
        }

        return esc_url_raw(
            rtrim(get_site_url(), '/') . "/?rest_route=/{$ns}/{$ver}"
        );
    }

    protected function getRestEndpoinds($r)
    {
        $url = $r['url'] . '/' . $r['namespace'] . '/__endpoints';

        $result = wp_remote_get($url, [
            'sslverify'  => false,
            'cookies'    => $_COOKIE,
            'user-agent' => "wpfluent.{$this->slug}.__endpoints",
            'headers'    => [
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

    public function __invoke()
    {
        $this->add();
    }
}

