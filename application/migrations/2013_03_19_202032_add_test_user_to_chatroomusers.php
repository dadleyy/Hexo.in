<?php

class Add_Test_User_To_Chatroomusers {

	public function up( ) { 
        $chat = new Chatroom;
        
        $chat->game_id = 0;
        $chat->token = sha1( "generalchat" );
        $chat->name = "General Chat";
        
        $chat->createJSON( );
        $chat->save( );
        
        /* add the current user to that chatroom */
        $date = new DateTime( );
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => 1,
            'user_id'     => 1,
            'token'       => sha1( "11" ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));

	}
	
	public function down( ) { 
    	
	}

}