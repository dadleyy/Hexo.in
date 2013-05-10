<?php

class Game extends Tokened {

    public static $table = 'games';
    public static $timestamps = true;
    
    /* static properties */
    private static $tile_values = array( 0, 12, 32, 23, 25, 26, 17, 31, 5, 15, 13, 20, 18, 22, 19, 28, 
    8, 24, 1, 16, 6, 11, 35, 4, 7, 3, 21, 27, 9, 10, 14, 36, 29, 30, 33, 34, 2, 37 );
    private static $tile_neighbors = array(
        
        12 => array( 32, 17, 26 ),
        32 => array( 12, 17, 31, 23 ),
        23 => array( 25, 5,  31, 32 ),
        25 => array( 15, 5, 23 ),
        
        26 => array( 12, 13, 20, 17 ),
        17 => array( 26, 12, 32, 31, 18, 20 ),
        31 => array( 17, 32, 23, 5,  22, 18 ),
        5  => array( 15, 19, 22, 31, 23, 25 ),
        15 => array( 25, 5,  19, 28 ),
        
        13 => array( 8,  24, 20, 26 ),
        20 => array( 13, 26, 17, 18, 1, 24 ),
        18 => array( 1,  20, 17, 31, 22 ),
        22 => array( 18, 31, 5,  19, 16 ),
        19 => array( 22, 5,  15, 28, 6, 16 ),
        28 => array( 15, 19, 6,  11 ),
        
        8  => array( 13, 24, 35 ),
        24 => array( 8,  13, 20, 1,  4,  35 ),
        1  => array( 24, 20, 18, 7,  4 ),
        16 => array( 22, 19, 6,  21, 3 ),
        6  => array( 16, 19, 28, 11, 27, 21 ),
        11 => array( 28, 6,  27 ),
        
        35 => array( 8,  24, 4,  9 ),
        4  => array( 35, 24, 1,  7,  10, 9 ),
        7  => array( 1,  4,  10, 14, 3 ),
        3  => array( 7,  14, 36, 21, 16 ),
        21 => array( 3,  36, 29, 27, 6, 16 ),
        27 => array( 21, 29, 11, 6 ),
        
        9  => array( 35, 4,  10, 30 ),
        10 => array( 9,  4,  7,  14, 33, 30 ),
        14 => array( 10, 7,  3,  36, 34, 33 ),
        36 => array( 14, 3,  21, 29, 2,  34 ),
        29 => array( 36, 21, 27, 2 ),
        
        30 => array( 9,  10, 33 ),
        33 => array( 30, 10, 14, 34 ),
        34 => array( 33, 14, 36, 2  ),
        2  => array( 34, 36, 29 )
        
    );
    
    /* Game::getOpen
     * returns a game if there is any open, or returns false
     * if none are open
    */
    public static function getOpen( ) {
        $open = Game::where( "visitor_id", "=", 0 )
                    ->where( "is_private", "!=", true )
                    ->where( "complete", "=", false )
                    ->first( );
                    
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
        $game->complete = false;
        
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
    
    /* game->visitor 
     * returns the visitor of the game
     * @returns {User} the visitor of the game
    */
    public function visitor( ){ return User::find( $this->visitor_id ); }
    
    /* game->challenger 
     * Returns the challenger of the game
     * @returns {User} the challenger of the game
    */
    public function challenger( ){ return User::find( $this->challenger_id ); }
    
    /* game->chatroom 
     * Returns the chatroom of the game
     * @returns {Chatroom} the chatroom of the game
    */
    public function chatroom( ){ return Chatroom::where( "game_id", "=", $this->id )->first( ); }
    
    private function getTileState( $tile_value ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        $tiles = $info['tiles'];
        foreach( $tiles as $tile ) {
            if( $tile['value'] === $tile_value ){ 
                return (int)$tile['state'];
            }
        }
        return -1;
    }
    
    /* game->restart
     * clears out the json file and 
     * restarts the game
    */
    public function restart( ) {
        $challenger = $this->challenger( );
        $visitor = $this->visitor( );
        
        $this->complete = false;   
        $this->turn = 1;
        $this->createJSON( );
        $this->save( );
        
        if( $visitor == null )
            return true;
        
        $file_location = $this->gameFile( );
        $file_contents = File::get( $file_location );
        $game_info = json_decode( $file_contents, true );
        
        /* add this user into that game and set state to 1 (playing) */
        $game_info['visitor_id'] = $visitor->id;
        $game_info['state'] = 1;
        
        /* save the json */
        File::put( $file_location, json_encode( $game_info ) );
        return true;
    }
    
    private function setTileState( $tile_value, $tile_state, $flip_turn = false ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        $tiles = $info['tiles'];
        
        $okay  = false;
        $indx  = -1;
        $state = 0;
        
        foreach( $tiles as $key=>$tile ) {
            if( (int)$tile['value'] === (int)$tile_value ){ 
                $info['tiles'][$key]['state'] = (int)$tile_state;
                
                $indx  = $key;
                $okay  = true;
                $state = (int)$tile_state;
            }
        }
        
        if( !$okay ){ return false; }
        
        if( $flip_turn === true ) {
            /* switch the turn */
            if( $info['turn'] == 1 ){
                $this->turn = 2;
                $info['turn'] = 2;
            } else {
                $this->turn = 1;
                $info['turn'] = 1;
            }
        }
        
        File::put( $this->gameFile(), json_encode( $info ) );
        flush( );
        
        return array( "state" => $state, "key" => $indx );
    }
    
    /* game->getScore 
     * Returns an array with the scores of the game
     * @returns [array] 2D associative
    */
    public function getScore( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        $tiles = $info['tiles'];
        
        $scores = array( 'visitor' => 0, 'challenger' => 0 );
        foreach( $tiles as $key=>$tile ) {
            switch( (int)$tile['state'] ) {
                case 1:
                    $scores['challenger'] += (int)$tile['value'];
                    break;
                case 2: 
                    $scores['visitor'] += (int)$tile['value'];
                    break;
                default:
                    break;
            }
        }
        return $scores;
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
    
    /* game->checkTurn
     * Checks whether the user is able to make a move 
     * this turn based on the state of the game
     * @param {User} the user trying to make a move
     * @returns {boolean} if the turn matches up
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
    
    /* game->isDead
     * Checks to see if the game file has been
     * deleted, which would mean the game is dead 
     * @returns {boolean}
    */
    public function isDead( ) { return !file_exists( $this->gameFile( ) ); }
    
    /* game->gameFile
     * Returns the location of the game file
     * @returns {string} the location of the game file
    */
    public function gameFile( ){ return path('storage') . 'games/' . $this->token . '.json'; }
    
    /* game->getLastTime
     * @returns {timestamp} the timestamp of the latest update
    */
    public function getLastTime( ) { return strtotime( $this->updated_at ); }
    
    public function isComplete( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        $tiles = $info['tiles'];
        $complete = true;
    
        if( (int)$info['state'] === 3 ){
            return true;
        }
        
        foreach( $tiles as $tile ) {
            if( (int)$tile['state'] === 0 ){
                $complete = false;
            }
        }
        
        return $complete;
    }
    
    /* game->getFlag 
     * Returns the current flag from the game file
     * @returns {string} the game flag
    */
    public function getFlag( ) {
        
        if( !file_exists( $this->gameFile() ) ) {
            return "dead";
        }
        
        $info = json_decode( File::get( $this->gameFile() ), true ); 
        return $info['flag'];
    }
    
    public function resolve( $winner = null ) { 
        $scores = $this->getScore( );
        if( $this->visitor( ) === null ){ 
            // do nothing  
        } else if( (int)$scores['visitor'] > (int)$scores['challenger'] ){
            $this->visitor( )->addWin( );
            $this->challenger( )->addLoss( );
        } else {
            $this->challenger( )->addWin( );
            $this->visitor( )->addLoss( );
        }
        $this->complete = true;
        $this->save( );
    }
    
    /* game->updateFlag
     * Updates the game file with a new random flag to 
     * trigger any needed socket updates
    */
    public function updateFlag( $p_flag = "", $p_state = -1 ) {
        $info = json_decode( File::get( $this->gameFile() ), true );    
        
        if( $p_flag !== "" && $p_state !== -1 ){
            $info['flag'] = $p_flag;
            $info['state'] = $p_state;
        } else if( $this->isComplete( ) === true && (int)$info['state'] !== 3 ){
            $info['flag'] = "complete";
            $info['state'] = 3;
            $this->resolve( );
        } else {
            $info['flag'] = base_convert( rand( 1000000, 1000000000 ), 10, 36 );
        }
        
        File::put( $this->gameFile(), json_encode( $info ) );
    }
    
    /* game->publicJSON
     * Returns a json encoded array of
     * information that is usable on the client
     * side for communication
     * @returns {string} json ecoded array
    */
    public function publicJSON( ){
    
        $info  = json_decode( File::get( $this->gameFile() ), true );
        $public = array( );

        /* save the information into the public array */
        $public['state'] = $info['state'];
        $public['tiles'] = $info['tiles'];
        $public['flag'] = $info['flag'];
        $public['turn'] = intval( $info['turn'] );
        $public['score'] = $this->getScore( );
        
        $public['token'] = $this->encodeToken( $this->token );
        $public['is_private'] = $this->is_private;
        
        if( $this->is_tutorial )
            $public['no_live'] = true;
        
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
    
    /* game->getTiles 
     * Returns a php array of the tiles that
     * are in the game file
     * @returns {array} the tiles
    */
    public function getTiles( ) {
        $info = json_decode( File::get( $this->gameFile() ), true );
        return $info['tiles'];
    }
    
    /* game->moveTile
     * Changes the tile with the appropriate
     * value to a specific state
     * @param {int} the target tile value
     * @param {int} the new tile state
    */
    public function moveTile( $tile_value, $tile_state, $pop_turn = true ) {
        
        /* set the tile state */
        $status = $this->setTileState( $tile_value, $tile_state, $pop_turn );
        if( !is_array($status) ){ return false; }
    
        /* check the neighbors */
        $target_neighbors = Game::$tile_neighbors[$tile_value];
        $r_neighbors = array();
        $changed = false;
        foreach( $target_neighbors as $n_value ) {
        
            /* only check neighbors that are in jeopardy */
            $n_state = $this->getTileState( $n_value );
            if( $n_state === 0 || $n_state === -1 || (int)$n_state === (int)$tile_state ){ continue; }
            
            /* we have a neighbor that is taken by the opponent */    
            $neighbor_neighbors = Game::$tile_neighbors[$n_value];
            $nn_arr = array( );
            foreach( $neighbor_neighbors as $nn_value ){
                $nn_state = $this->getTileState( $nn_value );
                if( $nn_state !== (int)$tile_state ){ continue; }
                $nn_arr[] = array( "value" => $nn_value, "state" => $nn_state );
            }
            if( count($nn_arr) > 3 ) {
                $this->moveTile( (int)$n_value, (int)$tile_state, false );
            }
            $r_neighbors[$n_value] = $nn_arr;
        }

        $result['key'] = $status['key'];
        $result['state'] = $status['state'];
            
        $this->touch( );
        $this->save( );
        return $result;
    }
    
    /* game->createJSON
     * Sets up the json file for future use
    */
    public function createJSON( ){
    
        /* create the file that will hold the game states */
        $file_location = $this->gameFile( );
        
        /* array of vals for the tiles */
        $tile_values = Game::$tile_values;
        
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