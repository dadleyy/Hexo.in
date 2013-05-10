<article class="f player-name {{ $type }}" id="{{ $type }}-info">
    <h4 class="position">{{ $type }}</h4>
    <h1 class="name blue c">{{ ($player != null) ? $player->username : "waiting" }}</h1>
    <div class="score cf">
        <h1 class="darkg r c" id="{{ $type }}-score">0</h1>
    </div>
    <div class="cf stats">
        <dl class="r wins">
            <dt>Wins</dt>
            <dd>{{ ($player != null) ? $player->wins : 0 }}</dd>
        </dl>
        <dl class="r wins">
            <dt>Losses</dt>
            <dd>{{ ($player != null) ? $player->losses : 0 }}</dd>
        </dl>
    </div>
    @if( $player && Auth::user( )->id == $player->id )
    @render('games.gamemenu')
    @endif 
</article>