<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/posts",
     *     summary="Get posts",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of posts"
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $posts = Post::with('user')->latest()->get();
        } else {
            $posts = Post::where('user_id', $user->id)->latest()->get();
        }

        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/api/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","body"},
     *             @OA\Property(property="title", type="string", example="My first post"),
     *             @OA\Property(property="body", type="string", example="This is my post content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully"
     *     )
     * )
     */
   public function store(Request $request)
{
    $user = Auth::user();

    // Validate fields
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'body'  => 'required|string',
        'user_id' => 'nullable|exists:users,id', // optional for normal user
    ]);

    // Determine the user for the post
    if ($user->role === 'admin') {
        // Admin must select a user
        if (empty($validated['user_id'])) {
            return response()->json(['error' => 'Admin must select a user for this post.'], 422);
        }
        $postUserId = $validated['user_id'];
    } else {
        // Normal user posts for themselves
        $postUserId = $user->id;
    }

    $post = Post::create([
        'title' => $validated['title'],
        'body' => $validated['body'],
        'user_id' => $postUserId,
    ]);

    return response()->json([
        'message' => 'Post created successfully',
        'post' => $post
    ], 201);
}



// Update Post
/**
 * @OA\Put(
 *     path="/api/posts/{id}",
 *     summary="Update a post",
 *     tags={"Posts"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Post ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"title","body"},
 *                 @OA\Property(property="title", type="string", example="Updated title"),
 *                 @OA\Property(property="body", type="string", example="Updated body")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Post updated successfully"
 *     )
 * )
 */
public function update(Request $request, $id)
{
    $post = Post::findOrFail($id);

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'body' => 'required|string',
    ]);

    $post->update($validated);

    return response()->json([
        'message' => 'Post updated successfully',
        'post' => $post
    ]);
}


// Delete Post
/**
 * @OA\Delete(
 *     path="/api/posts/{id}",
 *     summary="Delete a post",
 *     tags={"Posts"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Post ID",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Post deleted successfully"
 *     )
 * )
 */
public function destroy($id)
{
    $post = Post::find($id);

    if (!$post) {
        return response()->json(['error' => 'Post not found'], 404);
    }

    $post->delete();

    return response()->json(['message' => 'Post deleted successfully']);
}


}


