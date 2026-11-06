<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'price_gnf', 'max_users', 'max_branches', 'max_products', 'features', 'is_active'];

    protected $casts = ['features' => 'array', 'is_active' => 'boolean'];
}
