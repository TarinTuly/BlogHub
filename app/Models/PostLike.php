<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PostLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id'
    ];

    // Each like belongs to a post
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Each like belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
