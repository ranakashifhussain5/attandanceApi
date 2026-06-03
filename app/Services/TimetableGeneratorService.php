<?php

namespace App\Services;

use App\Models\{
    BatchCourse, Room, Schedule, TimeSlot,
    TeacherAvailability, TimetableGenerationLog
};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimetableGeneratorService
{
    private array $teacherSlotUsed = [];
    private array $roomSlotUsed    = [];
    private array $batchSlotUsed   = [];

    public function generate(int $departmentId, int $semester, int $hodId): TimetableGenerationLog
    {
        $log = TimetableGenerationLog::create([
            'hod_id'        => $hodId,
            'department_id' => $departmentId,
            'semester'      => $semester,
            'status'        => 'processing',
        ]);

        try {
            DB::transaction(function () use ($departmentId, $semester, $log) {
                $effectiveFrom = Carbon::now()->startOfWeek();

                $batchCourses = BatchCourse::with([
                    'batch.program', 'course', 'teacher.availability'
                ])
                ->whereHas('batch.program', fn($q) =>
                    $q->where('department_id', $departmentId)
                )
                ->whereHas('batch', fn($q) =>
                    $q->where('semester', $semester)->where('is_active', true)
                )
                ->where('is_active', true)
                ->get();

                $timeSlots = TimeSlot::where('is_active', true)
                    ->orderBy('start_time')->get();

                $rooms = Room::where('is_active', true)
                    ->where(fn($q) => $q->whereNull('department_id')
                        ->orWhere('department_id', $departmentId))
                    ->get();

                $days = [1, 2, 3, 4, 5];

                $this->loadExistingOccupancy($effectiveFrom);

                $scheduled = [];
                $conflicts = [];

                foreach ($batchCourses as $bc) {
                    $classesPerWeek = $bc->course->credit_hours >= 3 ? 2 : 1;
                    $placed = 0;

                    foreach ($days as $day) {
                        if ($placed >= $classesPerWeek) break;

                        foreach ($timeSlots as $slot) {
                            $key = "{$day}_{$slot->id}";

                            if (! $this->isTeacherAvailable($bc->teacher_id, $day, $slot)) continue;
                            if (! $this->isTeacherSlotFree($bc->teacher_id, $key)) continue;
                            if (! $this->isBatchSlotFree($bc->batch_id, $key)) continue;

                            $room = $this->findAvailableRoom($rooms, $key, $bc->batch->max_students ?? 40);
                            if (! $room) continue;

                            $scheduled[] = [
                                'batch_course_id' => $bc->id,
                                'room_id'         => $room->id,
                                'time_slot_id'    => $slot->id,
                                'day_of_week'     => $day,
                                'effective_from'  => $effectiveFrom->toDateString(),
                                'is_active'       => true,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ];

                            $this->markOccupied($bc->teacher_id, $room->id, $bc->batch_id, $key);
                            $placed++;
                            break;
                        }
                    }

                    if ($placed < $classesPerWeek) {
                        $conflicts[] = [
                            'batch_course_id' => $bc->id,
                            'batch'           => $bc->batch->name,
                            'course'          => $bc->course->name,
                            'needed'          => $classesPerWeek,
                            'placed'          => $placed,
                            'reason'          => 'No available slot found',
                        ];
                    }
                }

                if (! empty($scheduled)) {
                    $bcIds = array_column($scheduled, 'batch_course_id');
                    Schedule::whereIn('batch_course_id', $bcIds)
                        ->where('effective_from', '>=', $effectiveFrom)
                        ->delete();

                    Schedule::insert($scheduled);
                }

                $log->update([
                    'status'             => 'success',
                    'schedules_created'  => count($scheduled),
                    'conflicts_detected' => count($conflicts),
                    'conflict_details'   => $conflicts ?: null,
                    'generated_at'       => now(),
                ]);
            });
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    private function loadExistingOccupancy(Carbon $from): void
    {
        $existing = Schedule::with('batchCourse')
            ->where('effective_from', '>=', $from)
            ->where('is_active', true)
            ->get();

        foreach ($existing as $s) {
            $key = "{$s->day_of_week}_{$s->time_slot_id}";
            $this->teacherSlotUsed[$s->batchCourse->teacher_id][$key] = true;
            $this->roomSlotUsed[$s->room_id][$key]                    = true;
            $this->batchSlotUsed[$s->batchCourse->batch_id][$key]     = true;
        }
    }

    private function isTeacherAvailable(int $teacherId, int $day, TimeSlot $slot): bool
    {
        return TeacherAvailability::where('teacher_id', $teacherId)
            ->where('day_of_week', $day)
            ->where('available_from', '<=', $slot->start_time)
            ->where('available_to', '>=', $slot->end_time)
            ->exists();
    }

    private function isTeacherSlotFree(int $teacherId, string $key): bool
    {
        return empty($this->teacherSlotUsed[$teacherId][$key]);
    }

    private function isBatchSlotFree(int $batchId, string $key): bool
    {
        return empty($this->batchSlotUsed[$batchId][$key]);
    }

    private function findAvailableRoom(Collection $rooms, string $key, int $minCapacity): ?Room
    {
        return $rooms
            ->filter(fn(Room $r) =>
                $r->capacity >= $minCapacity &&
                empty($this->roomSlotUsed[$r->id][$key])
            )
            ->sortBy('capacity')
            ->first();
    }

    private function markOccupied(int $teacherId, int $roomId, int $batchId, string $key): void
    {
        $this->teacherSlotUsed[$teacherId][$key] = true;
        $this->roomSlotUsed[$roomId][$key]       = true;
        $this->batchSlotUsed[$batchId][$key]     = true;
    }
}
