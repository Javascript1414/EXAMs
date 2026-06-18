<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/certificate_generator.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $cert_id = (int)($_POST['cert_id'] ?? 0);

    if ($id > 0 && $cert_id > 0) {
        if ($action === 'send_email') {
            // Send certificate via email
            $certStmt = $pdo->prepare("
                SELECT c.*, u.email, u.full_name, e.exam_name 
                FROM certificates c 
                JOIN users u ON c.student_id = u.id 
                JOIN exams e ON c.exam_id = e.id 
                WHERE c.id = ?
            ");
            $certStmt->execute([$cert_id]);
            $cert = $certStmt->fetch();

            if ($cert && $cert['status'] === 'active') {
                try {
                    $mail = new PHPMailer(true);
                    
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = getenv('SMTP_USER') ?: 'your-email@gmail.com';
                    $mail->Password = getenv('SMTP_PASS') ?: 'your-password';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Email Details
                    $mail->setFrom('noreply@exams.local', APP_NAME);
                    $mail->addAddress($cert['email'], $cert['full_name']);
                    $mail->Subject = 'Your Certificate - ' . $cert['exam_name'];

                    $verify_url = BASE_URL . '/verify.php?code=' . urlencode($cert['verification_code']);
                    $view_url = BASE_URL . '/student/certificate_view.php?id=' . $cert['result_id'];
                    $download_url = BASE_URL . '/student/certificate_download.php?id=' . $cert['result_id'];

                    $mail->isHTML(true);
                    $mail->Body = "
                    <h2>Certificate of Achievement</h2>
                    <p>Dear {$cert['full_name']},</p>
                    <p>Congratulations! You have successfully passed the <strong>{$cert['exam_name']}</strong> exam with a score of <strong>{$cert['percentage']}%</strong>.</p>
                    <p>Your official certificate is ready. You can:</p>
                    <ul>
                        <li><a href='{$view_url}'>View Certificate</a></li>
                        <li><a href='{$download_url}'>Download as PDF</a></li>
                        <li><a href='{$verify_url}'>Verify Certificate</a></li>
                    </ul>
                    <p><strong>Certificate ID:</strong> {$cert['certificate_id']}</p>
                    <p><strong>Verification Code:</strong> {$cert['verification_code']}</p>
                    <p>Best regards,<br>" . APP_NAME . "</p>
                    ";

                    if ($mail->send()) {
                        $_SESSION['success_message'] = "Certificate sent to {$cert['email']} successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to send email. Please try again.";
                    }
                } catch (Exception $e) {
                    error_log("Email Error: " . $e->getMessage());
                    $_SESSION['error_message'] = "Email service error: " . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Certificate not found or revoked.";
            }
        } elseif ($action === 'release') {
            // Release/Approve Certificate
            $result = $pdo->prepare("
                SELECT r.id, r.student_id, r.exam_id, r.obtained_marks, r.total_marks, r.percentage, r.created_at
                FROM results r 
                WHERE r.id = ? AND r.is_passed = 1
            ");
            $result->execute([$id]);
            $resultData = $result->fetch();

            if ($resultData) {
                // Check if certificate exists
                $certCheck = $pdo->prepare("SELECT id FROM certificates WHERE result_id = ? LIMIT 1");
                $certCheck->execute([$id]);
                $existingCert = $certCheck->fetch();

                if (!$existingCert) {
                    // Use the new certificate generator
                    $result_data = [
                        'obtained_marks' => $resultData['obtained_marks'],
                        'total_marks' => $resultData['total_marks'],
                        'percentage' => $resultData['percentage'],
                        'created_at' => $resultData['created_at']
                    ];
                    
                    $cert_result = insertCertificate($pdo, $resultData['student_id'], $resultData['exam_id'], $id, $result_data, $_SESSION['user_id'] ?? null);
                    
                    if ($cert_result && $cert_result['success']) {
                        $pdo->prepare("UPDATE results SET certificate_generated = 1 WHERE id = ?")->execute([$id]);
                        $_SESSION['success_message'] = "Certificate released successfully! ID: " . $cert_result['certificate_id'];
                    } else {
                        $_SESSION['error_message'] = "Failed to generate certificate.";
                    }
                } else {
                    $_SESSION['error_message'] = "Certificate already exists for this result.";
                }
            } else {
                $_SESSION['error_message'] = "Result not found or not passed.";
            }
        }
    }

    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/admin/certificates.php');
    exit;
}
