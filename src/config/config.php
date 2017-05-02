<?php

// The Placeholder For Laravel Only

return [
    
    'page_id'           => 'YOUR_PAGE_ID',
    
    'page_access_token' => 'YOUR_PAGE_ACCESS_TOKEN',
    
    'app_id'            => 'YOUR_APP_ID_{NOT_REQUIRED}',
    
    'auto_stop'         => [
        'stop_when'    => '*',
        'restart_when' => ':)'
    ],
    
    'greeting_text' => [
        [
            'locale' => 'default',
            'text'   => 'Hi {{user_first_name}}, welcome to my page!'
        ]
    ],
    
    'persistent_menu' => [
        [
            'locale' => 'default',
            'call_to_actions' => [
                //
            ]
        ]
    ],
    
    'get_started_payload' => 'GIGA_GET_STARTED_PAYLOAD'
];