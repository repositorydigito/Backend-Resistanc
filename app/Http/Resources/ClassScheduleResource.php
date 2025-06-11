<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
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
            'class' => [
                'id' => $this->class->id,
                'name' => $this->class->name,
                'discipline' => $this->class->discipline->name,
            ],
            'instructor' => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
            ],
            'studio' => [
                'id' => $this->studio->id,
                'name' => $this->studio->name,
            ],
            'scheduled_date' => $this->scheduled_date->format('d/m/Y'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_capacity' => $this->max_capacity,
            'available_spots' => $this->available_spots,
            'booked_spots' => $this->booked_spots,
            'waitlist_spots' => $this->waitlist_spots,
            'booking_opens_at' => $this->booking_opens_at?->format('d/m/Y H:i'),
            'booking_closes_at' => $this->booking_closes_at?->format('d/m/Y H:i'),
            'cancellation_deadline' => $this->cancellation_deadline?->format('d/m/Y H:i'),
            'special_notes' => $this->special_notes,
            'is_holiday_schedule' => $this->is_holiday_schedule,
            'status' => $this->status,
        ];

    }
}
