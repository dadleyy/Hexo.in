<?php

class Session_Controller extends Base_Controller {
    
    public $layout = 'layouts.common';
        
        
    public function action_index( ) {
        return Response::error("404");   
    }
    
    public function action_create( ) {
        return $this->layout
                    ->nest("content", "sessions.login")
                    ->with("title", "login");
    }
    
    public function action_end( ) {
    
        if( Auth::user() !== null ) {
            $date = new DateTime('1970-01-01');
            Auth::user()->last_update = $date;
            Auth::user()->save( );
            Chatroom::find(1)->removeUser( Auth::user() );
        }
    
        Auth::logout();
        return Redirect::to( '/session/create' );
    }
        
    public function action_registrar( ) { 
        return $this->layout
                    ->nest("content", "sessions.registrar")
                    ->with("title", "register");
    }
        
    public function action_attempt( ) { 
        
        $email = Input::get('email');
        $passw = Input::get('passw');
        
        if ( Request::forged( ) || !$email || !$passw ) { 
            return Response::error('404');
        }
        
        if ( Auth::attempt( array( "username"=>$email, "password"=>$passw ) ) ) {
            return Redirect::to( '/home' );
        } else {
            return Redirect::to( '/session/create' )
                        ->with('errors', 'Please try again' );
        }
        
    }
    
    public function action_register( ) {
        
        if ( Request::forged( ) ) { 
            return Response::error('404');
        }
        
        $info = array( 
            "email" => Input::get('email'),
            "user"  => Input::get('user'),
            "pass"  => Input::get('pass'),
            "passc" => Input::get('passc')
        );
        
        if( $info['passc'] !== $info['pass'] ) { 
            return Redirect::to( '/session/registrar' ) 
                    ->with('errors', 'The passwords you entered did not match' );
        }     
        
        foreach( $info as $n=>$v ) {
            if( !$v || !isset($v) || $v === "" ){ 
                return Redirect::to( '/session/registrar' ) 
                        ->with('errors', 'Please provide all information' ); 
            }
            if( $n == "email" && !filter_var( $v, FILTER_VALIDATE_EMAIL ) ){ 
                return Redirect::to( '/session/registrar' )
                        ->with('errors', 'Please re-type your email... a real one' );
            } else {
                $info[$n] = HTML::entities( $v );
            }
        }
        
        if( strlen($info['user']) < 4 || strlen($info['user']) > 20 ) {
            return Redirect::to( '/session/registrar' )
                        ->with('errors', 'Usernames must be between 4 and 20 characters' );
        }
        
        
        if( count ( User::where( 'email', '=', $info['email'] )->get() ) !== 0 ) {
            return Redirect::to( '/session/registrar' )
                        ->with('errors', 'There is already an account with that email' );
        }
        
        if( count ( User::where( 'username', '=', $info['user'] )->get() ) !== 0 ) {
            return Redirect::to( '/session/registrar' )
                        ->with('errors', 'That username is taken' );
        }
        
        $user = new User;
        
        $user->email = $info['email'];
        $user->password = Hash::make( $info['pass'] );
        $user->username = $info['user'];
        
        $user->save();
        
        if ( Auth::attempt( array( "username"=>$info['email'], "password"=>$info['pass'] ) ) ) {
            return Redirect::to( '/home' );
        } else {
            return Redirect::to( '/session/create' )
                        ->with('errors', 'Please try again' );
        }
    } 

}