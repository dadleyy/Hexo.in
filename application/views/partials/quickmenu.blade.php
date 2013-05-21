<article class="quickmenu block f right">
    <div class="inner">
        <div class="top cf">
            <h4>Quick<em>Menu</em></h4>
        </div>
        <div class="bottom">    
            <a href="/game/start" class="slick big gr4 t">
            @if( Auth::user()->game() !== false )
                Resume Game
            @else 
                Quick Game
            @endif
            </a>
            <button class="add-new dis slick big gr4 t" data-type="chat-room">New Chatroom</button>
        </div>
        <div class="top cf">
            <h4>Online<em>now</em></h4>
        </div>
        <div class="bottom">
            <div class="head cf">
                <h4 class="f col">Name</h4>
                <h4 class="r col">Losses</h4>
                <h4 class="r col">Wins</h4>
            </div>
            <ul class="online-list listing" id="online-list">
        @foreach( User::online() as $user )
                <li class="user cf"> 
                    @if( $user['busy'] )
<span class="icon busy f"></span>
                    @else
<button class="t challenge slick f gr3" data-user="{{ $user['username'] }}" data-type="challenge">+</button>
                    @endif
                    <h1 class="name f">{{ $user['username'] }}</h2>
                    <h1 class="amount r">{{ $user['losses'] }}</h2>
                    <h1 class="amount r">{{ $user['wins'] }}</h2>
                </li>
        @endforeach
            </ul>
        </div>
    </div>
</article>