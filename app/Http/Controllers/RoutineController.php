<?php

namespace App\Http\Controllers;

use Smalot\PdfParser\Parser;
use App\Models\Schedule;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    public function importSchedule()
    {
        $pdfPath = storage_path('app/pdfs/class_routine.pdf');
        if (!file_exists($pdfPath)) {
            return response()->json(['error' => 'PDF not found at ' . $pdfPath], 404);
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        // Process the PDF text
        $lines = explode("\n", $text);
        $timeSlots = [];
        $data = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extract time slots from the header row
            if ($index === 1) {
                $columns = array_map('trim', explode(' ', $line));
                $timeSlots = array_slice($columns, 1);
                continue;
            }

            if($index >= 2 ) {
                $columns = array_map('trim', explode(' ', $line));
                $day = trim($columns[0]);
                $classes = array_slice($columns, 1,);

//                dd($timeSlots);

                foreach ($timeSlots as $slotIndex => $timeSlot) {
                    if (isset($classes[$slotIndex]) && $classes[$slotIndex] !== 'Break') {
                        $data[] = [
                            'day' => $day,
                            'time' => $timeSlots[$slotIndex],
                            'class' => $classes[$slotIndex],
                        ];
                    }
                }

            }
        }

        // Store in database
        foreach ($data as $item) {
            Schedule::updateOrCreate(
                ['day' => $item['day'], 'time' => $item['time']],
                ['class' => $item['class']]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Schedule imported successfully']);
    }

    public function getSchedule(Request $request)
    {
        $day = $request->input('day');
        if (!$day) {
            return response()->json(['error' => 'Day parameter is required'], 400);
        }

        $day = ucfirst(strtolower($day));
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if (!in_array($day, $validDays)) {
            return response()->json(['error' => 'Invalid day. Must be Monday, Tuesday, Wednesday, Thursday, or Friday'], 400);
        }

        $schedule = Schedule::where('day', $day)->whereNotNull('class')->get();

        if ($schedule->isEmpty()) {
            return response()->json(['error' => 'No schedule found for ' . $day], 404);
        }

        return response()->json([
            'status' => 'success',
            'day' => $day,
            'schedule' => $schedule->map(function ($item) {
                return [
                    'time' => $item->time,
                    'class' => $item->class,
                ];
            }),
        ]);
    }
}
