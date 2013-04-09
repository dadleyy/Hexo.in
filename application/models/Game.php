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
    
    /* Game->visitor 
     * returns the visitor of the game
     * @return {User} the visitor of the game
    */
    public function visitor( ){ return User::find( $this->visitor_id ); }
    
    /* Game->challenger 
     * returns the challenger of the game
     * @return {User} the challenger of the game
    */
    public function challenger( ){ return User::find( $this->challenger_id ); }
    
    /* Game->chatroom 
     * returns the chatroom of the game
     * @return {Chatroom} the chatroom of the game
    */
    public function chatroom( ){ return Chatroom::where( "game_id", "=", $this->id )->first( ); }
    
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
    
    /* Game->checkTurn
     * Checks whether the user is able to make a move 
     * this turn based on the state of the game
     * @param {User} the user trying to make a move
     * @return {boolean} if the turn matches up
    */
    public function checkTurn( $user ) {
        
        if( $this->visitor() !== null ) {
            if( $this->visitor()->id === $user->id && $this->turn === 1 ) {
                return false;
            }
        }      
        if( $this->challenger()->id === $user->id && $this->turn === 2 ) {
            return false;
        }  

        return true;
    }
    
    /* Game->isOver
     * Checks to see if the game file has been
     * deleted, which would mean the game is over 
     * @return {boolean}
    */
    public function isOver( ) { return !file_exists( $this->gameFile( ) ); }
    
    /* Game->gameFile
     * Returns the location of the game file
     * @return {string} the location of the game file
    */
    public function gameFile( ){ return path('storage') . 'games/' . $this->token . '.json'; }
    
    /* Game->getLastTime
     * @return {timestamp} the timestamp of the latest update
    */
    public function getLastTime( ) { return strtotime( $this->updated_at ); }
    
    /* Game->getFlag 
     * Returns the current flag from the game file
     * @return {string} the game flag
    */
    public function getFlag( ) {
        
        if( !file_exists( $this->gameFile() ) ) {
            return "dead";
        }
        
        $info = json_decode( File::get( $this->gameFile() ), true ); 
        return $info['flag'];
    }
    
    /* Game->updateFlag
     * Updates the game file with a new random flag to 
     * trigger any needed socket updates
    */
    public function updateFlag( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );    
        $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        File::put( $this->gameFile(), json_encode( $info ) );
    }
    
    /* Game->publicJSON
     * Returns a json encoded array of
     * information that is usable on the client
     * side for communication
     * return {string} json ecoded array
    */
    public function publicJSON( ){
    
        $info  = json_decode( File::get( $this->gameFile() ), true );
        $public = array( );

        /* save the information into the public array */
        $public['state'] = $info['state'];
        $public['tiles'] = $info['tiles'];
        $public['flag'] = $info['flag'];
        $public['turn'] = intval( $info['turn'] );
        
        $public['token'] = $this->encodeToken( $this->token );
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
    
    /* Game->getTiles 
     * Returns a php array of the tiles that
     * are in the game file
     * @return {array} the tiles
    */
    public function getTiles( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        return $info['tiles'];
    }
    
    /* Game->moveTile
     * Changes the tile with the appropriate
     * value to a specific state
     * @param {int} the target tile value
     * @param {int} the new tile state
    */
    public function moveTile( $tile_value, $tile_state ) {
    
        $info = json_decode( File::get( $this->gameFile() ), true );
        $tiles = $info['tiles'];
        
        /* set the tile state */
        $indx = -1;
        foreach( $tiles as $key=>$tile ) {
            $indx = $key;
            if( (int)$tile['value'] === (int)$tile_value ){
                break;
            }
        }
        
        if( !isset( $tiles[$indx] ) || $tiles[$indx]['state'] !== 0 ) {
            return false;    
        }
        
        /* set the state - put it back in - save it */
        $info['tiles'][$indx]['state'] = $tile_state;
        
        $result = array( );
        $result['key'] = $indx;
        $result['state'] = $info['tiles'][$indx]['state'];
        
        /* switch the turn */
        if( $info['turn'] == 1 ){
            $this->turn = 2;
            $info['turn'] = 2;
        } else {
            $this->turn = 1;
            $info['turn'] = 1;
        }
        
        File::put( $this->gameFile(), json_encode( $info ) );
        
        $this->touch( );
        $this->save( );
        return $result;
    }
    
    /* Game->createJSON
     * Sets up the json file for future use
    */
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
            "flag"  => rand( 0, 100000 ),
            "tiles" => $tiles, 
            "turn"  => 1,
            "challenger" => $this->challenger_id 
        );
        
        File::put( $file_location, json_encode( $file_contents ) );
    }
    
}

?>