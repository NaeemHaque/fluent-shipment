<?php

namespace FluentShipment\App\Utils\Vue;

use JsonSerializable;

class Router implements JsonSerializable
{
    use CommonTrait, Enqueueable;

    /**
     * Route groups.
     *
     * @var array
     */
    public $groups = [];

    /**
     * Top-level routes.
     *
     * @var array
     */
    public $routes = [];

    /**
     * Add a new route.
     *
     * @param mixed ...$args
     * @return Route
     */
    public function add(...$args)
    {
        $route = new Route(...$args);
        
        $target = isset(
            $this->groups['primary']
        ) ? $this->groups['primary'] : $this->routes;

        $target = $this->removeRouteByPath($target, $route->get('path'));

        $target[] = $route;

        if (isset($this->groups['primary'])) {
            $this->groups['primary'] = $target;
        } else {
            $this->routes = $target;
        }

        return $route;
    }

    /**
     * Add a group of routes.
     *
     * @param string   $name
     * @param callable $callback
     * @return self
     */
    public function menu($name, $callback)
    {
        if (!isset($this->groups[$name])) {
            $this->groups[$name] = $name === 'primary' ? $this->routes : [];
        }

        $group = new Group($name, $this);

        $group->setItems($this->groups[$name]);

        $callback($group);

        $this->groups[$name] = $group->getItems();

        if ($name === 'primary') {
            $this->routes = [];
        }

        return $this;
    }

    /**
     * Get all routes.
     *
     * @return array
     */
    public function getRoutes()
    {
        $primary = ['primary' => $this->routes];

        return array_merge($primary, $this->groups);
    }

    /**
     * Get a route group.
     * 
     * @param  string $group
     * @return array
     */
    public function __get($group)
    {
        if (isset($this->groups[$group])) {
            return $this->newAdder($this->groups[$group]);
        }
    }

    /**
     * Create a new Adder.
     * 
     * @param  array &$group
     * @return Adder
     */
    protected function newAdder(&$group)
    {
        return new Adder($group);
    }

    /**
     * Serialize to JSON.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getRoutes();
    }
}
