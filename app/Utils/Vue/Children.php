<?php

namespace FluentShipment\App\Utils\Vue;

use JsonSerializable;
use InvalidArgumentException;

class Children implements JsonSerializable
{
    use CommonTrait, Enqueueable;
    
	/**
	 * Child routes.
	 * 
	 * @var array
	 */
    protected array $routes = [];

    /**
     * Add a new child route.
     * 
     * @param mixed ...$items Route constructor arguments.
     * @return Route
     */
    public function add(...$items)
    {
        if (count($items) === 1 && is_array($items[0])) {
            $items = $items[0];
        }

        return $this->addMany($items);
    }

    /**
     * Add new menu item(s).
     * 
     * @param array $routes
     * @return self
     */
    public function addMany($routes)
    {
        if (is_string($routes[0])) {
            // Single route [path, component, ...]
            return $this->addOrReplaceRoute($routes);
        }

        foreach ($routes as $route) {
            $this->addOrReplaceRoute($route);
        }

        return $this;
    }

    /**
     * Add or replace a route with the same path (or name).
     *
     * @param array|Route $routeOrArgs
     * @return Route
     */
    protected function addOrReplaceRoute($routeOrArgs)
    {
        if ($routeOrArgs instanceof Route) {
            $newRoute = $routeOrArgs;
        } elseif (is_array($routeOrArgs)) {
            $newRoute = new Route(...$routeOrArgs);
        } else {
            throw new InvalidArgumentException("Invalid route argument");
        }

        $this->routes = $this->removeRouteByPath(
            $this->routes, $newRoute->get('path')
        );

        $this->routes[] = $newRoute;

        return $newRoute;
    }

    /**
     * Get all children.
     * 
     * @return array
     */
    public function getItems()
    {
        return $this->routes;
    }

    /**
     * Prepare data for JSON serialization.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getItems();
    }
}
