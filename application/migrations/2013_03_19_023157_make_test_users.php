<?php

class Make_Test_Users {
    
    public function up( ){
        $date = new DateTime( );
        
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;  
        DB::table('users')->insert( array(
            'username'     => "Dadleyy",
            'email'		   => "danny@dadleyy.com",
            'password'     => Hash::make('password'),
            'privileges'   => 1,
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;
        DB::table('users')->insert( array(
            'username'     => "Admin",
            'email'		   => "info@hexo.in",
            'password'     => Hash::make('password'),
            'privileges'   => 1,
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        
        /*
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;
        DB::table('users')->insert( array(
            'username'     => "Teddy",
            'email'		   => "b@dadleyy.com",
            'password'     => Hash::make('password'),
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;
        DB::table('users')->insert( array(
            'username'     => "Frank",
            'email'		   => "c@dadleyy.com",
            'password'     => Hash::make('password'),
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;
        DB::table('users')->insert( array(
            'username'     => "George",
            'email'		   => "d@dadleyy.com",
            'password'     => Hash::make('password'),
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        
        $wins   = 0;
        $losses = 0;
        $games = $wins + $losses;
        DB::table('users')->insert( array(
            'username'     => "Tester",
            'email'		   => "test@test.com",
            'password'     => Hash::make('testpass'),
            'created_at'   => $date,
            'updated_at'   => $date,
            'last_update'  => $date,
            'wins'         => $wins,
            'losses'       => $losses,
            'games'        => $games
        ));
        */
        
    }

	public function down( ){
		DB::table('users')->where('id','=',"1")->delete( );
		DB::table('users')->where('id','=',"2")->delete( );
		/*
		DB::table('users')->where('id','=',"3")->delete( );
		DB::table('users')->where('id','=',"4")->delete( );
		DB::table('users')->where('id','=',"5")->delete( );
		DB::table('users')->where('id','=',"6")->delete( );
		*/
	}

}