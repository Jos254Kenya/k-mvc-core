<?php

use sigawa\mvccore\AuthProvider;

function auth(): AuthProvider
{
    return new AuthProvider();
}