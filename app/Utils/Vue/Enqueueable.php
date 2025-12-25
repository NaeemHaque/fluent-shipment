<?php

namespace FluentShipment\App\Utils\Vue;

trait Enqueueable
{
	public function enqueue($callback)
	{
		$callback();

		return $this;
	}
}
