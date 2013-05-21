@if ( Auth::check() )
<!-- chatroom zone -->
<section id="chatroom-pullout"></section>
<!-- end chatroom zone -->
@endif
<header class="whole pw middle cf gr1">
    <article class="middle pw cf">
        <div class="f cf left-menu ribbon-title">
            
            <article class="cf">
            
                <div class="gr2 home-link">
                <div class="inner qt gr3">
                    <a href="/home" title="" class="home">
                        <img src="/img/logo_large.png" alt="">
                    </a>
                </div>
                </div>
                
                @if( Auth::check() )
                @render('partials.chatmenu')
                @endif
                
            </article>
            
        </div>
        
        <div class="f site-links">
            <ul class="links cf">
                <li class="f t <?php if( $title == "about"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/about" title="">About</a>
                </li>
                <li class="f t <?php if( $title == "tutorial"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/tutorial" title="">Tutorial</a>
                </li>
                <li class="f t <?php if( $title == "blog"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/blog" title="">Blog</a>
                </li>
                <li class="f t <?php if( $title == "contact"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/contact" title="">Contact</a>
                </li>
            </ul>
        </div>
        
        <div class="r right cf">

            @if ( Auth::check() )
            @render( 'partials.headermenu' )
            @endif

        </div>
    </article>
</header>