<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Post;
use FluentShipment\Framework\Http\Request\Request;

class PostController extends Controller
{
    public function get(Request $request)
    {
        $status = $request->get('status');

        return Post::when($status, function ($query) use ($status) {
            $query->where('post_status', $status);
        })->latest('ID')->take(10)->get();
    }

    public function find(Request $request, $id)
    {
        return Post::find($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'post_title' => 'required|string',
            'post_content' => 'required|string',
        ]);

        return wp_insert_post(
            $request->all() + ['post_status' => 'publish']
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'post_title' => 'required|string',
            'post_content' => 'required|string',
        ]);

        return wp_update_post($request->all());
    }

    public function delete($id)
    {
        return Post::findOrFail($id)->delete();
    }

    public function upload(Request $request)
    {
        return $request->file('file')->save();
    }
}
