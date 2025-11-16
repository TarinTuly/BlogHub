<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\PostLike;

class PostLikeController extends Controller
{

   /**
     * @OA\Post(
     *     path="/api/posts/{postId}/like",
     *     summary="Toggle like on a post (like/unlike)",
     *     tags={"Likes"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post to like/unlike",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully toggled like",
     *         @OA\JsonContent(
     *             @OA\Property(property="liked", type="boolean", example=true),
     *             @OA\Property(property="like_count", type="integer", example=12)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Post not found"
     *     )
     * )
     */
 public function toggle(Post $post, Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($post->likes()->where('user_id', $user->id)->exists()) {
            $post->likes()->where('user_id', $user->id)->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        $like_count = $post->likes()->count();

        return response()->json([
            'liked' => $liked,
            'like_count' => $like_count
        ]);
    }
}
