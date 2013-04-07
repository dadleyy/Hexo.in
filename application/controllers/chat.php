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
        $active_user        = Auth::user( );    
        $real_user_token    = $active_user->getChatToken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            return Response::make( json_encode($output), 202, $headers ); 
        }
        
        /* everything is okay beyond this point */
        $active_user->ping( );
        
        /* get the flag as it is now */
        $original_flag = $chat_obj->getFlag( );
    
        $changed = false;
        $loops = 0;
        
        /* socket timeout variables */
        $s_time = time( );
        $c_time = time( );
        
        while ( !$changed ) { 
                    
            $c_time = time( );
            
            /* ************************************************** *
             * SOCKET STATE - chat closed                         *
             * ************************************************** */
            if( $chat_obj == null || $chat_obj->isClosed( ) ) {
                $output = array(
                    "success" => false,
                    "new_flag" => "dead",
                    "type" => "chat",
                    "code" => 4
                );
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - socket timeout                      *
             * This socket loop has been going on for too long,   *
             * it is time to let the client know to make a new rq *
             * ************************************************** */
            if( ($c_time - $s_time) > 10 || $loops > 10000 ){
                $output = array(
                    "success" => true,
                    "type" => "chat",
                    "code" => 2
                );        
                return Response::make( json_encode($output), 200, $headers );
            }
            
            /* ************************************************** *
             * SOCKET STATE - updated needed                      *
             * There has been a change since the last time the    *
             * socket hit this iteration, send the update to the  *
             * client.                                            *
             * ************************************************** */
            if( $chat_obj->getFlag( ) !== $original_flag ) {
                $package = $chat_obj->mostRecentMessages( );
                $output = array( 
                    "success" => true,
                    "code" => 1,
                    "type" => "chat",
                    "new_flag" => $chat_obj->getFlag( ),
                    "package" => $package
                );
                return Response::make( json_encode($output), 200, $headers );
            }
            
            
            $loops++;
            time_nanosleep( 0, 9000000 );
        
        }
        
        $output = array(
            "success" => true,
            "code" => 2
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
        $real_user_token    = $active_user->getChatToken( $chat_obj->id );
        $decoded_user_token = Tokened::decodeToken( $user_token );
        
        if( $real_user_token !== $decoded_user_token ) {
            return Response::make( json_encode($output), 202, $headers ); 
        }
        
        $chat_contents = File::get( $chat_obj->chatFile( ) );
        $chat_info = json_decode( $chat_contents , true );
        
        $chat_info['messages'][] = array( 
            "user" => $active_user->username,
            "message" => HTML::entities( $message ) 
        );
        
        File::put( $chat_obj->chatFile( ), json_encode($chat_info) );
        $chat_obj->updateFlag( );
        
        $output = array(
            "success" => true,
            "code" => 1
        );
        
        return Response::make( json_encode($output), 200, $headers );
    }

    public function post_online( ) {
        $headers = array( 'Content-type' => 'application/json' );
        $output = array( "success" => false, "code" => 4 );
        
        if( Request::forged( ) ) {
            return Response::make( json_encode($output), 200, $headers );
        }
        
        $output['success'] = true;
        $output['code'] = 2;
        
        $s_online = count( User::online( ) );
                
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
             if( count( User::online() ) !== $s_online ){
                 $output['code'] = 1;
                 $output['package'] = User::online( );
                 return Response::make( json_encode($output), 200, $headers );
             }
                    
            $loops++;
            time_nanosleep( 0, 9000000 );
        }

        
        return Response::make( json_encode($output), 200, $headers );
    }

}