<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'base_uri' => env('OPENAI_BASE_URI', 'https://openai.phelotto.com/v1'),
    'x_proxy_token' => env('OPENAI_PROXY_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Request Log
    |--------------------------------------------------------------------------
    |
    | Enable request/response logs for OpenAI HTTP calls.
    |
    */

    'request_log' => env('OPENAI_REQUEST_LOG', false),
    'request_log_channel' => env('OPENAI_REQUEST_LOG_CHANNEL', 'daily'),
];
