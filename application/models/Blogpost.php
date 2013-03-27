<?php

class Blogpost extends Eloquent {

    public static $table = 'blogpost';
    
    public function posted( ){
        return date( 'M j, Y', strtotime($this->created_at) );
    }
    
    public function author( ){
        return User::find( $this->user_id );
    }
    
}

?>