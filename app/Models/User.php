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
}
