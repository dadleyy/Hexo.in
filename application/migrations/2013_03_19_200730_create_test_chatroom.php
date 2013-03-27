<?php

class Create_Test_Chatroom {

    public function up( ){
        $date = new DateTime( );
        
        DB::table('chatrooms')->insert( array(
            'token'      => sha1("zomg"),
            'name'       => 'General',
            'created_at' => $date,
            'updated_at' => $date
        ));
        
        $file_location = Chatroom::find(1)->chatfile( );
        $file_contents = array(
            "messages" => array( ),
            "token" => Chatroom::find(1)->token
        );
        File::put( $file_location, json_encode( $file_contents ) );
    }

	public function down( ){
    	
	}


}