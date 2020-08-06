<?php

namespace DevHokage\Collections;

use Closure;
use PDO;
use PDOException;
use ReflectionClass;
use stdClass;

class Collection
{
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function toArray()
    {
        return array_map(function ($v) {
            if ($v instanceof Collection) {
                return $v->toArray();
            }
            return $v;
        }, $this->items);
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function all()
    {
        return $this->items;
    }

    public function get(int $index)
    {
        return $this->items[$index];
    }

    public function end()
    {
        return end($this->items);
    }

    public function push($item): Collection
    {
        $this->items[] = $item;
        return $this;
    }

    public function map(Closure $fn): Collection
    {
        return new static(array_map(function ($v) use ($fn) {
            return $fn($v);
        }, $this->items));
    }

    public function mapWithKeys(Closure $fn): Collection
    {
        $return = [];
        $firstValidate = false;
     
        foreach ($this->items as $value) {
            $res = $fn($value);
 
            if (!$firstValidate) {
                if (!is_array($res)) {
                    die("Retorno da closure deve ser um array");
                }
                if (count($res) != 1) {
                    die("Array deve ter apenas 1 elemento");
                }
                $firstValidate = true;
            }
            if (!array_diff_key($res, $return)) {
                die("Há índice repetido");
            }
            $return = array_merge($return, $res);
        }

        return (new static($return));
    }

    public function chunk(int $n): Collection
    {
        $return = [];
        $i=0;
        foreach ($this->items as $value) {
            $i++;
            $fragment[]=$value;
            if (!($i % $n) || count($this->items) === $i) {
                $return[] = (new static($fragment));
                $fragment = [];
            }
        }
        return (new static($return));
    }

    public function filter(Closure $fn): Collection
    {
        $return = [];
        foreach ($this->items as $value) {
            if (!$fn($value)) {
                continue;
            }
            $return[]=$value;
        }

        return (new static($return));
    }

    public function reduce(Closure $fn)
    {
        $return = [];
        $before = null;
        foreach ($this->items as $value) {
            $before = $fn($before, $value);
        }

        return $before;
    }

    public function pluck($name): Collection
    {
        return new static(array_map(function ($v) use ($name) {
            if ($v instanceof Collection) {
                return $v->pluck($name);
            }
            
            if ($v instanceof Model) {
                if (!isset($v->{$name})) {
                    return null;
                }
                return $v->{$name};
            }
            if (!isset($v[$name])) {
                return null;
            }
            return $v[$name];
        }, $this->items));
    }

    public function unique(): Collection
    {
        return (new static(array_unique($this->items)))->values();
    }

    public function values(): Collection
    {
        return new static(array_values($this->items));
    }

    public function keyWhere($key, $value)
    {
        foreach ($this->items as $item) {
            $v=$item;
            if ($item instanceof Model) {
                $v = $v->{$key};
            }
            if ($v === $value) {
                return $item;
            }
        }
    }
}
