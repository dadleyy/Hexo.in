<?php

class Home_Controller extends Base_Controller {
    
    public $layout = 'layouts.common';
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function action_index( ) {
        $view = View::make('layouts.common')
                    ->nest("content", "home.index")
                    ->with("title", "home");
        return $view;
    }
    
    public function action_heartbeat( ) {
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => false, "code" => 4, "type"=>"heartbeat" );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $output['success'] = true;
        $output['code'] = 2;
        
        /* ************************************************** *
         * SOCKET STATE - raw update required                 *
         * The client has specifically asked for an update    *
         * ************************************************** */
        if( Input::get("raw") && Input::get("raw") == "raw" ) {  
            $output['code'] = 1;
            $output['package'] = $current_user->getUpdates( );
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $s_online = count( User::online( ) );
                
        $s_time = time( );
        $c_time = time( );
        
        $loops   = 0;
        $changed = false;
        
        $current_user = Auth::user( );    
        $s_updates = count( $current_user->getUpdates( ) );
                
        $s_time = time( );
        $c_time = time( );
        
        $loops   = 0;
        $changed = false;
        
        while( !$changed ) {
            
            $c_time = time( );
            
             /* ************************************************** *
             * SOCKET STATE - socket timeout                      *
             * This socket loop has been going on for too long,   *
             * it is time to let the client know to make a new rq *
             * ************************************************** */
            if( $c_time - $s_time > 10 || $loops > 1000 ) {
                return Response::make( json_encode($output), 200, $headers );
            }
                    
            /* ************************************************** *
             * SOCKET STATE - updated needed                      *
             * There has been a change since the last time the    *
             * socket hit this iteration, send the update to the  *
             * client.                                            *
             * ************************************************** */
             if( count( $current_user->getUpdates( ) ) !== $s_updates ){
                 $output['code'] = 1;
                 $output['package'] = $current_user->getUpdates( );
                 return Response::make( json_encode($output), 200, $headers );
             }
                    
            $loops++;
            usleep( 500000 );
        }
           
    }
    
    public function action_debug( ){
        
        if( (int)Auth::user()->privileges !== 1 ){
            return Redirect::to( '/home' );
        }
        
        $output = array();
        
        $output['users'] = array();
        $output['games'] = array();
        
        foreach( Game::all() as $game ) {
            $output['games'][] = json_decode( $game->publicJSON( ), true );
        }
        
        foreach( User::all() as $user ) {
            
            $u_array = array();
            $u_array['username'] = $user->username;
            $u_array['notes'] = array();
            foreach( $user->notifications()->get() as $note ) {
                $u_array['notes'][] = json_decode( $note->publicJSON(), true );
            }
            
            $output['users'][] = $u_array;
        }

        return json_encode( $output );
            
    }
        
}