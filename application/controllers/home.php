<?php

class Home_Controller extends Base_Controller {
    
    public $layout = 'layouts.common';
    
    public function __construct(){
        $this->filter('before', 'auth');
    }
    
    public function action_index( ) {
        $view = View::make('home.index')->with('title', 'home');
        Chatroom::find(1)->addUser( Auth::user() );
        return $view;
    }
    
    public function action_account( ) {
        $view = View::make('home.account')->with('title', 'account');
        return $view;
    }
    
    public function action_debug( ){
        if( !Auth::check() || Auth::user()->privileges < 1 )
            return "psych";
        
        Auth::user()->addFriend( User::find(2) );
        
        $out = array( );
        foreach( User::all() as $user ) {
            $info = json_decode( $user->publicJSON( ), true );
            $info['friends'] = $user->friends( );
            $out[] = $info;
        }
        
        return json_encode( $out );
    }
       
}