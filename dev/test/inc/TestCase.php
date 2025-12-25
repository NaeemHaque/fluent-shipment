<?php

namespace Dev\Test\Inc;

use Exception;
use ErrorException;

class TestCase extends \WP_UnitTestCase
{
	use Concerns;

	protected $ns = null;

	protected $plugin = null;
	
	protected $factory = null;

	public function setUp() : void
	{
		parent::setUp();

        $this->bootstrap();

        $this->clearBootedModels();
	}

	protected function bootstrap()
	{	
		$this->plugin = $this->createApplication(__DIR__ . '/../../../');

        $this->refreshDatabaseAndResetUser();
        
        $this->factory = new Factory;

        $config = require(__DIR__.'/../config.php');

        update_option('siteurl', $config['site_url']);

        add_filter('pre_option_home', fn() => $config['site_url']);
		add_filter('pre_option_siteurl', fn() => $config['site_url']);
	}

	protected function createApplication($pluginDir)
	{
		$this->ns = json_decode(
			file_get_contents($pluginDir . '/composer.json'),
		)->extra->wpfluent->namespace->current;
		
		$application = $this->ns . '\Framework\Foundation\Application';

		return new $application(realpath($pluginDir . '/plugin.php'));
	}

	protected function refreshDatabaseAndResetUser()
	{
		if (method_exists($this, 'refreshDatabase')) {
			$this->refreshDatabase('migrateUp');
		}

		$this->setUser(0);
		remove_all_filters('determine_current_user');
	}

	protected function clearBootedModels()
	{
		$model = $this->ns.'\Framework\Database\Orm\Model';

        $model::clearBootedModels();
	}

	public function tearDown() : void
	{
		global $wp_rest_server;

        $wp_rest_server = null;

		if ($this->plugin) {
	        $this->clearAllPluginHooks();
	    } else {
	    	throw new Exception(
	    		'You didn\'t call parent::setUp in your ' .
	    		get_class($this) . '::setUp method.'
	    	);
	    }

	    $this->setUser(0);
		remove_all_filters('determine_current_user');
		
		parent::tearDown();
	}

	public function clearAllPluginHooks()
	{
		$prefix = $this->plugin->config->get('app.hook_prefix');

	    foreach (array_keys($GLOBALS['wp_filter']) as $hook) {
	        if (strpos($hook, $prefix) === 0) {
	            remove_all_actions($hook);
	            remove_all_filters($hook);
	        }
	    }
	}

	public function wp()
	{
		return new WPRestClient;
	}

	public function __get($key)
	{
		if ($this->plugin->bound($key)) {
			return $this->plugin->{$key};
		}

		throw new ErrorException(
			'Undefined property: ' . get_class(new self) . '::' . $key
		);
	}
}
