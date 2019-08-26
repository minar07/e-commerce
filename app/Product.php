<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   
// 5:42 / 36:41
// Laravel E-Commerce - Categories & Pagination - Part 5
    public function categories()
    {
        return $this->belongsToMany('App\Category');
    }


    // making things dynamic-part-1 (11:34)
    // Alternative to money_format()
   public function presentPrice()
    {
        return "BDT ".number_format($this->price, 2);;
    }
    
    public function scopeMightAlsoLike($query)
    {
        return $query->inRandomOrder()->take(4);
    }
}
