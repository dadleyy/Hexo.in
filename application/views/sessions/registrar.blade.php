@section('scripts')
<script type="text/javascript" src="/js/IV.js"></script>
<script type="text/javascript" src="/js/session.js"></script>
@endsection

@section('styles')
<link rel="stylesheet" type="text/css" href="/css/session.css">
@endsection

@section('content')
    <section class="main-content pw middle cf">
        <article class="login-form middle cf">
            <span class="plain-logo"></span>
            <h1>Hop in and become a <em>hex</em></h1>
            <p>Sign up now and join the fun!</p>
            <div class="errors">
                @if( gettype($errors) == "string" )
                <h1 class="red c">{{ $errors }}</h1>
                @endif
            </div>
<?php echo Form::open('/session/register', 'POST', array('class'=>'IValidate')); echo Form::token( ); ?>

<div class="input cf">
<input type="text" name="email" class="t IValidate" data-placeholder="email" data-filter="email" autocomplete="off">
</div>

<div class="input cf">
<input type="text" name="user" class="t IValidate" data-filter="minmax" data-minlength="4" data-maxlength="20" data-placeholder="username (max 20 chars | min 4 chars)" autocomplete="off">
</div>

<div class="input cf">
<input type="password" name="pass" class="t IValidate" data-filter="min" data-minlength="9" autocomplete="off">
<span class="placeholder">Password (min 10 chars)</span>
</div>

<div class="input cf">
<input type="password" name="passc" class="t IValidate" data-filter="equal" data-target="pass" autocomplete="off">
<span class="placeholder">Confirm Password</span>
</div>

<div class="register actions cf">
    <input type="submit" value="register" class="t IValidator gr3 f">
</div>
<?php echo Form::close( ); ?>
        </article>
    </section>
@endsection