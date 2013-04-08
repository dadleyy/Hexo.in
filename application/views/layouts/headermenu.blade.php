<div id="geo-zone" class="r" style="display:none;"></div>
<div class="r menu" id="settings-menu">
    <button class="toggle"></button>
    <div class="dropable t">
        <h1>Settings</h1>
        <ul class="links">
            <li class="t"><a class="t" href="/session/end" title="">logout</a></li>
            <li class="t"><a class="t" href="/session/end" title="">settings</a></li>
        </ul>
    </div>
</div>
<div class="r menu" id="heartbeat-menu">
    <button class="toggle"></button>
    <span class="bubble gr1 <?php echo (count(Auth::user()->notifications()->get()) > 0 )?'on':'off';?>">{{ count( Auth::user()->notifications()->get() ) }}</span>
    <div class="dropable t">
        <h1>Notifications</h1>
        <ul class="links" id="notification-list">
        @foreach( Auth::user()->notifications()->get() as $note )
        <li class="{{ $note->type }} cf">
        @if( $note->type === "game" )
            <a href="/game/start/{{ $note->item_id }}" class="t slick f gr3">+</a>
            <p>{{ User::find( $note->source_id )->username }} has invited you to play a game with them!</p>
        @else
        
        @endif
        </li>
        @endforeach
        </ul>
    </div>
</div>
<div class="r name">
    <h3>Hello, <em><?php echo Auth::user()->username; ?></em></h3>
</div>