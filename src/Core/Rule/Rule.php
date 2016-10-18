<?php


namespace GigaAI\Core\Rule;

use Illuminate\Database\Eloquent\Model;


/**
 * Class Rule
 *
 * @property $request
 * @property $response
 * @property $thenHandler
 *
 * @package GigaAI\Core
 */
class Rule extends Model
{
    public $timestamps = false;

    public $fillable = [
        'request',
        'response',
        'thenHandler'
    ];
}