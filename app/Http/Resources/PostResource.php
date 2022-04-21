<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

use App\CentralLogics\Helpers;

class ProductResource extends ResourceCollection
{
    //TODO :: MAKE SURE TO BE AS ORIGINAL PAGINATION

    //TODO :: DESC MUST BE 512 CHAR LIMIT


    private $pagination;

    public function __construct($resource)
    {
        $this->pagination = [
            'total' => $resource->total(),
            'count' => $resource->count(),
            'per_page' => $resource->perPage(),
            'current_page' => $resource->currentPage(),
            'total_pages' => $resource->lastPage(),
            'last_page'=> $resource->lastPage(),
            'next_page_url'=> $resource->nextPageUrl(),
            'prev_page_url'=> $resource->previousPageUrl(),
            'path'=> $resource->path(),
        ];

      
    	$resource =   Helpers::product_data_formatting($resource->getCollection(), true);
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            'data' => $this->collection,
            'pagination' => $this->pagination
        ];

    }
}
