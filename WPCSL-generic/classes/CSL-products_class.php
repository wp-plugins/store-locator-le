<?php

/***********************************************************************
* Class: wpCSL_products
*
* Contains various products methods to assist in making
* WordPress Plugins easier to build for product-centric plugins,
* primarily the MoneyPress series.
*
************************************************************************/

class wpCSL_products__slplus {
    


    /*-------------------------------------
     * method: __construct
     *
     * Overload of the default class instantiation.
     *
     */
    function __construct($params) {
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }
    }
    
    /*-------------------------------------
     * method: display_products
     *
     * Legacy Panhandler stuff that will eventually come out.
     * This method generates the HTML that will be used to display
     * the product list in WordPress when it renders the page.
     *
     */
    function display_products($products) {
        $product_output[] = '';
        $moneyFormat = get_option($this->css_prefix.'-money_format');
        foreach ($products as $product) {
            $product_output[] = "<div class=\"{$this->css_prefix}-product\">";
            $product_output[] = "<h3>{$product->name}</h3>";
            $product_output[] = "<div class=\"{$this->css_prefix}-left\">";
            $product_output[] = "<a href=\"{$product->web_urls[0]}\" target=\"newinfo\">";
            $product_output[] = "<img src=\"{$product->image_urls[0]}\" alt=\"{$product->name}\" title=\"{$product->name}\" />";
            $product_output[] = '</a><br/>';
            $product_output[] = '<a class="thickbox" href="'.$product->image_urls[0].'">+zoom</a>';
            $product_output[] = '</div>';
            $product_output[] = '<div class="'.$this->css_prefix . '-right">';
            $product_output[] = '<p class="' . $this->css_prefix . '-desc" >'.$product->description.'</p>';
            $product_output[] = '<p class="' . $this->css_prefix . '-price">'.$product->currency;
            if (function_exists('money_format') &&  ($moneyFormat != '')) {
                $product_output[] =
                    "$<a href=\"{$product->web_urls[0]}\" target=\"newinfo\">".
                    trim(money_format($moneyFormat, (float)$product->price)) .
                    '</a>';
            } else {
                $product_output[] =
                    "$<a href=\"{$product->web_urls[0]}\" target=\"newinfo\">".
                    trim(number_format((float)$product->price, 2)) .
                    '</a>';
            }
            $product_output[] = '</p>';
            $product_output[] = '</div>';
            $product_output[] = '<div class="'.$this->css_prefix.'-cleanup"></div>';            
            $product_output[] = '</div>';            
        }

        return implode($product_output);
    }    


}
