<?php

class Game_Controller extends Base_Controller {
    
    public $layout = 'layouts.common';
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function action_index( ) {
        return Response::error("404"); 
    }
    
    public function action_play( ){
                
        $c_user  = Auth::User( );
        $rq_game = $c_user->game( );
        
        /* make sure there is a game */
        if( $rq_game == false ){
            return Redirect::to( '/home' );
        }
        
        /* make sure our user is in the game */
        if( $rq_game->visitor_id !== $c_user->id && $rq_game->challenger_id !== $c_user->id ){
            return Redirect::to( '/home' );
        }
        
        /* prep the json that gets dumped */
        $game_public = $rq_game->publicJSON( );
        $rq_chat = $rq_game->chatroom( );
                
        $view = View::make('games.main')
                    ->with("title", "play" )
                    ->with("game_js", $game_public )
                    ->with("game", $rq_game )
                    ->with("chat", $rq_chat );
                    
        return $view;
    }
    
    public function action_debug( ){
        
        /* the location of the file */
        $location = path('storage') . '/testington.json';
        $contents = json_encode( array('hello'=>'goodye') );
        
        /* store contents */
        File::put( $location, $contents );
        
        return json_encode( File::get( $location ) );
    }
    
    public function action_quit( ){
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
    
        if( $current_game !== false ) {
            
            $current_chat = $current_game->chatroom( );
            
            File::delete( $current_game->gamefile( ) );
            File::delete( $current_chat->chatfile( ) );
            DB::table('chatroom_user')->where( 'chatroom_id', '=', $current_chat->id )->delete( );
            
            $current_game->delete( );
            $current_chat->delete( );
        }
        
        return Redirect::to( '/home' );
    }
    
    public function action_move( ) {
        
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        
        if( $current_game == null ){ 
            return json_encode( array( "success" => false ) );
        }   

        $current_game->updateFlag( );                
        return json_encode( array( "success" => true ) );
    }
    
    public function action_socket( ) {
                
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        $output = array( "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }
                
        if( $current_game == null || !Input::get("token") ){ 
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }   
        
        $game_token = $current_game->token; 
        $param_token = Input::get("token");
        $decoded_param = $current_game->decodeTokenArray( $param_token );
        
        if( $game_token !== $decoded_param ) {
            return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 204, $headers );
        }
        
        if( Input::get("raw") && Input::get("raw") == "raw" ) {
        
            $package = json_decode( $current_game->publicJSON( ), true );        
            $output = array( 
                "success" => true,
                "code" => 1,
                "timeout" => false,
                "old_flag" => false,
                "new_flag" => false,
                "request" => Request::forged( ),
                "package" => $package
            );
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $changed = false;
        $loops   = 0;
        $s_time  = time( );
        $c_time  = time( );
        
        $original_flag = $current_game->getFlag( );
        
        while ( !$changed ) {
            $c_time = time( );
            /* time out - the loop has been going on for wayy too long */
            if( ($c_time - $s_time) >  20 || $loops > 10000 ){
                
                $output = array(
                    "success" => true,
                    "code" => 2,
                    "timeout" => $loops,
                    "type" => "game"
                );
                
                return Response::make( json_encode($output), 202, $headers );
            }
            
            if( $current_game == null ) {
            
                $output = array(
                    "success" => false,
                    "code" => 4,
                    "timeout" => $loops,
                    "type" => "chat",
                    "rest" => true
                );
                
                return Response::make( json_encode($output), 202, $headers );
            }
            
            if( $current_game->getFlag( ) !== $original_flag ){
                
                $package = json_decode( $current_game->publicJSON( ), true );
                
                $output = array( 
                    "success" => true,
                    "code" => 1,
                    "timeout" => $loops,
                    "old_flag" => $original_flag,
                    "new_flag" => $current_game->getFlag( ),
                    "package" => $package,
                    "request" => Request::forged( )
                );
                
                return Response::make( json_encode($output), 200, array() );
            }
            
            time_nanosleep( 0, 900000 );
            $loops++;
        }
        
        
        return Response::make( json_encode( array("success"=>false,"csrf"=>true) ), 404, array() );
    }
    
    public function action_start( ){
        
        /* try to find the game the current user is playing (if any) */
        $current_user = Auth::user( );
        $current_game = $current_user->game( );
        if( $current_game !== false ) {
            return Redirect::to( '/game/play' );
        }
        
        /* At this point, there is no current game.
         * The next step is to either join or make a
         * brand new one
        */
        
        /* try to find an open game */
        $open = Game::where("visitor_id", "=", 0)->take(1)->first();
        if( $open !== NULL ){
            
            $open->visitor_id = $current_user->id;
            
            /* set the json for this game */
            $file_location = $open->gamefile( );
            $file_contents = File::get( $file_location );
            $game_info = json_decode( $file_contents, true );
            $game_info['visitor_id'] = $current_user->id;
            $game_info['state'] = 1;
            File::put( $file_location, json_encode( $game_info ) );
            
            $open->save( );
        
            $open->updateFlag( );
            
            
            /* put this user into the chatroom */
            $chat = Chatroom::where("game_id", "=", $open->id )->first( );        
            $date = new DateTime( );
            DB::table('chatroom_user')->insert( array(
                'chatroom_id' => $chat->id,
                'user_id'     => $current_user->id,
                'token'       => sha1( $chat->id . $current_user->id ),
                'created_at'  => $date,
                'updated_at'  => $date
            ));
            
            return Redirect::to( '/game/play' );
        } 
        
        //////////////////////////////
        // GAME INITIALIZATION      //
        $game = new Game;
        
        $game->visitor_id    = 0; 
        $game->challenger_id = $current_user->id;
        $game->token         = sha1( time() );
        
        /* create the file that will hold the game states */
        $file_location = $game->gamefile( );
        
        /* initialize the tiles array */
        $tiles = array();
        for( $i = 0; $i < 18; $i++ ){
            $tiles[$i.""] = array( "id" => $i, "state" => 0 );
        }
        
        $file_contents = array( 
            "state" => 0, 
            "flag"  => rand( 0, 100000 ),
            "tiles" => $tiles, 
            "token" => $game->token, 
            "challenger" => $current_user->id 
        );
        
        File::put( $file_location, json_encode( $file_contents ) );
        
        $game->save( );
        
        
        
        //////////////////////////////
        // CHATROOM INITIALIZATION  //
        
        /* make the chatroom for this */
        $chat = new Chatroom;
        
        $chat->game_id = $game->id;
        $chat->token = sha1( $game->token );
        $chat->name = "Game #" . $game->id;
        
        $chat->save( );
        
        $chat_location = $chat->chatfile( );
        $chat_contents = array(
            "messages" => array( ),
            "token" => $chat->token,
            "flag"  => rand( 0, 100000 )
        );
        File::put( $chat_location, json_encode( $chat_contents ) );
        
        /* add the current user to that chatroom */
        $date = new DateTime( );
        DB::table('chatroom_user')->insert( array(
            'chatroom_id' => $chat->id,
            'user_id'     => $current_user->id,
            'token'       => sha1( $chat->id . $current_user->id ),
            'created_at'  => $date,
            'updated_at'  => $date
        ));
        
        return Redirect::to( '/game/play' );
        
    }
    
}