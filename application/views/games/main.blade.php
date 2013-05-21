<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    @include('globals.headerinfo')
    <link rel="stylesheet" type="text/css" href="/css/game.css">
    @render('globals.tests')
</head>
<body>
    
    @include('globals.header')
    
    <section class="content pw whole cf">
    <section class="main-content spw middle cf">
    
        @include('games.player-ui')->with("type","challenger")->with("player",$game->challenger( ))
        
        @include('games.game-ui')
                
        @include('games.player-ui')->with("type","visitor")->with("player",$game->visitor( ))
        
    </section>
    </section>
    
    @render('globals.footer')
    
    <script type="text/javascript" src="/js/d3.js"></script>
    @render('globals.scripts')
    <script type="text/javascript" src="/js/game.min.js"></script>
    <script type="text/javascript">hexo.Game({{ $game_js }})</script>
    <script type="text/template" data-name="game-messagebox">
        <div class="pad">
            <p><%= message %></p>
            <div class="actions middle cf">
                <button class="middle gr3 closer <% if( !!homebtn ) { %> f <% } %>">close</button>
                <% if( !!homebtn ) { %> 
                <a href="/game/quit" title="" class="gr3 f">return to lobby</a>
                <% } %>
            </div>
        </div>
    </script>
    
    
</body>
</html>