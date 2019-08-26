<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = 'category';

// 5:22 / 36:41
// Laravel E-Commerce - Categories & Pagination - Part 5
    public function products()
    {
        return $this->belongsToMany('App\Product');
    }
}
