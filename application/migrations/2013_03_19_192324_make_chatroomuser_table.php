<?php

class Make_Chatroomuser_Table {

    public function up( ) {
        Schema::create('chatroom_user', function($table) {
            $table->increments('id');
            $table->string('token');
            
            $table->integer('chatroom_id');
            $table->integer('user_id');
                        
            $table->timestamps();
        });
    }

	public function down( ) {  
    	Schema::drop('chatroom_user');
	}

}