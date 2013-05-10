<section class="game-zone f">
    
    <div class="game-title">
        <span id="turn-indicator" class="icon"></span>
        @if( $game->is_private )
        <h1><em>Pivate</em>game</h1>
        @else
        <h1><em>Public</em>game</h1>
        @endif
    </div>
    
    <div class="inner">
        <section class="game-board whole">
            <div id="game-message-box" class=""><div class="inner"></div></div>
            <figure id="render-zone"></figure>
        </section>
    </div>
    
    @if( !isset($nochat) )
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
    @endif
    
</section>