<article class="leaderboard-stats block f left">
    <div class="inner">
        <div class="top cf">
            <h4>Leader<em>boards</em></h4>
        </div>
        <div class="bottom cf">
            <h2 class="subtitle blue c">Winners</h2>
            <div class="head cf">
                <h4 class="f col">Name</h4>
                <h4 class="r col">Wins</h4>
            </div>
            <ul class="listing">
            <?php 
            $winners = User::where("dummy_user","=",false)->order_by('wins', 'desc')->take(3)->get();
            $index   = 0;
            foreach( $winners as $winner ) {
            $index++;
            ?>
            <li class="cf">
                <h1 class="count f">{{ $index }}.</h1>  
                <h1 class="name f">{{ $winner->username }}</h2>
                <h1 class="amount r">{{ $winner->wins }}</h2>
            </li>
            <?php } ?>
            </ul>
        </div>
        <div class="bottom cf">
            <h2 class="subtitle orange c">Losers</h2>
            <div class="head cf">
                <h4 class="f col">Name</h4>
                <h4 class="r col">Losses</h4>
            </div>
            <ul class="listing">
            <?php 
            $losers = User::where("dummy_user","=",false)->order_by('losses', 'desc')->take(3)->get();
            $index   = 0;
            foreach( $losers as $loser ) {
            $index++;
            ?>
            <li class="cf">
                <h1 class="count f">{{ $index }}.</h1> 
                <h1 class="name f">{{ $loser->username }}</h2>
                <h1 class="amount r">{{ $loser->losses }}</h2>
            </li>
            <?php } ?>
            </ul>
        </div>
        <div class="bottom cf">
            <h2 class="subtitle green c">Activity</h2>
            <div class="head cf">
                <h4 class="f col">Name</h4>
                <h4 class="r col">Games</h4>
            </div>
            <ul class="listing">
            <?php 
            $actives = User::where("dummy_user","=",false)->order_by('games', 'desc')->take(3)->get();
            $index   = 0;
            foreach( $actives as $gamer ) {
            $index++;
            ?>
            <li class="cf">
                <h1 class="count f">{{ $index }}.</h1> 
                <h1 class="name f">{{ $gamer->username }}</h2>
                <h1 class="amount r">{{ $gamer->games }}</h2>
            </li>
            <?php } ?>
            </ul>
        </div>
        <div class="etc cf">
            <?php $game_total = User::sum('games'); ?>
            <dl class="stat cf">
                <dt class="f">Total games played:</dt>
                <dd class="f green c">{{ $game_total }}</dd>
            </dl>
        </div>
        <div class="top cf">
            <h4>New<em>users</em></h4>
        </div>
        <div class="bottom cf">
            <ul class="listing">
            <?php
            $newbs = User::where("dummy_user","=",false)->where( "games", "=", 0 )->get();
            foreach( $newbs as $newb ) {
            ?>
    <li class="obj user cf">
        <h1 class="name f"><?php echo $newb->username; ?></h1>
        <h1 class="count green c r">{{ $newb->joined() }}</h2>
    </li>
            <?php
            }
            ?>
            </ul>
        </div>
    </div>
</article>