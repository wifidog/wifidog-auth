<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = ['id', 'sys_uptime', 'sys_memfree', 'sys_load', 'wifidog_uptime', 'created_at', 'updated_at'];
    protected $primaryKey = 'id';
    public $incrementing = false;
}
