<?php
require_once __DIR__ . '/../Utils/Sanitize.php';
require_once __DIR__ . '/../models/BookingModels.php';
// We need to fetch user details/schedule sometimes, so good to have access if needed
// require_once __DIR__ . '/../models/UserModels.php';

class BookingController extends BaseController {

    private $bookingModel;

    public function __construct($db) {
        $this->bookingModel = new BookingModels($db);
    }

    // ... (Keep addGroomingBooking, addMedicalBooking as they are) ...

    public function addGroomingBooking() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_id']) || empty($data['booking_date']) || empty($data['service_type'])) {
                $this->sendError('pet_id, booking_date, and service_type are required.', 400);
                return;
            }

            $formattedDate = str_replace('T', ' ', $data['booking_date']);

            $bookingData = [
                'user_id' => $userId,
                'pet_id' => (int)$data['pet_id'],
                'doctor_id' => null,
                'booking_type' => 'grooming',
                'booking_date' => $formattedDate,
                'service_type' => $data['service_type']
            ];

            $newBookingId = $this->bookingModel->create($bookingData);
            $this->sendResponse(['status' => 'success', 'message' => 'Grooming booked successfully.', 'booking_id' => $newBookingId], 201);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function addMedicalBooking() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_id']) || empty($data['doctor_id']) || empty($data['booking_date']) || empty($data['service_type'])) {
                $this->sendError('pet_id, doctor_id, booking_date, and service_type are required.', 400);
                return;
            }

            $bookingData = [
                'user_id' => $userId,
                'pet_id' => (int)$data['pet_id'],
                'doctor_id' => (int)$data['doctor_id'],
                'booking_type' => 'medical',
                'booking_date' => $data['booking_date'],
                'service_type' => $data['service_type']
            ];

            $newBookingId = $this->bookingModel->create($bookingData);
            $this->sendResponse(['status' => 'success', 'message' => 'Appointment booked successfully.', 'booking_id' => $newBookingId], 201);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function getUserBookings() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // Optional: Run auto-update logic here if you implemented it in Model
            // $this->bookingModel->autoUpdateStatuses();

            $bookings = $this->bookingModel->findByUserId($userId);

            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function getDoctorBookings() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'doctor') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $doctorId = $session['user_id'];
            $bookings = $this->bookingModel->findByDoctorId($doctorId);

            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function getAllBookings() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $bookings = $this->bookingModel->findAll();
            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function updateBookingStatus($bookingId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden', 403);
                return;
            }

            $data = $this->getRequestData();
            $status = Sanitize::string($data['status']);

            // Updated valid statuses to include new ones
            $validStatuses = ['Pending', 'Confirmed', 'Checked In', 'Completed', 'Cancelled', 'No Show'];
            if (empty($status) || !in_array($status, $validStatuses)) {
                $this->sendError('Invalid status provided.', 400);
                return;
            }

            $success = $this->bookingModel->updateStatus((int)$bookingId, $status);

            if ($success) {
                $this->sendResponse(['status' => 'success', 'message' => 'Booking status updated.'], 200);
            } else {
                $this->sendError('Could not update booking status.', 404);
            }

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * --- MANUAL CHECK-IN (UPDATED) ---
     * Handles PUT /api/bookings/:id/checkin
     * Enforces the 1-hour window rule.
     */
    public function checkIn($bookingId) {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            // 1. Fetch the booking to verify ownership and time
            $booking = $this->bookingModel->findById($bookingId);

            if (!$booking) {
                $this->sendError('Booking not found.', 404);
                return;
            }

            // Verify ownership
            if ($booking['user_id'] != $userId) {
                $this->sendError('Forbidden: This is not your booking.', 403);
                return;
            }

            // 2. Validate Time Window (1 Hour Rule)
            $bookingTime = strtotime($booking['booking_date']);
            $now = time();
            $oneHourBefore = $bookingTime - 3600; // 1 hour in seconds
            $fifteenMinsAfter = $bookingTime + (15 * 60); // 15 min grace period

            // --- UNCOMMENTED AND ACTIVE LOGIC ---
            if ($now < $oneHourBefore) {
                // Calculate minutes remaining for helpful error message
                $minsWait = ceil(($oneHourBefore - $now) / 60);
                $this->sendError("Too early. Check-in opens in $minsWait minutes.", 400);
                return;
            }

            if ($now > $fifteenMinsAfter) {
                $this->sendError('Too late. Appointment missed.', 400);
                return;
            }
            // ------------------------------------

            if ($booking['status'] !== 'Confirmed') {
                $this->sendError('Booking must be Confirmed before checking in.', 400);
                return;
            }

            // 3. Update Status
            $this->bookingModel->checkIn($bookingId);
            $this->sendResponse(['status' => 'success', 'message' => 'Checked in successfully!'], 200);

        } catch (Exception $e) {
            $this->sendError("Error: " . $e->getMessage(), 500);
        }
    }
}