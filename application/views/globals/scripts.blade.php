<div id="{{ substr( time()."", 0, 5 ) }}">
<?php echo Form::token( ); ?>
</div>

<!-- start global js scripts -->
<script type="text/javascript" src="{{ asset("js/dependencies.js") }}"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCqLAjnUApy5HiwwPTj_Oq9WIiQo-plM1A&sensor=false"></script>
<script type="text/javascript" src="{{ asset("js/IV.min.js") }}"></script>
<script type="text/javascript" src="{{ asset("js/global.min.js") }}"></script>

<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-39532711-1']);
_gaq.push(['_setDomainName', 'hexo.in']);
_gaq.push(['_trackPageview']);

(function() {
    var ga, s;
    
    ga = document.createElement('script'); 
    ga.type = 'text/javascript'; 
    ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    s = document.getElementsByTagName('script')[0]; 
    
    s.parentNode.insertBefore(ga, s);
    
})( );
</script>
@if( Auth::user() !== null )

<script type="text/javascript">hexo.User({{ Auth::user()->publicJSON() }})</script>

@foreach( Auth::user()->publicChats( ) as $chatroom )
<script type="text/javascript">hexo.Chat( {{ $chatroom->publicJSON( ) }}, false )</script>
@endforeach

@endif

<script type="text/template" data-name="game-notification">
    <li class="game cf"> 
        <a href="/game/start/<%= item_id %>" class="t slick f gr3">+</a>
        <p><%= source_name %> has invited you to play a game with them!</p>
    </li>
</script>

<script type="text/template" data-name="friend-notification">
    <li class="friend cf"> 
    
    </li>
</script>

<script type="text/template" data-name="chatroom-tempate">
    <section class="chatroom whole" data-name="<%= name %>" data-uid="<%= uid %>">
        <article class="middle">
            <button class="closer t slick gr3" data-uid="<%= uid %>">x</button>
            <div class="inner">
            <section id="<%= room_id %>-window" class="chat-window"> 
            
            </section>
            </div>
            <div class="inner">
            <form id="<%= room_id %>-form" action="/chat/post" method="post" class="IValidate">
<input class="IValidate" type="text" data-filter="any" data-placeholder="message" name="message">
            </form>
            </div>
        </article>
    </section>
</script>

<script type="text/template" data-name="charoom-listitem-template">
    <li class="active-room t cf">
        <button class="t opener slick f gr3" data-name="<%= name %>" data-uid="<%= uid %>">+</button>
        <button class="t closer slick f gr3" data-name="<%= name %>" data-uid="<%= uid %>">-</button> 
        <h1 class="f name"><%= name %></h1>
        <h2 class="count cf r">
            <span class="f">users:</span>
            <span class="f count"><%= count %></span>
        </h2>
    </li>    
</script>

<script type="text/template" data-name="chat-message-template">
    <dl class="cf message">
        <dt class="f"><%= user %></dt>
        <dd class=""><%= text %></dd>
    </dl>
</script>
<!-- end global js scripts -->