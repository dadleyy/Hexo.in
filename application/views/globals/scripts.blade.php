<div id="{{ substr( time()."", 0, 5 ) }}">
<?php echo Form::token( ); ?>
</div>

<!-- start global js scripts -->
<script type="text/javascript" src="/js/dependencies.js"></script>
<script type="text/javascript" src="/js/global.js"></script>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-39532711-1']);
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
<script type="text/javascript">User({{ Auth::user()->publicJSON() }})</script>
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


<!-- end global js scripts -->