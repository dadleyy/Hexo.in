<?php

class Create_Tutorial_Game {

    public function up( ) { 
        $info = array( 
            "email" => 'tester',
            "user"  => 'tutorial player',
            "pass"  => 'tester_password123'
        );
        
        $tester_a = new User( );
        $tester_a->email = $info['email'];
        $tester_a->password = Hash::make( $info['pass'] );
        $tester_a->username = $info['user'];
        $tester_a->save( );
        
        $tester_b = new User( );
        $tester_b->email = $info['email'];
        $tester_b->password = Hash::make( $info['pass'] );
        $tester_b->username = $info['user'];
        $tester_b->save( );
        
        $game = Game::open( $tester_a, $tester_b->id );
        $game->addUser( $tester_b );
        $game->is_tutorial = true;
        $game->save( );
    }
    
    public function down( ) {  
    
    }

}