<?php

namespace Dev\Test\Inc;

use WP_REST_Request;

trait Concerns
{
    use InteractsWithUserContext, DBAssertions, HttpMethods;

    /**
     * Dispatch a REST API request and return a wrapped response.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $uri REST endpoint URI
     * @param array $params Request parameters
     * @param array $headers Request headers
     * @return Response
     */
    public function dispatch($method, $uri, $params = [], $headers = [])
    {
        $response = rest_do_request(
            $this->createRequest($method, $uri, $params, $headers)
        );

        return new Response($response);
    }

    /**
     * Create a WP_REST_Request with parameters and headers set.
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $params Request parameters
     * @param array $headers Request headers
     * @return WP_REST_Request
     */
    protected function createRequest($method, $url, $params, $headers)
    {
        do_action('rest_api_init');

        $parsed = parse_url($url);

        $request = $this->makeRestRequest($method, $parsed['path']);

        $this->populateSuperglobals($method, $parsed, $params, $url)
            ->setRequestParams($request, $params)
            ->setRequestHeaders($request, $headers)
            ->processUploadedFiles($params);

        return $request;
    }

    /**
     * Instantiate a WP_REST_Request for a given method and path.
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @return WP_REST_Request
     */
    protected function makeRestRequest($method, $path)
    {
        return new WP_REST_Request(
            $method, $this->buildUrl($path)
        );
    }

    /**
     * Build full REST namespace URL path.
     *
     * @param string $path Endpoint path
     * @return string Full REST namespace path
     */
    protected function buildUrl($path)
    {
        return rtrim(
            $this->getRestNamespace(), '/') . '/' . ltrim($path, '/'
        );
    }

    /**
     * Populate PHP superglobals ($_GET, $_POST, $_REQUEST, $_FILES, $_SERVER).
     *
     * @param string $method HTTP method
     * @param array $parsedUrl Parsed URL components
     * @param array $params Request parameters
     * @param string $uri Request URI
     * @return $this
     */
    protected function populateSuperglobals($method, $parsedUrl, $params, $uri)
    {
        return $this->resetSuperglobals()
            ->populateQueryParams($parsedUrl)
            ->populateInputParams($method, $params)
            ->setupAllServerGlobals($method, $uri);
    }

    /**
     * Reset all relevant PHP superglobals to empty state.
     *
     * @return $this
     */
    protected function resetSuperglobals()
    {
        $_GET = $_POST = $_REQUEST = $_FILES = [];

        return $this;
    }

    /**
     * Parse and populate $_GET from URL query parameters.
     *
     * @param array $parsedUrl Parsed URL components
     * @return $this
     */
    protected function populateQueryParams($parsedUrl)
    {
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            $_GET = array_merge($_GET, $queryParams);
        }

        return $this;
    }

    /**
     * Populate $_GET or $_POST from request parameters, excluding files.
     *
     * @param string $method HTTP method
     * @param array $params Request parameters
     * @return $this
     */
    protected function populateInputParams($method, $params)
    {
        $inputParams = array_filter($params, function ($value) {
            return !$value instanceof UploadedFile;
        });

        if ($method === 'GET') {
            $_GET = array_merge($_GET, $inputParams);
        } else {
            $_POST = array_merge($_POST, $inputParams);
        }

        return $this;
    }

    /**
     * Set PHP $_SERVER variables needed for REST API context.
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return $this
     */
    protected function setupAllServerGlobals($method, $uri)
    {
        $_REQUEST = array_merge($_GET, $_POST);

        $config = $this->plugin->config->get('app');

        $formattedUri = '/wp-json/' .
            trim($config['rest_namespace'], '/') . '/' .
            trim($config['rest_version'], '/') . '/' .
            ltrim($uri, '/');

        $_SERVER['REQUEST_URI'] = $formattedUri;
        $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../../..');
        $_SERVER['SERVER_SOFTWARE'] = 'PHP Unit';
        $_SERVER['REQUEST_METHOD'] = $method;

        $host = parse_url(get_option('siteUrl'), PHP_URL_HOST) ?: 'localhost';
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SERVER_NAME'] = $host;

        return $this;
    }

    /**
     * Set parameters on the WP_REST_Request object.
     *
     * @param WP_REST_Request $request Request instance
     * @param array $params Parameters to set
     * @return $this
     */
    protected function setRequestParams($request, $params)
    {
        foreach ($params as $key => $value) {
            if (!$value instanceof UploadedFile) {
                $request->set_param($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set headers on the WP_REST_Request object.
     *
     * @param WP_REST_Request $request Request instance
     * @param array $headers Headers to set
     * @return $this
     */
    protected function setRequestHeaders($request, $headers = [])
    {
        $defaultHeaders = [
            'X-WP-Nonce' => wp_create_nonce('wp_rest'),
        ];

        $request->set_headers(
            array_merge($defaultHeaders, $headers)
        );

        return $this;
    }

    /**
     * Recursively add uploaded files to the $_FILES superglobal.
     *
     * @param array $params Request parameters
     * @param string $parentKey Key prefix for nested arrays
     * @return void
     */
    protected function processUploadedFiles($params, $parentKey = '')
    {
        foreach ($params as $key => $value) {
            $inputName = $parentKey !== '' ? $parentKey : $key;

            if (is_array($value)) {
                $this->processUploadedFiles($value, $inputName);
                continue;
            }

            if ($value instanceof UploadedFile) {
                $this->addFileToFilesArray($inputName, $value);
            }
        }
    }

    /**
     * Add a single uploaded file's data into the $_FILES array.
     *
     * @param string $inputName Name of the input field
     * @param UploadedFile $file Uploaded file instance
     * @return void
     */
    protected function addFileToFilesArray($inputName, $file)
    {
        $fileAttributes = ['name', 'type', 'tmp_name', 'error', 'size'];

        if (isset($_FILES[$inputName]['name'])) {
            foreach ($fileAttributes as $attribute) {
                if (!is_array($_FILES[$inputName][$attribute])) {
                    $_FILES[$inputName][$attribute] = [$_FILES[$inputName][$attribute]];
                }

                $_FILES[$inputName][$attribute][] = $file->toArray()[$attribute];
            }
        } else {
            $_FILES[$inputName] = $file->toArray();
        }
    }

    /**
     * Get the full REST namespace string including version.
     *
     * @return string REST namespace path
     */
    protected function getRestNamespace()
    {
        $ns = $this->plugin->config->get('app.rest_namespace');

        $ver = $this->plugin->config->get('app.rest_version');

        return '/' . $ns . '/' . $ver . '/';
    }

    /**
     * Create an UploadedFile instance from a file path.
     *
     * @param string $path Path to the file
     * @return UploadedFile
     */
    public function uploadableFile($path)
    {
        return new UploadedFile($path);
    }
}
