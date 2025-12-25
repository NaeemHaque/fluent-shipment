<?php

namespace FluentShipment\App\Utils\Vue;

use JsonSerializable;

class Route implements JsonSerializable
{
    use Enqueueable;
    
    /**
     * Route item.
     * 
     * @var array
     */
    protected array $item;

    /**
     * Route constructor.
     * 
     * @param string $path
     * @param string $component
     * @param string $name
     */
    public function __construct($path, $component, $name = '')
    {
        $this->item = [
            'name'      => $name,
            'path'      => $path,
            'component' => $component,
            'props'     => true,
            'children'  => [],
            'meta'      => [],
        ];
    }

    /**
     * Add meta data to the menu item.
     * 
     * @param  array|string $key
     * @param  mixed $value[description]
     * @return self
     */
    public function meta($key, $value = null)
    {
        if (is_array($key)) {
            $this->item['meta'] = array_merge($this->item['meta'], $key);
        } else {
            $this->item['meta'][$key] = $value;
        }

        return $this;
    }

    /**
     * Add middleware(s) to the menu item's meta.
     *
     * @param  string|array $middlewares
     * @return self
     */
    public function middleware($middlewares)
    {
        $middlewares = (array) $middlewares;

        if (!isset($this->item['meta']['middleware'])) {
            $this->item['meta']['middleware'] = [];
        }

        $this->item['meta']['middleware'] = array_merge(
            $this->item['meta']['middleware'],
            $middlewares
        );

        return $this;
    }

    /**
     * Set the icon for menu item.
     * 
     * @param  string $icon
     * @return self
     */
    public function icon($icon)
    {
        $this->item['meta']['icon'] = $icon;

        return $this;
    }

    /**
     * Set the label for menu item.
     * 
     * @param  string $label
     * @return self
     */
    public function label($label)
    {
        $this->item['label'] = $label;
        
        return $this;
    }

    /**
     * Set the label for menu item.
     * 
     * @param  string $label
     * @return self
     */
    public function title($label)
    {   
        return $this->label($label);
    }

    /**
     * Set the name for menu item.
     * 
     * @param  string $name
     * @return self
     */
    public function name($name)
    {
        return $this->set('name', $name);
    }

    /**
     * Specify whether the menu item can receive props.
     * 
     * @param  bool $value
     * @return self
     */
    public function props($value = true)
    {
        return $this->set('props', $value);
    }

    /**
     * Specify that the menu item can't receive props.
     * 
     * @return self
     */
    public function noProps()
    {
        return $this->props(false);
    }

    /**
     * Add a child menu item.
     * 
     * @param  mixed ...$args  Arguments to pass to the Route constructor.
     * @return Route
     */
    public function child(...$args)
    {
        $child = new Route(...$args);

        if (!isset($this->item['children'])) {
            $this->item['children'] = [];
        }

        $this->item['children'][] = $child;

        return $child;
    }

    /**
     * Conditionally add a child route.
     *
     * @param mixed ...$args
     * @return Route|object
     */
    public function childIf(...$args)
    {
        $condition = array_pop($args);

        $shouldAdd = is_callable($condition) ? $condition() : (bool) $condition;

        if ($shouldAdd) {
            return $this->child(...$args);
        }

        // Return a dummy object that ignores method calls
        return new class {
            public function __call($name, $args) {
                return new self;
            }
        };
    }

    /**
     * Add children menu items.
     * 
     * @param  callable $callback
     * @return self
     */
    public function children($callback)
    {
        $children = new Children;

        $callback($children);

        $this->item['children'] = $children;

        return $this;
    }

    /**
     * Get one or all items of the route.
     * 
     * @param  string|null $key
     * @return mixed
     */
    public function get($key = null)
    {
        if ($key) {
            return isset($this->item[$key]) ? $this->item[$key] : null;
        }

        return $this->item;
    }

    /**
     * Set an item in the route.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->item[$key] = $value;

        return $this;
    }

    /**
     * Clone the route (shallow copy).
     * 
     * @return self
     */
    public function copy()
    {
        $route = clone $this;

        return $route;
    }

    /**
     * Serialize the route item.
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->item;
    }
}
