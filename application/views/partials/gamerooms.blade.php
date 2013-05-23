<article class="game-rooms block f left">
    <div class="inner">
        <div class="top cf">
            <h4 class="f"><em>Game</em>rooms</h4>
            <a href="/game/start" class="slick r gr3" data-type="game-room">+</a>
        </div>
        <div class="bottom cf">
            <div class="head cf">
                <h4 class="f col">Visitor</h4>
                <h4 class="r col">Challenger</h4>
            </div>
            <ul class="listing">
            <?php
            $games = Game::where( "visitor_id", ">", 0 )->where( "complete", "=", false )->where("is_tutorial","=",false)->get();
            foreach( $games as $game ) {
            $visitor    = User::find( $game->visitor_id );
            $challenger = User::find( $game->challenger_id );
            ?>
<li class="obj chat-room cf">
    <h1 class="name f"><em>{{ $visitor->username; }}</em></h1>
    <h1 class="name r"><em>{{ $challenger->username; }}</em></h1>
</li>
            <?php
            }
            ?>
            </ul>
        </div>
    </div>
</article>