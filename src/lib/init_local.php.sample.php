<?php

$envs = [
    "API_KEY" => "API_KEY"
    ,"API_URL" => "API_URL"
    ,"REDIS_URL" => "REDIS_URL"
    ,"FENIX_REQUEST_URL" => "FENIX_REQUEST_URL"
    ,"FENIX_BASIC_AUTH" => "FENIX_BASIC_AUTH"
    ,"LOCODE" => "LOCODE"
    ,"AINO_API_KEY" => "AINO_API_KEY"
];

foreach ($envs as $k => $v) {
    putenv("$k=$v");
};
