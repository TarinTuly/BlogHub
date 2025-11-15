<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Comment extends Model
{
    //
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'parent_id', 'body'];

    // Each comment belongs to a post
    public function post() {
        return $this->belongsTo(Post::class);
    }

    // Each comment belongs to a user
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Each comment can have replies (self relationship)
    // public function replies() {
    //     return $this->hasMany(Comment::class, 'parent_id');
    // }
    public function replies()
{
    return $this->hasMany(Comment::class, 'parent_id')->with('user', 'replies');
}

    // Optional: for like functionality later
    // public function likes() {
    //     return $this->morphMany(Like::class, 'likeable');
    // }
}
