<div id="{{ substr( time()."", 0, 5 ) }}">
<?php echo Form::token( ); ?>
</div>

<!-- start global js scripts -->
<script type="text/javascript" src="/js/jquery.js"></script>
<script type="text/javascript" src="/js/underscore.js"></script>
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

<!-- end global js scripts -->