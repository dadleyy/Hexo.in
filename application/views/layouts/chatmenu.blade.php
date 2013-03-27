<div class="chat-list cf" id="chatist-menu">
    <ul class="room-list t">
    <?php
        $user  = Auth::user();
        $rooms = $user->chatrooms()->get();
        foreach( $rooms as $room ) {
        $count = count( $room->users()->get() );
    ?>
        <li class="active-room t cf">
            <button class="t join-old quick-join slick f gr3" data-id="<?php echo $room->id; ?>" data-type="chat-room">+</button>
            <h1 class="f name">{{ $room->name }}</h1>
            <h2 class="count cf r">
                <span class="f">users:</span>
                <span class="f count">{{ $count }}</span>
            </h2>
        </li>
    <?php 
        }
    ?>
    </ul>
    <button class="chatlist-toggle toggle t gr4">chat list</button>
</div>