<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/ScheduleModels.php';
require_once __DIR__ . '/../models/BookingModels.php';

/**
 * Schedule Controller
 * Handles setting hours and generating time slots for bookings.
 */
class ScheduleController extends BaseController {

    private $scheduleModel;
    private $bookingModel;

    public function __construct($db) {
        $this->scheduleModel = new ScheduleModels($db);
        $this->bookingModel = new BookingModels($db);
    }

    /**
     * POST /api/schedule
     * Sets the schedule for a specific day.
     * Body: { day: 'Monday', start: '09:00', end: '17:00', active: true, doctor_id: 1 (optional) }
     */
    public function setSchedule() {
        try {
            // 1. Authenticate
            $session = $this->authenticate();

            // 2. Get Data
            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            // 3. Determine Target User (Doctor or General/Admin)
            $targetUserId = null;

            if ($session['user_role'] === 'admin') {
                // Admin can set schedule for "Grooming" (null ID) or specific doctors
                if (isset($data['doctor_id']) && !empty($data['doctor_id'])) {
                    $targetUserId = (int)$data['doctor_id'];
                }
            } elseif ($session['user_role'] === 'doctor') {
                // Doctors can only set their own schedule
                $targetUserId = $session['user_id'];
            } else {
                $this->sendError('Forbidden', 403);
                return;
            }

            // 4. Validation
            if (empty($data['day']) || empty($data['start']) || empty($data['end'])) {
                $this->sendError('Day, start time, and end time are required.', 400);
                return;
            }

            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            if (!in_array($data['day'], $validDays)) {
                $this->sendError('Invalid day of week.', 400);
                return;
            }

            // 5. Save Schedule
            $isActive = isset($data['active']) ? (bool)$data['active'] : true;

            $success = $this->scheduleModel->setSchedule(
                $targetUserId,
                $data['day'],
                $data['start'],
                $data['end'],
                $isActive
            );

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Schedule updated.'], 200);
            } else {
                $this->sendError('Failed to update schedule.', 500);
            }

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/schedule?doctor_id=X
     * Gets the full weekly schedule for a provider.
     */
    public function getSchedule() {
        try {
            $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

            // If no doctor_id is provided, we assume it's a request for the Grooming schedule (null)
            $schedule = $this->scheduleModel->getSchedule($doctorId);

            $this->sendResponse($schedule, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/slots?date=YYYY-MM-DD&doctor_id=X
     * Generates available 30-minute time slots for a specific date.
     */
    public function getAvailableSlots() {
        try {
            // 1. Get Query Parameters
            if (!isset($_GET['date'])) {
                $this->sendError('Date parameter is required.', 400);
                return;
            }

            $date = $_GET['date']; // e.g., "2025-11-27"
            $doctorId = isset($_GET['doctor_id']) && $_GET['doctor_id'] !== '' ? (int)$_GET['doctor_id'] : null;

            // 2. Determine Day of Week
            $timestamp = strtotime($date);
            if (!$timestamp) {
                $this->sendError('Invalid date format.', 400);
                return;
            }
            $dayOfWeek = date('l', $timestamp); // e.g., "Thursday"

            // 3. Fetch Master Schedule for that Day
            $daySchedule = $this->scheduleModel->getDaySchedule($doctorId, $dayOfWeek);

            // If no schedule exists or they are marked as closed/inactive
            if (!$daySchedule || !$daySchedule['is_active']) {
                // Return empty array (Shop/Doctor is closed)
                $this->sendResponse([], 200);
                return;
            }

            // 4. Fetch Existing Bookings (The "Greyed Out" slots)
            // We need to know what times are already taken in the 'bookings' table
            $bookedTimes = $this->bookingModel->getBookedTimes($doctorId, $date);

            // 5. Generate 30-minute Intervals
            $slots = [];
            $start = strtotime($date . ' ' . $daySchedule['start_time']);
            $end = strtotime($date . ' ' . $daySchedule['end_time']);
            $interval = 30 * 60; // 30 minutes in seconds

            while ($start < $end) {
                // Format for DB comparison (HH:MM:SS)
                $timeString = date('H:i:s', $start);
                // Format for UI display (9:30 AM)
                $displayTime = date('g:i A', $start);

                // ISO string for the actual value (2025-11-27 09:30:00)
                $fullDateTime = date('Y-m-d H:i:s', $start);

                // Logic: Is this time present in the booked array?
                $isTaken = in_array($timeString, $bookedTimes);

                $slots[] = [
                    'value' => $fullDateTime,
                    'display' => $displayTime,
                    'available' => !$isTaken // If taken, available is false
                ];

                $start += $interval;
            }

            $this->sendResponse($slots, 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}