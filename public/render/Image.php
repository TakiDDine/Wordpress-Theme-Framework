<?php

namespace HelloFramework\Render;

use HelloFramework;

/*

IMAGE LOADER 

Louis Walch / say@hellolouis.com


-- EXAMPLES --
-- Note: You must also have the IMAGE instance helper function in global scope --

// Output <IMG> element from custom field value.
echo IMAGE()->img(get_field('image'));

// Output <IMG> element from Featured Image with class attribute.
echo IMAGE()->class('thumbnail')->div(get_post_thumbnail_id()); 

// Output <IMG> for lazy loading from custom field. 
echo IMAGE()->classes('lazy image')->alpha(true)->img(get_field('image'));

// Output <IMG> from a custom field, adding custom attributes, example 1.
echo IMAGE()->attr(array('data-test1'=>'value1', 'data-test2'=>'value2'))->img(get_field('image'));

// Output <IMG> from a custom field, adding custom attributes, example 2.
echo IMAGE()->attr('data-test1', 'value1')->img(get_field('image'));

// Output a <DIV> element with from image in a custom field with class attribute.
echo IMAGE()->classes('thumbnail')->div(get_field('image'));

// Output a <DIV> element from image in a custom field, setting it's base size to something smaller.
echo IMAGE()->size(600)->div(get_field('image'));

// Output just the image src (e.g. for use in Meta Tag), passing in the size we want.
echo IMAGE()->src(get_post_thumbnail_id(), 800); 

*/

class ImageRender extends HelloFramework\Singleton {

    private $_alphadata = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

    private $_alpha;
    private $_attr;
    private $_classes;
    private $_pinnable;
    private $_showcaption;
    private $_low;
    private $_low_size;
    private $_size;
    private $_srcset;
    private $_wrap;

    public $max         = 2400;

    // ------------------------------------------------------------

    public function __construct() {

        parent::__construct();

        // Reset all the request settings.
        $this->_reset();

    }

    // ------------------------------------------------------------
    // Default options, these will get reset to original values once a request has completed.
    private function _reset() {

        $this->_alpha       = false;
        $this->_attr        = array();
        $this->_classes     = array();
        $this->_pinnable    = CONFIG('render/image/default_pinnable');
        $this->_low         = false;
        $this->_low_size    = '400';
        $this->_showcaption = CONFIG('render/image/default_caption');
        $this->_size        = CONFIG('render/image/default_size');
        $this->_srcset      = true;
        $this->_wrap        = CONFIG('render/image/default_wrap');

    }


    // ------------------------------------------------------------
    // Image getters.

    // private function _getImageObject($image) {

    //     // Sometimes an object is passed in.
    //     if (is_object($image) && property_exists($image, 'ID')) {
    //         return $image;
    //     }

    //     // Sometimes an array is passed in.
    //     if (is_array($image) && array_key_exists('ID', $image)) {
    //         return $image;
    //     }

    //     // Now make sure we have an ID. Should this error?
    //     if (is_numeric($image)) {
    //         return wp_get_attachment_image($image);
    //     }

    //     return false;

    // }

    private function _getImageId($image) {

        // Sometimes an object is passed in.
        if (is_object($image) && property_exists($image, 'ID')) {
            return $image->ID;
        }

        // Sometimes an array is passed in.
        if (is_array($image) && array_key_exists('ID', $image)) {
            return $image['ID'];
        }

        // Now make sure we have an ID. Should this error?
        if (is_numeric($image)) {
            return $image;
        }

        return false;

    }

    private function _getImageAlt($image) {

        // Sometimes an object is passed in.
        if (is_object($image) && property_exists($image, 'alt')) {
            return $image->alt;
        }

        // Sometimes an array is passed in.
        if (is_array($image) && array_key_exists('alt', $image)) {
            return $image['alt'];
        }

        return '';

    }

    private function _getImageCaption($image) {

        if (is_object($image) && property_exists($image, 'caption')) {
            return $image->caption;
        }

        if (is_array($image) && array_key_exists('caption', $image)) {
            return $image['caption'];
        }

        if (is_numeric($image)) {
            return wp_get_attachment_caption($image);
        }

        return false;

    }

    private function _getImageData($image) {

        $image_id       = $this->_getImageId($image);

        $image_caption  = $this->_getImageCaption($image);

        $image_alt      = $this->_getImageAlt($image);

        $image_src      = wp_get_attachment_image_url($image_id, $this->_size);
        $image_srclow   = $this->_low ? wp_get_attachment_image_url($image_id, $this->_low_size) : '';

        $image_srcset   = $this->_srcset ? wp_get_attachment_image_srcset( $image_id, $this->_size ) : '';
        $image_sizes    = $this->_srcset ? ('(max-width: '.$this->max.'px) 100vw, '.$this->max.'px') : '';

        $image_align    =  (class_exists('acf')) ? get_field('crop_alignment', $image_id) : '';

        return array(
            'alt'       => $image_alt,
            'caption'   => $image_caption,
            'class'     => $image_align .' '. implode(' ', $this->_classes),
            'src'       => $image_src,
            'src_low'   => $image_srclow,
            'srcset'    => $image_srcset,
            'sizes'     => $image_sizes
            );

    }


    // ------------------------------------------------------------
    // Generate Pinterest image. Used when drawing as a background image.

    private function _getPinterestForDiv($image=false, $alt='') {    

        // if ($this->_pinnable) {

        //     $image_id       = $this->_getImageId($image);
        //     $image_src      = wp_get_attachment_image_url($image_id, 1200);

        //     if ($image_src) {
        //         return '<img class="pinterest" src="'. $image_src .'" alt="'. $alt .'" style="display: none;" />';                    
        //     }

        // }

        return '';
        
    }


    // ------------------------------------------------------------
    // Build HTML attributes from an array.
    // https://stackoverflow.com/a/34063755/107763       

    private function _getAttributes($array=array()) {     

        $array = array_merge($array, $this->_attr);

        return implode(' ', array_map(
            function ($k, $v) { return $k .'="'. htmlspecialchars($v) .'"'; },
            array_keys($array), $array));
        
    }


    // ------------------------------------------------------------
    // Sometimes we wrap the output in another div.

    private function _getWrap($string) {

        if (!$this->_wrap) return $string;

        $class = is_string($this->_wrap) ? $this->_wrap : '';

        return '<div class="image_wrapper '.$class.'">' . $string .'</div>';

    }

    // ------------------------------------------------------------
    // Allow for updating of options when generating an image. Meant to be used as a chained method.

    public function low($incoming=true) {
        if (isset($incoming)) $this->_low = $incoming;
        return $this;
    }
    public function srcset($incoming=true) {
        if (isset($incoming)) $this->_srcset = $incoming;
        return $this;
    }
    public function wrap($incoming=false) {
        if (isset($incoming)) $this->_wrap = $incoming;
        return $this;
    }
    public function caption($incoming=null) {
        if (!is_null($incoming)) $this->_showcaption = $incoming;
        return $this;
    }
    public function classes($incoming=false) {
        if ($incoming) $this->_classes[] = $incoming;
        return $this;
    }
    public function lazy(){
        $this->_alpha = true;
        $this->_classes[] = 'lazyload';
        $this->_attr['data-sizes'] = 'auto';
        return $this;            
    }
    public function size($incoming=false) {
        if ($incoming) $this->_size = $incoming;
        return $this;
    }
    public function alpha($incoming=false) {
        if (isset($incoming)) $this->_alpha = $incoming;
        return $this;
    }
    public function attributes($incoming=false) {
        return $this->attr($incoming);
    }
    public function attr($one=false, $two=null) {
        if (is_string($one) && !is_null($two)) {
            $this->_attr[$one] = $two;
        } else if (is_array($one)) {
            $this->_attr = $one;
        }
        return $this;
    }
    public function pinnable($incoming=false) {
        if (isset($incoming) && is_bool($incoming)) $this->_pinnable = $incoming;
        return $this;
    }


    // ------------------------------------------------------------
    // Image Generator: Return a DIV element with this image as it's data-src for lazy loading. 

    public function div($image=false, $size=false) {

        $this->size($size);

        $data           = $this->_getImageData($image);

        $caption        = (!empty($data['caption']) && $this->_showcaption ) ? ('<div class="caption">'.$data['caption'].'</div>') : '';
        $pinterest      = $this->_getPinterestForDiv($image, $data['caption']);

        $data[CONFIG('render/image/div_src')]       = $data['src'];
        $data[CONFIG('render/image/div_srcset')]    = $data['srcset'];

        if ($this->_low) {
            $data['style'] = 'background-image: url(' . $data['src_low'] . ');';
        }

        unset($data['alt']);
        unset($data['caption']);
        unset($data['src_low']);
        unset($data['src']);
        unset($data['srcset']);

        $attributes     = $this->_getAttributes($data);
        $output         = $this->_getWrap('<div '.$attributes.'>'. $caption . $pinterest .'</div>');

        // Reset all the request settings.
        $this->_reset();

        return $output;
    }


    // ------------------------------------------------------------
    // Image Generator: Return a IMG element with this image. 

    public function img($image=false, $size=false) {

        $this->size($size);

        $data                   = $this->_getImageData($image);

        if ($this->_alpha) {
            $data['data-src']   = $data['src'];
            $data['src']        = $this->_alphadata;
        }

        if (!$this->_pinnable) {
            $data['data-pin-nopin'] = 'true';
        }

        if ($this->_low) {
            $data['src'] = $data['src_low'];
        }

        unset($data['src_low']);

        $attributes             = $this->_getAttributes($data);
        $output                 = '<img '.$attributes.' />';

        $output .= (!empty($data['caption']) && $this->_showcaption ) ? ('<div class="caption">'.$data['caption'].'</div>') : '';

        $output                 = $this->_getWrap($output);

        // Reset all the request settings.
        $this->_reset();

        return $output;

    }

    // ------------------------------------------------------------
    // Image Generator: Just the image source.

    public function src($image=false, $size=false) {

        if (!$image) return false;

        $this->size($size);

        $image_id       = $this->_getImageId($image);

        $image_src      = wp_get_attachment_image_url($image_id, $this->_size);

        // Reset all the request settings.
        $this->_reset();

        return $image_src;
    }

    // ------------------------------------------------------------
    // Caption Generator: Just the caption.

    public function get_caption($image=false){
        $data                   = $this->_getImageData($image);
        return (!empty($data['caption'])) ? $data['caption'] : '';
    }

}