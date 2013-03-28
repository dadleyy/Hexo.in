<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    
    <meta property="og:title" content="{{ $post->title }}" />
    <meta property="og:type" content="blog.post" />
    <meta property="og:url" content="http://hexo.in/blog/post/{{ $post->id }}" />
    
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
        
        <section class="bottom fourth cf">
            <article class="post-content f three">
            <div class="inner cf">
                <p>{{ nl2br( $post->content ) }}</p>
                <div class="back-to-blog">
                    <a class="gr3 cf" href="/blog">back to blog</a>
                </div>
                <div class="social cf">
                    <div class="f facebook t">
                        <span class="icon t"></span>
                        <div class="fb-like" data-send="false" data-layout="button_count" data-width="199" data-show-faces="false" data-font="arial"></div>
                    </div>
                    <div class="f twitter t"> 
                        <span class="icon t"></span>
                        <a href="https://twitter.com/share" class="twitter-share-button" data-via="hexoin">Tweet</a>
                    </div>
                    <div class="f google t">
                        <span class="icon t"></span>
                        <g:plusone size="medium"></g:plusone>
                    </div>
                </div>
            </div>
            </article>
            <article class="other-posts f one">
            <div class="inner cf">
                <h1 class="addt">Other posts</h1>
                <ul class="other-list">
                <?php $posts = Blogpost::where("id","!=",$post->id)->get( ); ?>
                @foreach( $posts as $o_post )
                    <li>
                    <a href="/blog/post/{{ $o_post->id }}" titile="">{{ $o_post->title }}</a>
                    </li>
                @endforeach
                </ul>
            </div>
            </article>
        </section>
        
    </section>
    </section>
    
    @render('layouts.footer')
    
    @if ( Auth::check() )
    @render( "layouts.pulldowns" )
    @endif    
   
    @render('globals.scripts')
    <div id="fb-root"></div>
    <script>
    (function(d, s, id) {
        var js, 
            fjs = d.getElementsByTagName(s)[0];
            if ( d.getElementById(id) ) { return };
  
        js = d.createElement(s); 
        js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=317962614973884";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>
    <script type="text/javascript">
    (function() {
        var po = document.createElement('script'); 
        po.type = 'text/javascript'; 
        po.async = true;
        po.src = 'https://apis.google.com/js/plusone.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(po, s);
    })( );
    </script>
    <script>
    !function(d,s,id){
        var js,
            fjs = d.getElementsByTagName(s)[0];
        if( !d.getElementById( id ) ) {
            js = d.createElement( s );
            js.id = id;
            js.src="//platform.twitter.com/widgets.js";
            fjs.parentNode.insertBefore( js, fjs );
        }
    }( document, "script", "twitter-wjs" );
    </script>
    
</body>
</html>