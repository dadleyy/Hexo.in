<?php

Route::controller( Controller::detect( ) );

/* about page */
Route::any('/about', function( ) {
    $view = View::make('layouts.common')
                    ->nest("content", "home.about")
                    ->with("title", "about");
    return $view;
});

/* tutorial page */
Route::any('/tutorial', function( ) {
    $view = View::make('layouts.common')
                    ->nest("content", "home.tutorial")
                    ->with("title", "tutorial");
    return $view;
});

/* upgrade page */
Route::any('/upgrade', function( ) {
    $view = View::make('home.upgrade')
                ->with("title","upgrade");
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
