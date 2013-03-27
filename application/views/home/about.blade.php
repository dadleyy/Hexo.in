@section('styles')
<link rel="stylesheet" type="text/css" href="/css/about.css">
@endsection

@section('scripts')
    
@endsection

@section('content')
    <section class="main-content spw middle about cf">
        <h1>About<br><em class="righteous">Hexo.in</em></h1>
        <article class="two">
            <div class="two f cf left">
            <div class="inner">
                <p class="big">Hexo.in is a multiplayer online game with similarities to <i>roulette</i>.</p> 
                <p class="reg">Duke it out against your opponent for strategic hexagonal probability and watch as your wins (or losses) rank up. Follow the leader boards to see who is on top or chat with fellow oddballs in the chatrooms after the game.</p>
                <p class="reg">The game itself was inspired by a mixture of several games, including <i>roulette</i>, <i>hexagon</i> and <i>settlers of catan</i>. The game itself is built using the up-and-coming php framework, <i>laravel</i>. It was built by Danny Hadley as a project for Web Client-Server programming, a course offered by RIT. Currently, there are <?php echo User::count(); ?> players registered.</p>
                <p class="big">Follow <em class="rale">Hexo.in</em></p>
                <div class="socials cf">
                    <a href="https://twitter.com/hexoin" title="" class="f icon twitter">
                        <span class="fg t"></span>
                        <span class="logo t"></span>
                    </a>
                    <a href="https://www.facebook.com/pages/Hexoin/570567029628060" title="" class="f icon facebook">
                        <span class="fg t"></span>
                        <span class="logo t"></span>
                    </a>
                </div>
            </div>
            </div>
            <div class="two f cf right">
            <div class="inner">
                <p class="big">Reviews</p>
                <ul class="reviews">
                    <li>"Tiam at justo rhoncus sapien adipiscing faucibus at ac est." <br><em>-Fake Person</em></li>
                    <li>"Proin non urna non tellus tempor aliquam sit amet a odio. Nullam volutpat tincidunt tempus." <br><em>-Someone Else</em></li>
                    <li>"Aliquam accumsan faucibus nibh, non tristique arcu ultricies nec." <br><em>-Aye Gamer</em></li>
                    <li>"In sit amet lorem eget leo egestas semper id vel orci." <br><em>-Some Bro</em></li>
                    <li>"Suspendisse vestibulum turpis venenatis leo pharetra dictum." <br><em>-Diddel Daddle</em></li>
    
                </ul>
            </div>
            </div>
        </article>
    </section>
@endsection