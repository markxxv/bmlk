<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReviewEmailOptOut extends Model
{
    protected $guarded = ['id'];

    public static function hashEmail(string $email): string
    {
        return hash_hmac('sha256', Str::lower(trim($email)), (string) config('app.key'));
    }

    public static function contains(string $email): bool
    {
        return static::query()->where('email_hash', static::hashEmail($email))->exists();
    }
}
