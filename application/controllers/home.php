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
        
        Chatroom::find(1)->addUser( Auth::user() );
        return $view;
    }
       
}