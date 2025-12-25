<?php

namespace Dev\Test\Tests\DB;

use Dev\Test\Inc\TestCase;
use Dev\Test\Inc\RefreshDatabase;

class DBWorksTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_db_works()
	{
		$this->assertIsArray(
			$this->plugin->db->getColumns('users')
		);

		// Check the users table is empty, initially it should be empty.
		$this->assertEquals(0, $this->plugin->db->table('users')->count());
	}
}