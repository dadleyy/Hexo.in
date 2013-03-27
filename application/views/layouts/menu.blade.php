<div class="r menu" id="authd-menu">
    <button class="toggle"></button>
    <div class="dropable t">
        <ul class="links">
            <li class="t"><a class="t" href="/session/end" title="">logout</a></li>
            <li class="t"><a class="t" href="/session/end" title="">settings</a></li>
        </ul>
    </div>
</div>
<div class="r name">
    <h3>Hello, <em><?php echo Auth::user()->username; ?></em></h3>
</div>