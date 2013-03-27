<?php

class Chat_Controller extends Base_Controller {

    public $restful = true;
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function post_socket( ) {
    
        $output = array( "csrf" => true, "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) || !Auth::check( ) ){
            return json_encode( $output );
        }
    
        /* save all the input stuff */
        $extras     = ( is_array( Input::get("extras") ) ) ? Input::get("extras") : array( );
        $chat_token = ( isset( $extras['chat_token'] ) ) ? $extras['chat_token']  : "";
        $user_token = ( isset( $extras['user_token'] ) ) ? $extras['user_token']  : "";
                
        if( $chat_token == null || $user_token == null ){
            return Response::make( json_encode($output), 202, $headers );
        }
    
        /* try getting a chat room from the chat_token */
        $decoded_chat = Tokened::decodeToken( $chat_token );
        $chat_obj     = Chatroom::where( "token" , "=" , $decoded_chat )->first( );
        
        if( $chat_obj == null ){ 
            return Response::make( json_encode($output), 202, $headers );   
        }
    
        /* someone is logged in - get their model */
        $active_user = Auth::user( );    
        $real_user_token    = $active_user->chattoken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            return Response::make( json_encode($output), 202, $headers ); 
        }
        
        /* get the flag as it is now */
        $original_flag = $chat_obj->getFlag( );
    
        $changed = false;
        $loops = 0;
        
        /* socket timeout variables */
        $s_time = time( );
        $c_time = time( );
        
        while ( !$changed ) { 
                    
            $c_time = time( );
            /* time out - the loop has been going on for wayy too long */
            if( ($c_time - $s_time) > 20 || $loops > 10000 ){
                
                $output = array(
                    "success" => true,
                    "code" => 2,
                    "timeout" => $loops,
                    "type" => "chat [2]",
                    "rest" => true
                );
                
                return Response::make( json_encode($output), 202, $headers );
            }
            
            if( $chat_obj == null ) {
            
                $output = array(
                    "success" => false,
                    "code" => 4,
                    "timeout" => $loops,
                    "type" => "chat [4]",
                    "rest" => true
                );
                
                return Response::make( json_encode($output), 202, $headers );
            }
            
            if( $chat_obj->getFlag( ) !== $original_flag ) {
                
                $package = $chat_obj->mostRecentMessages( );
                $output = array( 
                    "success" => true,
                    "code" => 1,
                    "type" => "chat [1]",
                    "timeout" => $loops,
                    "old_flag" => $original_flag,
                    "new_flag" => $chat_obj->getFlag( ),
                    "package" => $package,
                    "request" => Request::forged( )
                );
                
                return Response::make( json_encode($output), 200, $headers );
                
            }
            
            
            $loops++;
            time_nanosleep( 0, 900000 );
        
        }
        
        $output = array(
            "success" => true,
            "code" => 2,
            "type" => "chat",
            "timeout" => $loops
        );
        
        return Response::make( json_encode($output), 202, array() );
        
    }
    
    public function post_send( ){
        $output = array( "csrf" => true, "success" => false );
        $headers = array( 'Content-type' => 'application/json' );
        
        if( Request::forged( ) || !Auth::check( ) ){
            return Response::make( json_encode($output), 202, $headers );   
        }
        
        /* save all the input stuff */
        $chat_token = Input::get("chat_token");
        $user_token = Input::get("user_token");
        $message    = Input::get("msg");
        
        if( $chat_token == null || $user_token == null || $message == null ){
            return Response::make( json_encode($output), 202, $headers );
        }        
        
        /* try getting a chat room from the chat_token */
        $decoded_chat = Tokened::decodeToken( $chat_token );
        $chat_obj     = Chatroom::where( "token" , "=" , $decoded_chat )->first( );
        
        if( $chat_obj == null ){ 
            return Response::make( json_encode($output), 202, $headers );   
        }
        
        /* someone is logged in - get their model */
        $active_user = Auth::user( );    
        $real_user_token    = $active_user->chattoken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            return Response::make( json_encode($output), 202, $headers ); 
        }
        
        $chat_contents = File::get( $chat_obj->chatfile( ) );
        $chat_info = json_decode( $chat_contents , true );
        
        $chat_info['messages'][] = array( 
            "user" => $active_user->username,
            "message" => HTML::entities( $message ) 
        );
        
        File::put( $chat_obj->chatfile( ), json_encode($chat_info) );
        $chat_obj->updateFlag( );
        
        $output = array(
            "success" => true,
            "code" => 1
        );
        
        return Response::make( json_encode($output), 200, $headers );
    }

}