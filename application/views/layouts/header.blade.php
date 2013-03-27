<header class="whole middle cf gr1">
    <article class="middle pw cf">
        <div class="f cf left-menu ribbon-title">
            
            <article class="cf">
            
                <div class="gr2 home-link">
                <div class="inner qt gr3">
                    <a href="/home" title="" class="home"></a>
                </div>
                </div>
                
                @if ( Auth::check() )
                @render( "layouts.chatmenu" )
                @endif    
            
            </article>
            
        </div>
        
        <div class="f site-links">
            <ul class="links cf">
                <li class="f t <?php if( $title == "About"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/about" title="">About</a>
                </li>
                <li class="f t <?php if( $title == "Tutorial"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/tutorial" title="">Tutorial</a>
                </li>
                <li class="f t <?php if( $title == "Blog"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/blog" title="">Blog</a>
                </li>
                <li class="f t <?php if( $title == "Contact"){ echo "active"; } ?>">
                    <a class="darkg c t" href="/contact" title="">Contact</a>
                </li>
            </ul>
        </div>
        
        <div class="r right cf">

            @if ( Auth::check() )
            @render( "layouts.menu" )
            @endif

        </div>
    </article>
</header>