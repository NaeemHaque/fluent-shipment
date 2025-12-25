<?php

namespace Dev\Factories;

use __NAMESPACE\App\Models\Post;
use Dev\Factories\Core\Factory;

class PostFactory extends Factory
{
	protected static $model = Post::class;

	public function definition($data = [])
	{
		return [
			'post_author' => $data['post_author'] ?? 1,
			'post_title' => $this->fake->sentence(2),
			'post_content' => $this->fake->paragraph(5)
		];
	}
}
