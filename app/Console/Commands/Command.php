<?php namespace App\Console\Commands;

abstract class Command extends \Illuminate\Console\Command
{

    protected $name;
    protected $description;

    abstract public function handle();

}
