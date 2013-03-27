<?php

class Make_Chatroom_Table {

    public function up( ) {
        Schema::create('chatrooms', function($table) {
            $table->increments('id');
            
            $table->string('token');
            $table->integer('game_id')->default(0);
            
            $table->string('name');
            $table->unique('name');
            
            $table->timestamps();
        });
    }

	public function down( ) {  
    	Schema::drop('chatrooms');
    	exec( "rm -fr " . path('storage') . 'chats/*' );
	}

}

?>