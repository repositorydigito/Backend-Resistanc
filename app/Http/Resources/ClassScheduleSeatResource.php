<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleSeatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,
            'code' => $this->code,
            'row' => $this->row,
            'column' => $this->column,
            'status' => $this->status,
            'class_schedule' => $this->classSchedule ? new ClassScheduleResource($this->classSchedule) : null,

        ];

    }
}
