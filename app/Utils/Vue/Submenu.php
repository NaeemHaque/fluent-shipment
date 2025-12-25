<?php

namespace FluentShipment\App\Utils\Vue;

use JSONSerializable;

class Submenu implements JSONSerializable
{
    use CommonTrait, Enqueueable;

    /**
     * Router Instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Child routes.
     *
     * @var array
     */
    protected array $routes = [];

    /**
     * Create a new submenu.
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Add a new route to the submenu.
     *
     * @param array $args
     * @return Route
     */
    public function add(...$args)
    {
        if ($args[0] instanceof Route) {
            $route = $args[0];
        } else {
            $route = new Route(...$args);
        }

        $this->routes = $this->removeRouteByPath(
            $this->routes, $route->get('path')
        );

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add a new route or replace if exists in the submenu.
     * 
     * @param  Route $newRoute
     * @return Route
     */
    public function addOrReplace($newRoute)
    {
        foreach ($this->routes as $index => $route) {
            if (
                $route instanceof Route &&
                $newRoute instanceof Route &&
                $route->get('path') === $newRoute->get('path')
            ) {
                $this->routes[$index] = $newRoute;
                return $newRoute;
            }
        }

        $this->routes[] = $newRoute;

        return $newRoute;
    }

    /**
     * Add a nested submenu.
     *
     * @param string   $label
     * @param callable $callback
     * @return self
     */
    public function submenu($label, $callback)
    {
        return $this->group($label, $callback);
    }

    /**
     * Add a group of submenu items.
     *
     * @param array|string   $label
     * @param callable $callback
     * @return self
     */
    public function group($label, $callback)
    {
        $meta = [];

        if (is_array($label)) {
            $meta = $label;
            $label = $meta['label'];
        }

        $submenu = new static($this->router);

        $callback($submenu);

        $this->routes[] = [
            'id'    => $meta['name'] ?? '',
            'icon'    => $meta['icon'] ?? '',
            'label'   => $label,
            'submenu' => [$submenu->getItems()],
        ];

        return $this;
    }

    /**
     * Get all submenu items.
     *
     * @return array
     */
    public function &getItems()
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
