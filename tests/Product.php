<?php

namespace App\Models;

class Product {
    protected $attributes;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function find($id)
    {
        return new self(['id' => $id]);
    }

    public function __get($property)
    {
        return $this->attributes[$property];
    }
}