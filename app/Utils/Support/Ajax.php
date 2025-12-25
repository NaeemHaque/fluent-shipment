<?php

namespace FluentShipment\App\Utils\Support;

use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use FluentShipment\App\App;

/**
 * Register AJAX handlers using HTTP-like method names.
 *
 * $action should start with config.app.hook_prefix,
 * e.g. `wpfluent_my_action`.
 * 
 * The $handler can be a callable or "Class@method" string.
 * These dynamic methods delegate to the register() method.
 * 
 * @method get(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method post(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method put(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method patch(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method delete(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 *
 * @method static get(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method static post(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method static put(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method static patch(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 * @method static delete(string $action, callable|string $handler, int|string|array $priority = 10, string $scope = "both")
 *
 * Usage Examples:
 *
 * Ajax::post('action', 'MyController@handle'); // priority 10, admin & public
 * Ajax::post('action', 'MyController@handle', 5); // priority 5, admin & public
 * Ajax::post('action', 'MyController@handle', 'admin'); // priority 10, admin
 * Ajax::post('action', 'MyController@handle', 5, 'public'); // priority 5, public
 *
 * Ajax::post('action', 'MyController@handle', [
 *  'priority' => 5,
 *  'scope' => 'public'
 * ]);
 */

class Ajax
{
	/**
	 * @var \FluentShipment\Framework\Foundation\Application
	 */
	protected $app = null;
	
	/**
	 * $passthru allowed methods
	 * @var array
	 */
	protected $passthru = [
		'get',
		'post',
		'put',
		'patch',
		'delete',
	];

	/**
	 * Consruct the Instance.
	 * 
	 * @param \FluentShipment\Framework\Foundation\Application $app
	 */
	public function __construct($app = null)
	{
		$this->app = $app ?? App::getInstance();
	}

	/**
	 * Alternative constructor.
	 * 
	 * @return $this
	 */
	public static function getInstance()
	{
		return new static;
	}

	/**
	 * Register the ajax hook using appropriate method.
	 * 
	 * @param  string  			$method
	 * @param  string  			$action
	 * @param  callable|string  $handler
	 * @param  integer 			$priority
	 * @param  string  			$scope
	 * @return void
	 * @throws BadMethodCallException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function register(
	    $method,
	    $action,
	    $handler,
	    $priority = 10,
	    $scope = 'both'
	) {
	    if (!in_array($method, $this->passthru)) {
	        throw new BadMethodCallException(
	            "Ajax::{$method}() is not supported."
	        );
	    }

	    // If 3rd argument is an array
	    if (is_array($priority)) {
	        $scope = $priority['scope'] ?? 'both';
	        $priority = $priority['priority'] ?? 10;
	    }
	    // If scope is passed as the 3rd argument
	    elseif (is_string($priority) && !is_numeric($priority)) {
	        [$scope, $priority] = [$priority, $scope];
	    }

	    if (!in_array($scope, ['admin', 'public', 'both'], true)) {
	        throw new InvalidArgumentException(
	            "Invalid scope '{$scope}' provided."
	        );
	    }

	    $callback = function () use ($method, $handler) {
	        try {
	            wp_send_json_success(
	                $this->handle($method, $handler)
	            );
	        } catch (Exception $e) {
	            wp_send_json_error($e->getMessage());
	        }
	    };

	    if ($scope === 'both') {
	    	$registrationMethod = 'addAjaxActions';
	    } elseif ($scope === 'admin') {
	    	$registrationMethod = 'addAdminAjaxAction';
	    } elseif ($scope === 'public') {
	    	$registrationMethod = 'addPublicAjaxAction';
	    }

	    $this->$registrationMethod($action, $callback, $priority);
	}

	/**
     * Add ajax action
     * 
     * @param string $action
     * @param string|\Closure $handler
     * @param int $priority
     * @param string $scope
     * @return void
     */
    private function addAjaxAction($action, $handler, $priority, $scope)
    {
        $hook = $scope == 'admin' ? 'wp_ajax_' : 'wp_ajax_nopriv_';

        $action = $hook.$this->app->config->get('app.hook_prefix').$action;

        $callback = $this->app->parseHookHandler($handler);

        add_action($action, $callback, $priority);
    }

    /**
     * Add ajax actions including non_prive
     * @param string $action
     * @param string|\Closure $handler
     * @param int $priority
     * @return void
     */
    public function addAjaxActions($action, $handler, $priority = 10)
    {
        $this->addAjaxAction($action, $handler, $priority, 'admin');
        $this->addAjaxAction($action, $handler, $priority, 'public');
    }

    /**
     * Add ajax action for privilaged user
     * @param string $action
     * @param string|\Closure $handler
     * @param int $priority
     * @return void
     */
    public function addAdminAjaxAction($action, $handler, $priority = 10)
    {
        $this->addAjaxAction($action, $handler, $priority, 'admin');
    }

    /**
     * Add ajax action for non-privilaged user
     * @param string $action
     * @param string|\Closure $handler
     * @param int $priority
     * @return void
     */
    public function addPublicAjaxAction($action, $handler, $priority = 10)
    {
        $this->addAjaxAction($action, $handler, $priority, 'public');
    }

    /**
     * Handle ajax request.
     * 
     * @param  string $method
     * @param  mixed $handler
     * @return mixed
     */
	public function handle($method, $handler)
	{
		if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
			throw new BadMethodCallException("Invalid request method.");
		}

		check_ajax_referer($this->app->config->get('hook_prefix'));

		return $this->app->call($this->app->parseHookHandler($handler));
	}

	/**
	 * Dynamically call a method.
	 * 
	 * @param  string $method
	 * @param  mixed $args
	 * @return void
	 */
	public function __call($method, $args)
	{
		$this->register($method, ...$args);
	}

	/**
	 * Dynamically call a method.
	 * 
	 * @param  string $method
	 * @param  mixed $args
	 * @return void
	 */
	public static function __callStatic($method, $args)
	{
		return (new static)->$method(...$args);
	}
}
