<?php

class Loadblogposts_Task {

    public function run ( $arguments = array() ){
        
        $post_path = path('storage') . 'blogposts';
        $post_dir = opendir( $post_path );
        while ( false !== ( $entry = readdir($post_dir) ) ) {
            $entry_path = $post_path . "/" . $entry;
            $entry_info = pathinfo( $entry_path );
            
            if( is_dir( $entry_path) || $entry_info['extension'] !== "json" )
                continue;
        
            exec( 'php artisan loadpost ' . $entry_info['basename'] );
        
            echo $post_path . "/" . $entry . "\r\n";
         }
        
        echo "finished loading";
    }

}

?>