<?php

namespace FluentShipment\App\Utils\Vue;

use Exception;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;

class Group implements JsonSerializable, IteratorAggregate
{
    use CommonTrait, Enqueueable;
    
    /**
     * Group name.
     *
     * @var string
     */
    protected $name;

    /**
     * Router Instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * The menu items.
     * 
     * @var array
     */
    protected array $routes = [];

    /**
     * Create a new named menu.
     * 
     * @param string $name
     */
    public function __construct($name, $router = null)
    {
        $this->name = $name;
        $this->router = $router;
    }

    /**
     * Add new menu item(s).
     * 
     * @param mixed $items
     * @return self
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
     * Add a new submenu.
     * 
     * @param string   $label
     * @param callable $callback
     * @return self
     */
    public function group($label, $callback)
    {
        return $this->submenu($label, $callback);
    }

    /**
     * Add a new submenu.
     * 
     * @param array|string   $label
     * @param callable $callback
     * @return self
     */
    public function submenu($label, $callback)
    {
        $meta = [];

        if (is_array($label)) {
            $meta = $label;
            $label = $label['label'];
        }

        $submenu = new Submenu($this->router);

        $callback($submenu);

        $this->routes[] = [
            'name' => $meta['name'] ?? null,
            'id'    => $meta['name'] ?? null,
            'icon'    => $meta['icon'] ?? null,
            'label'   => $label,
            'submenu' => [$submenu],
        ];

        return $this;
    }

    /**
     * Get the menu items.
     * 
     * @return array
     */
    public function getItems()
    {
        return $this->routes;
    }

    /**
     * Set the menu items.
     * 
     * @param array $items
     * @return self
     */
    public function setItems($items)
    {
        $this->routes = $items;

        return $this;
    }

    /**
     * Build the group output.
     *
     * @return array
     */
    public function build()
    {
        return [
            $this->name => $this->routes
        ];
    }

    /**
     * Convert to JSON.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->build();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }
}
