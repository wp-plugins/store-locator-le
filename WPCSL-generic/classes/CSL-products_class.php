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
        foreach ($products as $product) {
            $product_output[] = "<div class=\"{$this->prefix}-product\">";
            $product_output[] = "<h3>{$product->name}</h3>";
            
            // --- DISABLED ---
            // This check takes entirely too long and I have yet to find
            // any method that works properly *and* quickly.
            //
            // Clicking on the zoom link for a url of an image that's not
            // there causes thickbox to hang indefinitely, therefore we only
            // show the link (and the image) if the file exists.
            //        if (wpCJ_url_exists($product->image_urls[0])) {
            
            $product_output[] = "<div class=\"{$this->prefix}-left\">";
            $product_output[] = "<a href=\"{$product->web_urls[0]}\" target=\"newinfo\">";
            $product_output[] = "<img src=\"{$product->image_urls[0]}\" alt=\"{$product->name}\" title=\"{$product->name}\" />";
            $product_output[] = '</a><br/>';
            $product_output[] = '<a class="thickbox" href="'.$product->image_urls[0].'">+zoom</a>';
            $product_output[] = '</div>';
            
            //        }
            
            
            $product_output[] = '<div class="'.$this->prefix . '-right">';
            $product_output[] = '<p class="' . $this->prefix . '-desc" >'.$product->description.'</p>';
            $product_output[] = '<p class="' . $this->prefix . '-price">'.$product->currency;
            $product_output[] = "$<a href=\"{$product->web_urls[0]}\" target=\"newinfo\">";
            if (function_exists('money_format') && 
                    get_option($this->prefix.'-money_format') && 
                    (get_option($this->prefix.'-money_format') != '')) {
                $product_output[] = money_format(get_option($this->prefix.'-money_format'), 
                    (float)$product->price);
            } else {
                $product_output[] = number_format((float)$product->price, 2);
            }
            $product_output[] = '</a>';
            $product_output[] = '</p>';
            $product_output[] = '</div>';
            $product_output[] = '<div class="'.$this->prefix.'-cleanup"></div>';            
            $product_output[] = '</div>';            
        }

        return implode("\n", $product_output);
    }    


}
