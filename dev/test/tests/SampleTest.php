<?php

namespace Dev\Test\Tests;

use Dev\Test\Inc\App;
use Dev\Test\Inc\TestCase;
use Dev\Test\Inc\RefreshDatabase;
use Dev\Test\Inc\UsersAndPostsSeeder;
use __NAMESPACE\App\Models\User;

class SampleTest extends TestCase
{
	use RefreshDatabase;
	use UsersAndPostsSeeder;

	public function setUp(): void
	{
		parent::setUp();
		$this->seedUsersAndPosts();
	}

	public function testWorks()
	{
		$this->assertCount(10, User::get());
	}

	public function testEditorContext()
	{	
		$this->actLike('editor');
		
		$this->assertTrue(current_user_can('edit_posts'));
	}

	public function testAdminContext()
	{	
		$this->actLikeAdmin();
		
		$this->assertTrue(current_user_can('administrator'));
	}
}
