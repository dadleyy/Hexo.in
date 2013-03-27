<?php

class Create_Blog_Table {

    public function up( ){
        
        Schema::create('blogpost', function($table) {
            $table->increments('id');                
            
            $table->string("title");
            $table->text("content");
            $table->integer("user_id");
            
            $table->timestamps();
        });
        
        $date = new DateTime( );
        
        DB::table('blogpost')->insert( array(
            'title'      => "The birth of Hexo.in",
            'content'    => "School assignments aren't always viewed by students as an opportunity to actually make something they might keep after the course is over. Usually, we're just trying to get a 3.5 GPA and call it a day, ending up with something we can just throw away and forget. For my course in web client-server technology though, I decided I actually wanted to invest in the thing I was making and see if I could make something that was worthwhile."."\r\n \r\n"."The project needed to be a turn based web application game, and we were able to pick whatever frameworks/libraries we wanted to use on the client and server. Fortunately, I have been messing around on and off with the php MVC framework, Laravel alot lately, which has made the majority of this project a breeze. My favorite feature is the ability to define the application's url map, with \"classes\" introduced in php 5. The classes are the controllers of the application, and handle things like user input and any filtering needed to be applied to requests. Also, database communication is an absolute pleasure with the \"Eloquent\" model-table relationships that it allows the programmer to set up.",
            'user_id'    => 1,
            'created_at' => $date,
            'updated_at' => $date
        ));
        
    }
    
    public function down( ){
        Schema::drop('blogpost');
    }

}