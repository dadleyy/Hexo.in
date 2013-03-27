<?php

class Make_Friends_Table {

    public function up( ){
        
        Schema::create('friends', function($table) {
            $table->increments('id');                
            $table->integer('friender');
            $table->integer('friendee');
            $table->timestamps();
        });
        
    }
    
    public function down( ){
        Schema::drop('friends');
    }

}