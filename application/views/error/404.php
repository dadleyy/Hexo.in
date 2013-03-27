<?php

    echo View::make('layouts.common')
            ->nest("content", "errors.404")
            ->with("title", "404");

?>