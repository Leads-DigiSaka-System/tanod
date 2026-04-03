<?php

return [

    'app_key' => env('M360_APP_KEY'),
    'app_secret' => env('M360_APP_SECRET'),
    'sender_name' => env('M360_SENDER_NAME', 'LeadsAgri'),
    'api_url' => 'https://api.m360.com.ph/v4/sms/send',

];
