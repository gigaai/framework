<?php

namespace GigaAI\Http;

use GigaAI\Storage\Eloquent\Instance;

class MessengerProfile
{
    /**
     * Available Messenger Profile Fields
     *
     * @var array
     */
    public static $fields = [
        'get_started', 'persistent_menu', 'greeting', 'whitelisted_domains',
        'account_linking_url', 'payment_settings', 'target_audience'
    ];
    
    /**
     * The Messenger Profile API URL
     *
     * @return string
     */
    private static function getResourceUrl()
    {
        return Request::PLATFORM_RESOURCE . 'me/messenger_profile?access_token=' . Request::$token;
    }
    
    
    /**
     * Update all fields
     *
     * @return mixed
     */
    public static function updateMessengerProfile()
    {
        $update = [];
        $delete = [];
    
        $irregular = [
            'get_started' => 'get_started_button_payload',
        ];
    
        $resource = self::getResourceUrl();
        
        foreach (self::$fields as $field_name) {
    
            if ( ! isset($irregular[$field_name])) {
                $field_value = Instance::get($field_name);
            } else {
                $field_value = Instance::get($irregular[$field_name]);
            }
            
            if ( ! empty($field_value) && ! is_null($field_value)) {
        
                $update[$field_name] = $field_value;
        
                if ($field_name === 'get_started') {
                    $update['get_started'] = [
                        'payload' => $field_value
                    ];
                }
            } else {
                $delete[] = $field_name;
            }
        }
        
        if ( ! empty($update)) {
            $messages['update'] = Request::send($resource, $update);
        }

        if ( ! empty($delete)) {
            $messages['delete'] = self::deleteFields($delete);
        }
        
        return $messages;
    }
    
    public static function deleteFields($fields)
    {
        $resource = self::getResourceUrl();
        
        $fields = (array) $fields;
        
        return giga_remote_delete($resource, compact('fields'));
    }
    
    public static function getFields($fields)
    {
        $resource = self::getResourceUrl();
        
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        
        $resource .= '&fields=' . $fields;
        
        return giga_remote_get($resource);
    }
    
    /**
     * Update each fields separately
     *
     * @param String $field_name
     *
     * @return mixed
     */
    public static function updateField($field_name)
    {
        $irregular = [
            'get_started' => 'get_started_button_payload',
            'greeting'    => 'greeting_text'
        ];
        
        if ( ! isset($irregular[$field_name])) {
            $field_value = Instance::get($field_name);
        } else {
            $field_value = Instance::get($irregular[$field_name]);
        }
    
        $resource = self::getResourceUrl();
    
        if ( ! empty($field_value) && ! is_null($field_value)) {
            
            $data = [
                $field_name => $field_value
            ];
    
            if ($field_name === 'get_started') {
                $data = [
                    'get_started' => [
                        'payload' => $field_value,
                    ],
                ];
            }
    
            return Request::send($resource, $data);
        }
    
        return self::deleteFields($field_name);
    }
}