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
        
        /* check for lat/long updates */
        if( Input::get("lat") !== null && Input::get("lng") !== null ){
            $lat = floatval( Input::get("lat") );
            $lon = floatval( Input::get("lng") );
            
            $a_user = Auth::user( );
            $a_user->latitude = $lat;
            $a_user->longitude = $lon;
            $a_user->save( );
            return Response::make( json_encode($output), 200, $headers );
        }
        
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
        
        $users = User::where("latitude","!=","0")->get( );
        $clean = array( );
        foreach( $users as $usr ) {
            $clean[] = $usr->original;
        }
        
        return json_encode( $clean );
            
    }
        
}