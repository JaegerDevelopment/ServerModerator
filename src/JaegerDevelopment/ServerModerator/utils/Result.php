<?php
declare(strict_types=1);

namespace JaegerDevelopment\ServerModerator\utils;

use Closure;

class Result{

    public const PENDING = "pending";

    public const REJECTED = "rejected";

    public const FULFILLED = "fulfilled";

    /** @var mixed */
    protected $value = null;

    /** @var string */
    protected $now = self::PENDING;

    /** @var Closure[] */
    protected $fulfilled = [];

    /** @var Closure[] */
    protected $rejected = [];

    public function __construct(){

    }

    public function then(Closure $callback) : Result{
        if($this->now === self::FULFILLED){
            $callback($this->value);
            return $this;
        }
        $this->fulfilled[] = $callback;
        return $this;
    }

    public function catch(Closure $callback) : Result{
        if($this->now === self::REJECTED){
            $callback($this->value);
            return $this;
        }
        $this->rejected[] = $callback;
        return $this;
    }

    public function resolve($value) : Result{
        $this->setNow(self::FULFILLED, $value);
        return $this;
    }

    public function reject($reason) : Result{
        $this->setNow(self::REJECTED, $reason);
        return $this;
    }

    public function setNow(string $now, $value) : Result{
        $this->now = $now;
        $this->value = $value;

        $callbacks = $this->now === self::FULFILLED ? $this->fulfilled : $this->rejected;
        foreach($callbacks as $closure){
            $closure($this->value);
        }
        $this->fulfilled = $this->rejected = [];
        return $this;
    }
}
