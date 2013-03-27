<?php

class Add_Test_User_To_Chatroomusers {

	public function up( ) {
        $date = new DateTime( );
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => '1',
            'user_id'     => '1',
            'token'       => Hash::make("zzzzasdasd"),
            'created_at'  => $date,
            'updated_at'  => $date
        ));
	}
	
	public function down( ) {
    	DB::table('chatroom_user')->where('token','=',Hash::make("zzzzasdasd"))->delete( );
	}

}