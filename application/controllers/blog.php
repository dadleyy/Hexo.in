<?php

class Blog_Controller extends Base_Controller {

    public function action_index( ){
        $view = View::make('blog.main')->with('title', 'blog');
        return $view; 
    }     
    
    public function action_post( $postid = 0 ) {
    
        $post = Blogpost::find( $postid );        
        if( $post == null ) {
            return Redirect::to( '/blog' );
        }
    
        $view = View::make('blog.single')
                    ->with("title", $post->title )
                    ->with("post", $post );
                    
        return $view;    
    }
    
    public function action_manage( ) {
        if( !Auth::check( ) || Auth::user( )->privileges != 1 ) {
            return Redirect::to( '/blog' );
        }
        $view = View::make('blog.manage')->with('title', 'blog');
        return $view; 
    }

}