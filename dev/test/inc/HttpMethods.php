<?php

namespace Dev\Test\Inc;

trait HttpMethods
{
	public function get($uri, $params = [], $headers = [])
    {
        return $this->dispatch(__FUNCTION__, $uri, $params, $headers);
    }

    public function post($uri, $params = [], $headers = [])
    {
        return $this->dispatch(__FUNCTION__, $uri, $params, $headers);
    }

    public function patch($uri, $params = [], $headers = [])
    {
        return $this->dispatch(__FUNCTION__, $uri, $params, $headers);
    }

    public function put($uri, $params = [], $headers = [])
    {   
        return $this->dispatch(__FUNCTION__, $uri, $params, $headers);
    }

    public function delete($uri, $params = [], $headers = [])
    {
        return $this->dispatch(__FUNCTION__, $uri, $params, $headers);
    }
}
