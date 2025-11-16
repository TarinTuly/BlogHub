<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
   use HasFactory;

    protected $fillable = ['title', 'body', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Cascade delete comments when post is deleted
    protected static function booted()
    {
        static::deleting(function ($post) {
            $post->comments()->each(function($comment) {
                $comment->replies()->delete(); // delete nested replies if your Comment has replies
            });
            $post->comments()->delete(); // delete all comments
        });
    }

    public function likes()
   {
    return $this->hasMany(PostLike::class);
   }
}
