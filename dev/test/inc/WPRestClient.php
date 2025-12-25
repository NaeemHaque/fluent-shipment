<?php

namespace Dev\Test\Inc;

use WP_REST_Request;
use WP_REST_Response;

class WPRestClient
{
    protected $version = 'v2';

    /**
     * Normalize and build the full REST route.
     *
     * @param string $route
     * @return string
     */
    protected function buildRoute($route)
    {
        $route = ltrim($route, '/');

        if (preg_match('#^wp/#', $route)) {
            return "/$route";
        }

        return "/wp/{$this->version}/$route";
    }

    /**
     * Make a REST request to the WordPress API.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $route REST route (e.g., posts, wp/v1/posts)
     * @param array $params Optional query or body parameters.
     * @return WP_REST_Response
     */
    public function request($method, $route, $params = [])
    {
        $request = new WP_REST_Request($method, $this->buildRoute($route));

        if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
            $request->set_query_params($params);
        } else {
            $request->set_body_params($params);
        }

        return new Response(rest_get_server()->dispatch($request));
    }

    public function get($route, $params = [])
    {
        return $this->request('GET', $route, $params);
    }

    public function post($route, $params = [])
    {
        return $this->request('POST', $route, $params);
    }

    public function put($route, $params = [])
    {
        return $this->request('PUT', $route, $params);
    }

    public function delete($route, $params = [])
    {
        return $this->request('DELETE', $route, $params);
    }
}
