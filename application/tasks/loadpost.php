<?php

class Loadpost_Task {

    public function run( $arguments ) {
        if( count($arguments) == 0 || $arguments[0] == "" ){ 
            echo "please provide a file name"; 
            return; 
        }
        
        $location = path('storage') . "blogposts/" . $arguments[0];
        if( !file_exists($location) ){
            echo "was unable to find that post";
            return;
        }
        $contents = json_decode( File::get( $location ), true );
        if( !$contents ) {
            echo "make sure the file can be parse as json";
            return;
        }
        if( !$contents['title'] || !$contents['content'] ){
            echo "make sure there is a title and content in the json";
            return false;
        }
    
        $timestring = ( isset( $contents['time'] ) ) ? $contents['time'] : "now";
        $date = new DateTime( $timestring );
        DB::table('blogpost')->insert( array(
            'title'      => $contents['title'],
            'content'    => $contents['content'],
            'user_id'    => 1,
            'created_at' => $date,
            'updated_at' => $date
        ));
    }

}

?>