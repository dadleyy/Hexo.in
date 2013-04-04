<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    <link rel="stylesheet" type="text/css" href="/css/game.css">
    
</head>
<body>
    
    @include('layouts.header')
    
    <section class="content whole cf">
    <section class="main-content spw middle cf">
    
        <article class="f player-name challenger" id="challenger-info">
        <?php $challenger = $game->challenger(); ?>
            <h4 class="position">Challenger</h4>
            <h1 class="name blue c">{{ $challenger->username }}</h1>
            <div class="cf stats">
                <dl class="r wins">
                    <dt>Wins</dt>
                    <dd>{{ $challenger->wins }}</dd>
                </dl>
                <dl class="r wins">
                    <dt>Losses</dt>
                    <dd>{{ $challenger->losses }}</dd>
                </dl>
            </div>
            @if( $challenger && Auth::user()->id == $challenger->id )
            @render("layouts.gamemenu")
            @endif 
        </article>
        
        <section class="game-zone f">
            <div class="inner">
            <section class="game-board whole">
                <figure id="render-zone"></figure>
            </section>
            </div>
            
            <div class="inner">
            <section class="chat-window whole">
                <section id="chat-zone">
                    @foreach( $chat->messages() as $message )
                    <dl class="message cf">
                        <dt class="f">{{ $message['user'] }}</dt>
                        <dd class="f">{{ $message['message'] }}</dd>
                    </dl>
                    @endforeach
                </section>
            </section>
            </div>
            <div class="inner">
            <section class="chat-control whole">
                <form id="chat-input" action="/chat/post" method="post" class="IValidate">
<input class="IValidate" type="text" data-filter="any" data-placeholder="message" name="message">
                </form>
            </section>
            </div>
            
        </section>
        
        <article class="f player-name visitor" id="visitor-info">
        <?php $visitor = $game->visitor(); ?>
            <h4 class="position">Visitor</h4>
            <h1 class="name green c">{{ ($visitor != null) ? $visitor->username : "waiting" }}</h1>
            <div class="cf stats">
                <dl class="f wins">
                    <dt>Wins</dt>
                    <dd>{{ ($visitor != null) ? $visitor->wins : 0 }}</dd>
                </dl>
                <dl class="f losses">
                    <dt>Losses</dt>
                    <dd>{{ ($visitor != null) ? $visitor->losses : 0 }}</dd>
                </dl>
            </div>   
            @if( $visitor && Auth::user()->id == $visitor->id )
            @render("layouts.gamemenu")
            @endif 
        </article>
        
    </section>
    </section>
    
    @render('layouts.footer')
    
    @if ( Auth::check() )
    @render( "layouts.pulldowns" )
    @endif    
   
    <script type="text/javascript" src="/js/d3.js"></script>
    @render('globals.scripts')
    <script type="text/javascript" src="/js/IV.js"></script>
    <script type="text/javascript" src="/js/game.min.js"></script>
    <script type="text/javascript">Game({{ $game_js }})</script>
    
</body>
</html>