<?php
namespace GigaAI\Storage\Eloquent;

trait UserModel
{
    public function getUserModel()
    {
        return is_inside_wp() ? WPUser::class : 'App\User';
    }

    public function getUserModelKey()
    {
        return is_inside_wp() ? 'ID' : 'id';
    }
}
