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
            'content'    => "School assignments aren't always viewed by students as an opportunity to actually make something they might keep after the course is over. Usually, we're just trying to get a 3.5 GPA and call it a day, ending up with something we can just throw away and forget. For my course in web client-server technology though, I decided I actually wanted to invest in the thing I was making and see if I could make something that was worthwhile."."\r\n \r\n"."The project needed to be a turn based web application game, and we were able to pick whatever frameworks/libraries we wanted to use on the client and server. Fortunately, I have been messing around on and off with the php MVC framework, Laravel alot lately, which has made the majority of this project a breeze. My favorite feature is the ability to define the application's url map by creating controllers with the  \"class\" feature introduced in php 5. These controllers handle things like user input, redirection, and any filtering needed to be applied to requests. Instead of having about 15 different files (ie: about.php, contact.php, blog.php) and and ugly template file, you're only making one php file, with the name of the url pointing to a view. This makes Laravel feel like a nice cozy php glove for developers who have worked with Google app engine, Java or ruby. On top of that, Laravel also makes database communication is an absolute pleasure with its \"Eloquent\" model-table relationships that allows the programmer to set up php classes that directly interface with a database table. The tables themselves can be set up and managed extremely efficiently with the \"artisan\" command line tool that the framework provides. Basically, the tool wraps the functionality of the framework inside a php shell and allows you to execute commands, run tasks, and install extensions. Personally, I haven't been able to do much with the extensions that I've seen on the project homepage, but I am sure they are awesome. All things considered, Laravel was a no-brainer for this project."."\r\n \r\n"."The hardest part about this project was actually deciding what to do. I've played my fair share of turn based games, but I didn't really feel like making something that was already floating around out there. Whatever ended up coming out of this project, I wanted it to be unique. My initial thoughts were just to make a roulette game, which I desperately tried to convince myself could end up being a learning tool to build up some practice before winning millions at a casino. Making an exact replica of roulette wasn't really appealing to me though, so I decided to turn the betting squares into betting hexagons. Now we're cooking. As the idea started to bounce around my head, I realized that an online roulette game would probably only end up being as depressing as the real word version, so I decided to scrap that idea. I did decided on keeping one element of it though; the concept of having hexagons of the game with numbers. That pretty much put me back at square one. Since I wanted to get started though, I figured I would just start coding and see what happened. After a few days, I ended up with a solid hexagonal game board. At this point, its safe to say I was pretty much stumped, so I decided to turn to my friends for some inspiration. My roommate suggested something like dice wars; a game that plays out like Risk. Okay, now we're moving. Next, one of my friends suggested something like the dart game \"501\". Between the two of them, I figured I'd give it a go. Thus giving birth to hexo.in.",
            'user_id'    => 1,
            'created_at' => $date,
            'updated_at' => $date
        ));
        
    }
    
    public function down( ){
        Schema::drop('blogpost');
    }

}