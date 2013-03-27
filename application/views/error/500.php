<?php

    echo View::make('layouts.common')
            ->nest("content", "errors.500")
            ->with("title", "500");

?>