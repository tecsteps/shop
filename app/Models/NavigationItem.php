<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    protected $fillable = ['navigation_menu_id', 'parent_id', 'label', 'url', 'position'];
}

