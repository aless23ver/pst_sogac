<?php

namespace App\Services;

use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\PreguntasFrecuentes;

class PreguntasFrecuentesService 
{
    public function getAll(): LengthAwarePaginator
    {
        $query = PreguntasFrecuentes::oldest();
        return $query->paginate(PreguntasFrecuentes::PAGINATE);
    }

    public function create(array $data): PreguntasFrecuentes
    {
        return PreguntasFrecuentes::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        return PreguntasFrecuentes::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return PreguntasFrecuentes::where('id', $id)->delete();
    }
}
