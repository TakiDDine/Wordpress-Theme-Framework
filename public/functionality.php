<?php

namespace {


    // ---------------------------------------------------------------------------
    // Get path to item in theme asset directory (CSS, JS, Images, etc).

    function asset($file='', $echo=false, $server=false, $directory='') {

        $root = ($server) ? (get_template_directory()) : (get_stylesheet_directory_uri());
        $path = $root . $directory . '/assets/' . $file;

        if ($echo) echo $path;
            
        return $path;

    }

    function framework_asset($file='', $echo=false, $server=false) {
        
        return asset($file, $echo, $server, '/_framework');

    }

    // ---------------------------------------------------------------------------
    // Require all files in specified directory. 

    function require_all_files($directory = false) {

        if (!$directory) return false;
        if (!is_dir($directory)) {
            $directory = get_template_directory() . '/' . $directory;
            if (!is_dir($directory)) return false;
        }
        
        if ($dh = opendir($directory)){
            while (($file = readdir($dh)) !== false) {

                // if (substr($file, 0, 1) === '.') continue;
                if (substr($file, 0, 3) === 'OFF') continue;
                if (substr($file, -4) !== '.php') continue;

                require($directory . $file);
        
            }
            closedir($dh);
        }

    }

    
    // ---------------------------------------------------------------------------
    // Detect where in Wordpress we are.

    function detect_zone($match = false) {

        static $result = NULL;

        if (is_null($result)) {

            if ($_SERVER['PHP_SELF'] === '/wp-login.php'){
                $result = 'login';
            } else if (is_admin()) {
                $result = 'admin';
            } else {
                $result = 'frontend';
            }
        
        }

        if ($match && is_string($match)) {
            return ($match == $result);
        } else {
            return $result;
        }

    }


    // ---------------------------------------------------------------------------
    // Detect site environment based on url. Can be handy to know.

    function detect_environment($match = false) {

        static $result = NULL;

        if (is_null($result)) {

            $domain                 = $_SERVER['SERVER_NAME'];
            $development_matches    = CONFIG('environment/development');
            $staging_matches        = CONFIG('environment/staging');

            foreach ($development_matches as $value) {
                if (strpos($domain, $value) !== false) {
                    $result = 'development';
                }
            }

            if (is_null($result)) {
                foreach ($staging_matches as $value) {
                    if (strpos($domain, $value) !== false) {
                        $result = 'staging';
                    }
                }
            }

            if (is_null($result)) {
                $result = 'production';
            }

        }

        if ($match && is_string($match)) {
            return ($match == $result);
        } else {
            return $result;
        }

    }

    // ---------------------------------------------------------------------------
    // Detect if current request is AJAX.

    function is_ajax_request() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return defined('DOING_AJAX');
    }

    
    // ---------------------------------------------------------------------------
    // Get the slug fo the top-most directory this page is in - e.g. it's section.

    function top_level_slug() {

        $path   = trim($_SERVER['REQUEST_URI'], '/');
        $parts  = explode("/", $path); 

        if (count($parts)) {
            return $parts[0];
        }

        return false;

    }

}
