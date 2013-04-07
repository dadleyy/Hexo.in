<?php

class Create_Notification_Table {
    
    public function up( ) {
        Schema::create('notifications', function($table) {
            $table->increments('id');
            
            $table->integer('source_id');
            $table->integer('user_id');
            
            $table->integer('item_id');
            $table->string('type');
            
            $table->timestamps();
        });   
    }
    
    public function down( ) {
        Schema::drop('notifications');
    }
    
}