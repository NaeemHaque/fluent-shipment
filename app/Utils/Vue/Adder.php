<?php

namespace FluentShipment\App\Utils\Vue;

class Adder
{
    protected $group = null;

    public function __construct(&$group)
    {
        $this->group = &$group;
    }

    public function submenu($id)
    {
        $items = &$this->group;

        if ($this->group instanceof Submenu) {
            $items = &$this->group->getItems();
        }

        foreach ($items as &$item) {
            if ($item instanceof Route) continue;
            if (isset($item['submenu']) && $item['id'] === $id) {
                return new self($item['submenu'][0]);
            }
        }
    }

    /**
     * Add a new route to the submenu.
     * 
     * @param mixed $args
     * @return Route
     */
    public function add(...$args)
    {
        $route = new Route(...$args);

        if ($this->group instanceof Submenu) {
        	return $this->group->addOrReplace($route);
        }

        return $this->addOrReplace($route);
    }

    /**
     * Conditionally add a new route to the submenu.
     *
     * @param mixed ...$args Route constructor arguments.
     * @return Route|null
     */
    public function addIf(...$args)
    {
        $condition = array_pop($args);

        $shouldAdd = is_callable($condition) ? $condition() : (bool) $condition;

        if ($shouldAdd) {
            return $this->add(...$args);
        }

        // If false, allow method chaning without breaking
        return new class {
            public function __call($name, $args) {
                return new self;
            }
        };
    }

    /**
     * Add a new route or replace if exists in the submenu.
     * 
     * @param  Route $newRoute
     * @return Route
     */
    public function addOrReplace($newRoute)
    {
        foreach ($this->group as $index => $route) {
            if (
                $route instanceof Route &&
                $newRoute instanceof Route &&
                $route->get('path') === $newRoute->get('path')
            ) {
                $this->group[$index] = $newRoute;
                return $newRoute;
            }
        }

        $this->group[] = $newRoute;

        return $newRoute;
    }
}
