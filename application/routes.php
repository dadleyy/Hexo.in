<?php

Route::controller( Controller::detect( ) );

/* about page */
Route::any('/about', function( ) {
    $view = View::make('layouts.common')
                    ->nest('content', 'home.about')
                    ->with('title', 'about');
    return $view;
});

Route::get('sitemap', function(){

    $sitemap = new Sitemap();
    
    // set item's url, date, priority, freq
    $sitemap->add(URL::to(), date('Y-m-d H:i:s'), '1.0', 'daily');
    
    $sitemap->add( URL::to('home'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('session/create'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('session/registrar'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('about'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('tutorial'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('contact'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('blog'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('home'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    $sitemap->add( URL::to('game/play'), date('Y-m-d H:i:s'), '1.0', 'daily' );
    
    foreach( Blogpost::all() as $post ) {
         $sitemap->add( URL::to('blog/post/'.$post->id), date('Y-m-d H:i:s'), '1.0', 'daily' );   
    }
    
    return $sitemap->render('xml');

});

/* upgrade page */
Route::any('/upgrade', function( ) {
    $view = View::make('home.upgrade')
                ->with('title','upgrade');
    return $view;
});

Event::listen('404', function( ) {
	return Response::error('404');
});

Event::listen('500', function() {
	return Response::error('500');
});

Route::filter('before', function( ) { });

Route::filter('after', function($response) { });

Route::filter('csrf', function( ) {
    if ( Request::forged( ) ) { 
        return Response::error('500');
    }
});

Route::filter('auth', function( ) {
    if ( !Auth::check( ) ) {
        return Redirect::to( '/session/create' );   
    }
    Auth::user()->ping( );
});

Route::filter('noneed', function( ) {
    if ( Auth::check( ) ) {
        return Redirect::to( '/home' );   
    }
});
