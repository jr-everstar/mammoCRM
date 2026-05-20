<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialSetting extends Model
{
    protected $guarded = [];

    public static function value(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
