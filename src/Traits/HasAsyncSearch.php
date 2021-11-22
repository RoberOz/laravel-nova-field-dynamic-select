<?php

namespace Hubertnnn\LaravelNova\Fields\DynamicSelect\Traits;

use Closure;

trait HasAsyncSearch
{
    protected $search = false;

    protected $asyncSearch = [];

    public function asyncSearch($asyncSearch)
    {
        $this->search = true;
        $this->asyncSearch = $asyncSearch;

        return $this;
    }

    public function getAsyncSearchResult($query = null)
    {
        $options = $this->asyncSearch instanceof Closure
            ? call_user_func($this->asyncSearch, $query)
            : $this->asyncSearch;

        $result = [];
        foreach ($options as $key => $option) {
            $result[] = [
                'value' => $key,
                'label' => $option,
            ];
        }

        return $result;
    }
}
