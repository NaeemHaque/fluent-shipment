<?php

namespace FluentShipment\App\Models;

use FluentShipment\App\Models\Model;
use FluentShipment\Framework\Database\Orm\UserProxyTrait;

class User extends Model
{
    use UserProxyTrait;

    public $timestamps = false;

    protected $table = 'users';
    
    protected $primaryKey = 'ID';

    public function posts()
    {
        return $this->hasMany(Post::class, 'post_author', 'ID');
    }
}
