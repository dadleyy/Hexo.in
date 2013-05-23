@layout('layouts.common')

@section('scripts')
<script type="text/javascript" src="/js/session.js"></script>
@endsection

@section('styles')
<link rel="stylesheet" type="text/css" href="/css/session.css">
@endsection

@section('content')
    <section class="main-content pw middle cf">
        <article class="login-form middle cf">
            <span class="plain-logo"></span>
            <h1>You have reached the gate</h1>
            <p>A login is required to continue beyond this point.</p>
            <div class="errors">
                @if( gettype($errors) == "string" )
                <h1 class="red c">{{ $errors }}</h1>
                @endif
            </div>
<?php echo Form::open('/session/attempt', 'POST', array('class'=>'IValidate')); echo Form::token( ); ?>

<div class="input cf">
<input type="text" name="email" class="t IValidate" data-placeholder="email" data-filter="email" autocomplete="off">
</div>

<div class="input cf">
<input type="password" name="passw" class="t IValidate" data-filter="any" autocomplete="off">
<span class="placeholder">Password</span>
</div>

<div class="actions cf">
    <input type="submit" value="login" class="t IValidator gr3 f">
    <a class="f gr3 t" href="/session/registrar" title="">Register</a>
</div>

<?php echo Form::close( ); ?>
        </article>
    </section>
@endsection