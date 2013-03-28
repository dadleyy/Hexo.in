<article class="chat-rooms block f right">
    <div class="inner">
        <div class="top cf">
            <h4 class="f"><em>Chat</em>rooms</h4>
            <button class="add-new slick r gr3" data-type="chat-room">+</button>
        </div>
        <div class="bottom cf">
            <ul class="listing">
            <?php
            $rooms = Chatroom::where("game_id","=","0")->get();
            foreach( $rooms as $room ) {
            $users = $room->users( )->get( );
            ?>
    <li class="obj chat-room cf">
        <button class="t join-old quick-join slick f gr3" data-id="<?php echo $room->id; ?>" data-type="chat-room">+</button>
        <h1 class="name f"><?php echo $room->name; ?></h1>
        <h2 class="count r cf">
            <span class="f">Population:</span>
            <span class="f count"><?php echo count( $users ); ?></span>
        </h2>
    </li>
            <?php
            }
            ?>
            </ul>
        </div>
    </div>
</article>