<?php

class Create_Games_Table {

    public function up( ) {
        Schema::create('games', function($table) {
            $table->increments('id');
            $table->string('token');   
            
            $table->integer('turn')->default(1);
            $table->integer('visitor_id')->default(0);
            $table->integer('challenger_id')->default(0);
                         
            $table->timestamps();
        });
    }

	public function down( ) {  
    	Schema::drop('games');
	}

}