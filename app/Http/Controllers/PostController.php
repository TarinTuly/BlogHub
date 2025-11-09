<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Post::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $f=$request->validate([
            'title'=>'required|string|max:255',
            'body'=>'required|string',
        ]);
        $p=Post::create($f);
        return ['p'=>$p,'msg'=>'Post Created'];
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
        return $post;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $f=$request->validate([
            'title'=>'sometimes|required|string|max:255',
            'body'=>'sometimes|required|string',
        ]);
        $post->update($f);
        return ['p'=>$post,'msg'=>'Post Updated'];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {


        $post->delete();
        return ['msg'=>'Post Deleted'];
    }
}
