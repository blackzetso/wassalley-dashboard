<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * substr to show only 512 character of the description
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => substr($this->description, 0 , 512),
            'image' => asset('/storage/'.$this->image),
            'user_name' => $this->user->name,
            'created_at' => $this->created_at->format('m/d/Y'),
            'updated_at' => $this->updated_at->format('m/d/Y'),
        ];
    }
}
