<?php

namespace Dev\Test\Tests\Rest;

use Dev\Test\Inc\TestCase;

class RestWorksTest extends TestCase
{
	public function test_rest_works_as_guest()
	{
		$this->router->get('test', function() {});
		
		$response = $this->get('test');

		$this->assertTrue($response->isOkay());
	}

	public function test_rest_works_as_Admin()
	{
		$this->router->get('test', function() {})->withPolicy(
			fn() => current_user_can('administrator')
		);
		
		$response = $this->ActAsAdmin()->get('test');

		$this->assertTrue($response->isOkay());
	}

	public function test_core_wp_rest_endpoint_works()
	{
		$user = $this->createWPUser();

		$this->factory->post->count(10)->create([
			'post_author'  => $user->ID,
	    ]);

	    $response = $this->wp()->get('posts', [
	        'per_page' => 2,
	    ]);

	    $response->assert200()->assertIsArray();
	}

	public function test_rest_api_pagination_works_as_expected()
	{
	    $user = $this->createWPUser();

	    // Create 10 published posts
	    $this->factory->post->count(10)->create([
	        'post_author'   => $user->ID,
	    ]);

	    // Request page 1 with 3 posts per page
	    $responsePage1 = $this->wp()->get('posts', [
	        'per_page' => 3,
	        'page'     => 1,
	    ]);

	    $dataPage1 = $responsePage1->assert200()->assertIsArray()->getData();
	    $headersPage1 = $responsePage1->getHeaders();

	    $this->assertCount(3, $dataPage1);

	    // Assert pagination headers
	    $this->assertEquals(10, (int) $headersPage1['X-WP-Total']);
	    $this->assertEquals(4, (int) $headersPage1['X-WP-TotalPages']);

	    // Request page 2
	    $responsePage2 = $this->wp()->get('posts', [
	        'per_page' => 3,
	        'page'     => 2,
	    ]);

	    $dataPage2 = $responsePage2->assert200()->assertIsArray()->getData();

	    $this->assertCount(3, $dataPage2);

	    // Verify posts are different between page 1 and 2
	    $this->assertNotEquals($dataPage1[0]['id'], $dataPage2[0]['id']);
	}
}
