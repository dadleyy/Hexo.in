<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    <link rel="stylesheet" type="text/css" href="/css/blog.css">
    
</head>
<body>
    
    @include('layouts.header')
    
    <section class="content whole cf">
    <section class="main-content spw middle cf single">
        
        <article class="top">
            <h3>{{ $post->posted() }}</h3>
            <h1><em>{{ $post->title }}</em></h1>
            <h3>posted by <em>{{ $post->author()->username }}</em></h3>
        </article>
        
        <article class="post-content">
            <p>{{ nl2br( $post->content ) }}</p>
        </article>
        
    </section>
    </section>
    
    @render('layouts.footer')
    
    @if ( Auth::check() )
    @render( "layouts.pulldowns" )
    @endif    
   
    @render('globals.scripts')
    
</body>
</html>