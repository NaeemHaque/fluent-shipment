<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\User;
use FluentShipment\Framework\Http\Request\Request;

class UserController extends Controller
{
	public function get()
	{
		return User::latest('ID')->paginate(2);
	}

	public function find($id)
	{
		return User::find($id);
	}

	public function create(Request $request)
	{
		$request->validate([
			'user_nicename' => 'required',
			'user_email' => 'required|email',
		]);

		return [
			'user' => User::create($request->all())
		];
	}

	public function update(Request $request, $id)
	{
		$request->validate([
			'user_nicename' => 'required',
			'user_email' => 'required|email',
		]);
		
		return [
			'user' => User::findOrFail($id)->update($request->all())
		];
	}

	public function delete($id)
	{
		return User::findOrFail($id)->delete();
	}
}
