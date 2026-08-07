<?php
// Career Application Controller with File Upload

class CareerController {

    public function apply(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $experience = intval($_POST['experience'] ?? 0);
        $jobTitle = trim($_POST['job_title'] ?? 'Faculty Educator');
        $message = trim($_POST['message'] ?? '');

        if (empty($fullName) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Full name, email, and phone number are required fields']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
            return;
        }

        // Validate File Upload
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please upload your resume file (PDF, DOC, DOCX)']);
            return;
        }

        $fileTmpPath = $_FILES['resume']['tmp_name'];
        $fileName = $_FILES['resume']['name'];
        $fileSize = $_FILES['resume']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Allowed formats: PDF, DOC, DOCX']);
            return;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Resume file size must not exceed 5MB']);
            return;
        }

        // Upload directory
        $uploadDir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'resume_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        $relativePath = 'uploads/resumes/' . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded resume file on server']);
            return;
        }

        $userId = $_SESSION['user']['id'] ?? 0;

        // Insert database record
        $stmt = $db->prepare("INSERT INTO career_applications (user_id, full_name, email, phone, experience, job_title, message, resume_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $fullName, $email, $phone, $experience, $jobTitle, $message, $relativePath]);

        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully! Our HR recruitment team will review your resume.',
            'resume_file' => $newFileName
        ]);
    }

    public function listApplications(): void {
        header('Content-Type: application/json');
        $db = getDB();

        $stmt = $db->query("SELECT * FROM career_applications ORDER BY id DESC LIMIT 20");
        $apps = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $apps]);
    }
}
