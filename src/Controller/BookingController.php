<?php
require_once __DIR__ . '/../Utils/Sanitize.php';

/**
 * Booking Controller
 * Handles all API requests for /api/bookings
 */
class BookingController extends BaseController {

    private $bookingModel;

    public function __construct($db) {
        // All controllers must call the parent constructor
        // We will add this to AuthController and PetController later
        // parent::__construct();

        $this->bookingModel = new BookingModels($db);
    }

    /**
     * Handles POST /api/bookings/grooming
     */
    public function addGroomingBooking() {
        try {
            $session = $this->authenticate(); // Check login
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_id']) || empty($data['booking_date']) || empty($data['service_type'])) {
                $this->sendError('pet_id, booking_date, and service_type are required.', 400);
                return;
            }

            $bookingData = [
                'user_id' => $userId,
                'pet_id' => (int)$data['pet_id'],
                'doctor_id' => null, // No doctor for grooming
                'booking_type' => 'grooming',
                'booking_date' => $data['booking_date'],
                'service_type' => $data['service_type']
            ];

            $newBookingId = $this->bookingModel->create($bookingData);
            $this->sendResponse(['status' => 'success', 'message' => 'Grooming booked successfully.', 'booking_id' => $newBookingId], 201);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles POST /api/bookings/medical
     */
    public function addMedicalBooking() {
        try {
            $session = $this->authenticate(); // Check login
            $userId = $session['user_id'];

            $data = $this->getRequestData();
            $data = Sanitize::all($data);

            if (empty($data['pet_id']) || empty($data['doctor_id']) || empty($data['booking_date']) || empty($data['service_type'])) {
                $this->sendError('pet_id, doctor_id, booking_date, and service_type are required.', 400);
                return;
            }

            // TODO: Here you should add logic to check if the doctor_id is a valid doctor

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

    /**
     * Handles GET /api/bookings/user
     * Gets all bookings for the currently logged-in user.
     */
    public function getUserBookings() {
        try {
            $session = $this->authenticate();
            $userId = $session['user_id'];

            $bookings = $this->bookingModel->findByUserId($userId);

            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles GET /api/bookings/doctor
     * Gets all bookings for the currently logged-in doctor.
     */
    public function getDoctorBookings() {
        try {
            $session = $this->authenticate();

            // Check if the user is a doctor
            if ($session['user_role'] !== 'doctor') {
                $this->sendError('Forbidden: You must be a doctor to access this resource.', 403);
                return;
            }

            $doctorId = $session['user_id'];
            $bookings = $this->bookingModel->findByDoctorId($doctorId);

            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles GET /api/bookings/all (Admin Only)
     */
    public function getAllBookings() {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden: Admin access required.', 403);
                return;
            }

            $bookings = $this->bookingModel->findAll();
            $this->sendResponse($bookings, 200);

        } catch (Exception $e) {
            $this->sendError("An error occurred: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handles PUT /api/bookings/:id/status (Admin Only)
     */
    public function updateBookingStatus($bookingId) {
        try {
            $session = $this->authenticate();
            if ($session['user_role'] !== 'admin') {
                $this->sendError('Forbidden: Admin access required.', 403);
                return;
            }

            $data = $this->getRequestData();
            $status = Sanitize::string($data['status']);

            if (empty($status) || !in_array($status, ['Pending', 'Confirmed', 'Cancelled', 'Completed'])) {
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
}