<?php

// 28:37 / 51:03
// Laravel E-Commerce - Shopping Cart - Part 2
 function presentCartPrice($price){

    return "BDT ".number_format($price, 2);;

}


// 35:13 / 36:39
// Laravel E-Commerce - Categories & Pagination - Part 5 (Make Category Active)
function setActiveCategory($category, $output = 'active'){
    
return request()->category == $category ? $output : '';

}

