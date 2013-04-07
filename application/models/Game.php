<?php

class Game extends Tokened {

    public static $table = 'games';
    public static $timestamps = true;
    
    /* Game::getOpen
     * returns a game if there is any open, or returns false
     * if none are open
    */
    public static function getOpen( ) {
        $open = Game::where("visitor_id", "=", 0)->where("is_private","!=",true)->take(1)->first( );
        return ( $open === NULL ) ? false : $open;
    }
    
    /* Game::open
     * creates a new game with the challenger and target 
     * passed in as args 
     * @param {User} $user the user that is opening up the game
     * @param {int} $target the user that was challenged
     */
    public static function open( $user, $target = 0 ){
        
        //////////////////////////////
        // GAME INITIALIZATION      //
        $game = new Game;
        
        $game->visitor_id = 0; 
        $game->challenger_id = $user->id;
        
        if( $target !== 0 ) {
            $game->is_private = true;
            $game->target_id = $target;
        }
        
        $game->token = sha1( time() );
        
        $game->createJSON( );
        $game->save( );
        
        //////////////////////////////
        // CHATROOM INITIALIZATION  //
        $chat = new Chatroom;
        
        $chat->game_id = $game->id;
        $chat->token = sha1( $game->token );
        $chat->name = "Game #" . $game->id;
        
        $chat->createJSON( );
        $chat->save( );
        
        /* add the current user to that chatroom */
        $date = new DateTime( );
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => $chat->id,
            'user_id'     => $user->id,
            'token'       => sha1( $chat->id . $user->id ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));
        
        return $game;
    }
    
    /* game->addUser
     * Adds the user to the game
     * @param {User} the user to be added
    */
    public function addUser( $user ) {
                    
        $this->visitor_id = $user->id;
            
        /* set the json for this game */
        $file_location = $this->gameFile( );
        $file_contents = File::get( $file_location );
        $game_info = json_decode( $file_contents, true );
        
        /* add this user into that game and set state to 1 (playing) */
        $game_info['visitor_id'] = $user->id;
        $game_info['state'] = 1;
        
        /* save the json */
        File::put( $file_location, json_encode( $game_info ) );
                
        /* put this user into the chatroom */
        $chat = Chatroom::where("game_id", "=", $this->id )->first( );        
        $chat->addUser( $user );
        
        /* save the game and update the flag (notifies the waiting challenger) */
        $this->save( );
        $this->updateFlag( );
        
        return true;
    }
    
    public function visitor( ){ 
        return User::find( $this->visitor_id ); 
    }
    
    public function challenger( ){ 
        return User::find( $this->challenger_id ); 
    }
    
    public function isOver( ) { 
        return !file_exists( $this->gameFile( ) ); 
    }
    
    public function gameFile( ){
        return path('storage') . 'games/' . $this->token . '.json';
    }
    
    public function getLastTime( ) {
        return strtotime( $this->updated_at );
    }
    
    public function getFlag( ) {
        
        if( !file_exists( $this->gameFile() ) ) {
            return "dead";
        }
        
        $info = json_decode( File::get( $this->gameFile() ), true ); 
        return $info['flag'];
    }
    
    public function updateFlag( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );    
        $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        File::put( $this->gameFile(), json_encode( $info ) );
    }
    
    public function publicJSON( ){
    
        $info  = json_decode( File::get( $this->gameFile() ), true );
        $token = $info['token'];
        $public = array( );

        /* save the information into the public array */
        $public['token'] = $this->encodeToken( $token );
    
        $public['state'] = $info['state'];
        $public['turn'] = $info['turn'];
        $public['tiles'] = $info['tiles'];
        $public['flag'] = $info['flag'];
        $public['is_private'] = $this->is_private;
        
        /* output the two users */
        $public['challenger'] = json_decode( $this->challenger()->publicJSON( ), true );
        $public['visitor'] = ( $this->visitor() !== null ) 
                                    ? json_decode( $this->visitor()->publicJSON( ), true )
                                    : false;
        
        if( $this->chatroom( ) !== null ){
            $public['chatroom'] = json_decode( $this->chatroom( )->publicJSON( ), true );
        }
        
        return json_encode( $public );
    }
    
    public function createJSON( ){
    
        /* create the file that will hold the game states */
        $file_location = $this->gameFile( );
        
        /* array of vals for the tiles */
        $tile_values = array( 0, 12, 32, 23, 25, 26, 17, 31, 
                              5, 15, 13, 20, 18, 22, 19, 28, 
                              8, 24, 1, 16, 6, 11, 35, 4, 7, 
                              3, 21, 27, 9, 10, 14, 36, 29, 
                              30, 33, 34, 2, 37 );
        
        /* initialize the tiles array */
        $tiles = array( );
        for( $i = 0; $i < 37; $i++ ){
            if( $i == 18 ) { continue; }
            $value = ( $i > 18 ) ? $i : $i + 1;
            $tiles[ $i ] = array( "id" => $i, "state" => 0, "value" => $tile_values[$value] );
        }
        
        $file_contents = array( 
            "state" => 0, 
            "turn"  => 1,
            "flag"  => rand( 0, 100000 ),
            "tiles" => $tiles, 
            "token" => $this->token, 
            "challenger" => $this->challenger_id 
        );
        
        File::put( $file_location, json_encode( $file_contents ) );
    }

    public function chatroom( ){
        return Chatroom::where( "game_id", "=", $this->id )->first( );
    }
    
}

?>