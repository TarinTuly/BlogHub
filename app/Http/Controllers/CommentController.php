<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 
class CommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/posts/{post}/comments",
     *     summary="Get comments for a post",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="Post ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comments fetched successfully"
     *     ),
     *     security={{"sanctum":{}}}
     * )
     */
    public function index(Post $post)
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    /**
     * @OA\Post(
     *     path="/api/posts/{post}/comments",
     *     summary="Add a comment or reply",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="Post ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"body"},
     *             @OA\Property(property="body", type="string", example="This is a comment"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment added successfully"
     *     ),
     *     security={{"sanctum":{}}}
     * )
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'body' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'body' => $request->body
        ]);

        $comment->load('user');

        return response()->json($comment, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/comments/{comment}",
     *     summary="Update a comment",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="Comment ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"body"},
     *             @OA\Property(property="body", type="string", example="Updated comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully"
     *     ),
     *     security={{"sanctum":{}}}
     * )
     */
    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'body' => 'required|string'
        ]);

        $comment->update(['body' => $request->body]);

        return response()->json($comment);
    }

    /**
     * @OA\Delete(
     *     path="/api/comments/{comment}",
     *     summary="Delete a comment and its replies",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="Comment ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully"
     *     ),
     *     security={{"sanctum":{}}}
     * )
     */
    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete replies recursively
        $this->deleteReplies($comment);

        return response()->json(['message' => 'Comment deleted']);
    }

    // Recursive deletion of replies
    protected function deleteReplies(Comment $comment)
    {
        foreach ($comment->replies as $reply) {
            $this->deleteReplies($reply);
        }
        $comment->delete();
    }
}
