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
                'img_url' => $this->class->img_url ? asset('storage/') . '/' . $this->class->img_url : null,
                'discipline' => $this->class->discipline->name,
                'discipline_img' => $this->class->discipline->icon_url ? asset('storage/') . '/' . $this->class->discipline->icon_url : null,
            ],
            'instructor' => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'profile_image' =>  $this->instructor->profile_image ? asset('storage/') . '/' . $this->instructor->profile_image : null,
                'rating_average' => round($this->instructor->rating_average, 1),
                'is_head_coach' => $this->instructor->is_head_coach,
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
            'theme' => $this->theme,
            'img_url' => $this->img_url ? asset('storage/') . '/' . $this->img_url : null,


            // Contadores de asientos
            'seats_summary' => [
                'total_seats' => $this->total_seats_count ?? 0,
                'available_count' => $this->available_seats_count ?? 0,
                'reserved_count' => $this->reserved_seats_count ?? 0,
                'occupied_count' => $this->occupied_seats_count ?? 0,
                'blocked_count' => $this->blocked_seats_count ?? 0,
            ],

            // Asientos (solo si están cargados)
            'seats' => $this->when($this->relationLoaded('seats'), function () {
                return $this->seats->groupBy('pivot.status')->map(function ($seats, $status) {
                    return $seats->map(function ($seat) {
                        return [
                            'id' => $seat->id,
                            'seat_number' => $seat->seat_number,
                            'row' => $seat->row,
                            'column' => $seat->column,
                            'status' => $seat->pivot->status,
                            'user' => $seat->pivot->user_id ? [
                                'id' => $seat->user?->id,
                                'name' => $seat->user?->name,
                            ] : null,
                            'reserved_at' => $seat->pivot->reserved_at,
                            'expires_at' => $seat->pivot->expires_at,
                        ];
                    });
                });
            }),

        ];
    }
}
