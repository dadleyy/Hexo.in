@layout('layouts.common')

@section('styles')
<link rel="stylesheet" type="text/css" href="/css/about.css">
@endsection

@section('scripts')
<script src="{{ asset('js/contact.min.js') }}" type="text/javascript"></script>
@endsection

@section('content')
<section class="main-content spw middle">
    <h1>Something to say?<br><em>Send an email.</em></h1>
    <article class="two cf">
        <article class="form-container middle f two">
        <div class="inner">
            <p class="big">Have an idea to make the game better?</p>
            <article class="success">
                <h1>Thank you!</h1>
                <h3>Your feedback is appreciated. We will be in touch soon</h3>
            </article>
    <?php echo Form::open('/contact/send', 'POST', array('id'=>'contact-form','class'=>'IValidate')); ?>
    
    <div class="input cf">
    <input type="text" name="name" class="t IValidate" data-placeholder="name" data-filter="any" autocomplete="off">
    </div>
    
    <div class="input cf">
    <input type="text" name="email" class="t IValidate" data-placeholder="email" data-filter="email" autocomplete="off">
    </div>
    
    <div class="input cf">
    <textarea class="IValidate" name="message" data-filter="any" data-placeholder="comments"></textarea>
    </div>
    
    <div class="actions cf">
        <input type="submit" value="send" class="t IValidator gr3 f">
    </div>
    <?php echo Form::close( ); ?>
        </div>
        </article>
        <article class="two f other-contact">
        <div class="inner">
            <p class="big">Looking to email someone in particular?</p>
            <ul class="reviews">
                <li><a href="mailto:info@hexo.in">info:  <em>info@hexo.in</em></a></li>
                <li><a href="mailto:danny@hexo.in">danny: <em>danny@hexo.in</em></a></li>
            </ul>
        </div>
        </article>
    </article>
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
</section>
@endsection