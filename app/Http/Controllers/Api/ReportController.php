<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceRecord, AttendanceSession, Batch, BatchCourse, BatchStudent};
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function attendanceReport(Request $request, Batch $batch): StreamedResponse
    {
        $data     = $this->buildReportData($batch);
        $filename = "attendance_report_{$batch->name}";

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            $headers = ['Student ID', 'Name', 'Email'];
            foreach ($data['courses'] as $bc) {
                $headers[] = $bc->course->code . ' (%)';
            }
            fputcsv($out, $headers);

            foreach ($data['rows'] as $row) {
                $line = [$row['student_id'], $row['student_name'], $row['email']];
                foreach ($data['courses'] as $bc) {
                    $line[] = $row['courses'][$bc->course->code]['percentage'] ?? 0;
                }
                fputcsv($out, $line);
            }

            fclose($out);
        }, "$filename.csv", ['Content-Type' => 'text/csv']);
    }

    private function buildReportData(Batch $batch): array
    {
        $students     = BatchStudent::where('batch_id', $batch->id)
            ->where('status', 'active')->with('student')->get();
        $batchCourses = BatchCourse::where('batch_id', $batch->id)->with('course')->get();

        $rows = [];
        foreach ($students as $bs) {
            $row = [
                'student_id'   => $bs->student->id,
                'student_name' => $bs->student->name,
                'email'        => $bs->student->email,
                'courses'      => [],
            ];

            foreach ($batchCourses as $bc) {
                $sessions = AttendanceSession::whereHas('schedule', fn($q) =>
                    $q->where('batch_course_id', $bc->id)
                )->where('status', 'closed')->pluck('id');

                $total   = $sessions->count();
                $present = AttendanceRecord::where('student_id', $bs->student->id)
                    ->whereIn('attendance_session_id', $sessions)
                    ->where('status', 'present')->count();

                $row['courses'][$bc->course->code] = [
                    'total'      => $total,
                    'present'    => $present,
                    'percentage' => $total > 0 ? round($present / $total * 100, 2) : 0,
                ];
            }

            $rows[] = $row;
        }

        return ['batch' => $batch, 'courses' => $batchCourses, 'rows' => $rows];
    }
}
