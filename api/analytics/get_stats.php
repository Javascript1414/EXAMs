<?php
/**
 * Analytics API Endpoint
 * Fetches real-time analytics data for dashboard visualization
 * 
 * GET parameters:
 * - action: get_stats, get_exam_data, get_trends, etc.
 * - date_from: Start date for filtering
 * - date_to: End date for filtering
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check admin authorization
if (!isset($_SESSION['user_id']) || !hasRole('superadmin') && !hasRole('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'get_stats';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

switch ($action) {
    case 'get_stats':
        echo json_encode(getQuickStats());
        break;
    case 'get_completion_data':
        echo json_encode(getCompletionData($date_from, $date_to));
        break;
    case 'get_performance_data':
        echo json_encode(getPerformanceData($date_from, $date_to));
        break;
    case 'get_trends_data':
        echo json_encode(getTrendsData($date_from, $date_to));
        break;
    case 'get_difficulty_data':
        echo json_encode(getDifficultyData());
        break;
    case 'get_ratings_data':
        echo json_encode(getRatingsData());
        break;
    case 'get_exam_metrics':
        echo json_encode(getExamMetrics($date_from, $date_to));
        break;
    case 'get_top_courses':
        echo json_encode(getTopCourses());
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Get quick statistics
 */
function getQuickStats() {
    global $pdo;
    
    try {
        // Total students
        $result = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_name = 'student'");
        $total_students = $result->fetch()['total'] ?? 0;
        
        // Active exams
        $result = $pdo->query("SELECT COUNT(*) as total FROM exams WHERE status = 'active' AND end_time > NOW()");
        $active_exams = $result->fetch()['total'] ?? 0;
        
        // Average score
        $result = $pdo->query("SELECT AVG(obtained_marks) as avg FROM exam_results WHERE obtained_marks > 0");
        $avg_score = round($result->fetch()['avg'] ?? 0, 2);
        
        // Ongoing attempts
        $result = $pdo->query("SELECT COUNT(*) as total FROM exam_attempts WHERE status = 'ongoing'");
        $ongoing_attempts = $result->fetch()['total'] ?? 0;
        
        // Total exams
        $result = $pdo->query("SELECT COUNT(*) as total FROM exams");
        $total_exams = $result->fetch()['total'] ?? 0;
        
        return [
            'success' => true,
            'total_students' => $total_students,
            'active_exams' => $active_exams,
            'avg_score' => $avg_score,
            'ongoing_attempts' => $ongoing_attempts,
            'total_exams' => $total_exams,
            'system_health' => 98
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get exam completion data
 */
function getCompletionData($date_from, $date_to) {
    global $pdo;
    
    try {
        $query = "
            SELECT 
                e.exam_name,
                COUNT(DISTINCT ea.student_id) as attempted,
                COUNT(DISTINCT CASE WHEN ea.status = 'completed' THEN ea.student_id END) as completed
            FROM exams e
            LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id 
                AND DATE(ea.created_at) BETWEEN ? AND ?
            WHERE e.created_at BETWEEN ? AND ?
            GROUP BY e.exam_id, e.exam_name
            ORDER BY completed DESC
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
        $rows = $stmt->fetchAll();
        
        $labels = [];
        $data = [];
        $colors = [];
        
        foreach ($rows as $row) {
            $labels[] = $row['exam_name'];
            $completion_rate = $row['attempted'] > 0 ? round(($row['completed'] / $row['attempted']) * 100, 2) : 0;
            $data[] = $completion_rate;
            $colors[] = 'rgba(' . rand(80, 200) . ', ' . rand(80, 200) . ', 255, 0.7)';
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get student performance distribution
 */
function getPerformanceData($date_from, $date_to) {
    global $conn;
    pdo;
    
    try {
        $ranges = [
            '0-20' => ['min' => 0, 'max' => 20],
            '21-40' => ['min' => 21, 'max' => 40],
            '41-60' => ['min' => 41, 'max' => 60],
            '61-80' => ['min' => 61, 'max' => 80],
            '81-100' => ['min' => 81, 'max' => 100]
        ];
        
        $labels = [];
        $data = [];
        
        foreach ($ranges as $range => $bounds) {
            $query = "
                SELECT COUNT(*) as count 
                FROM exam_results 
                WHERE obtained_marks >= ? AND obtained_marks <= ?
                AND DATE(created_at) BETWEEN ? AND ?
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$bounds['min'], $bounds['max'], $date_from, $date_to]);
            $count = $stmt->fetch()['count'] ?? 0
            $labels[] = $range . '%';
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                'rgba(255, 99, 99, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(75, 192, 75, 0.7)'
            ]
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get activity trends over time
 */
function getTrendsData($date_from, $date_to) {
    global $conn;
    
    try {pdo;
    
    try {
        $query = "
            SELECT 
                DATE(created_at) as date,
                COUNT(DISTINCT CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(DISTINCT CASE WHEN status = 'ongoing' THEN 1 END) as ongoing,
                COUNT(DISTINCT student_id) as active_students
            FROM exam_attempts
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$date_from, $date_to]);
        $rows = $stmt->fetchAll();
        
        $labels = [];
        $completed = [];
        $ongoing = [];
        $active = [];
        
        foreach ($rows as $row;
            $ongoing[] = $row['ongoing'];
            $active[] = $row['active_students'];
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Completed',
                    'data' => $completed,
                    'borderColor' => 'rgba(75, 192, 75, 1)',
                    'backgroundColor' => 'rgba(75, 192, 75, 0.1)',
                    'tension' => 0.4
                ],
                [
                    'label' => 'Ongoing',
                    'data' => $ongoing,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'tension' => 0.4
                ],
                [
                    'label' => 'Active Students',
                    'data' => $active,
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'backgroundColor' => 'rgba(255, 159, 64, 0.1)',
                    'tension' => 0.4
                ]
            ]
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get question difficulty analysis
 */
function getDifficultyData() {
    global $conn;
    
    try {
        $quepdo;
    
    try {
        $query = "
            SELECT 
                difficulty_level,
                COUNT(*) as count,
                AVG(correct_attempts / total_attempts * 100) as avg_success_rate
            FROM exam_questions
            GROUP BY difficulty_level
        ";
        
        $result = $pdo->query($query);
        $rows = $result->fetchAll();
        
        $labels = [];
        $data = [];
        $colors = ['rgba(75, 192, 192, 0.7)', 'rgba(255, 159, 64, 0.7)', 'rgba(255, 99, 99, 0.7)'];
        
        $i = 0;
        foreach ($rows as $row
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get material ratings distribution
 */
function getRatingsData() {
    global $conn;
    
    try {pdo;
    
    try {
        $query = "
            SELECT 
                rating,
                COUNT(*) as count
            FROM material_ratings
            GROUP BY rating
            ORDER BY rating DESC
        ";
        
        $result = $pdo->query($query);
        $rows = $result->fetchAll();
        
        $labels = [];
        $data = [];
        
        foreach ($rows as $row
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => ['rgba(255, 206, 86, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 99, 132, 0.7)']
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get detailed exam metrics
 */
function getpdo;
    
    try {
        $query = "
            SELECT 
                e.exam_id,
                e.exam_name,
                e.total_students,
                COUNT(DISTINCT ea.student_id) as completed,
                ROUND(COUNT(DISTINCT ea.student_id) / e.total_students * 100, 2) as completion_percentage,
                ROUND(AVG(er.obtained_marks), 2) as avg_score,
                ROUND(COUNT(DISTINCT CASE WHEN er.obtained_marks >= e.passing_marks THEN ea.student_id END) / COUNT(DISTINCT ea.student_id) * 100, 2) as pass_rate,
                CASE 
                    WHEN NOW() < e.start_time THEN 'Upcoming'
                    WHEN NOW() BETWEEN e.start_time AND e.end_time THEN 'Active'
                    ELSE 'Completed'
                END as status
            FROM exams e
            LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id AND ea.status = 'completed'
            LEFT JOIN exam_results er ON ea.attempt_id = er.attempt_id
            WHERE DATE(e.created_at) BETWEEN ? AND ?
            GROUP BY e.exam_id
            ORDER BY e.created_at DESC
            LIMIT 20
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$date_from, $date_to]);
        $metrics = $stmt->fetchAll();   $metrics[] = $row;
        }
        
        return ['success' => true, 'data' => $metrics];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get top performing courses
 */
function getTopCourses() {
    global $conn;
    
    try {
        $query = "
            SELECT 
            pdo;
    
    try {
        $query = "
            SELECT 
                c.course_id,
                c.course_name,
                COUNT(DISTINCT ea.student_id) as enrollments,
                ROUND(AVG(er.obtained_marks), 2) as avg_score,
                ROUND(COUNT(DISTINCT CASE WHEN er.obtained_marks >= e.passing_marks THEN ea.student_id END) / COUNT(DISTINCT ea.student_id) * 100, 2) as pass_rate
            FROM courses c
            JOIN exams e ON c.course_id = e.course_id
            LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id
            LEFT JOIN exam_results er ON ea.attempt_id = er.attempt_id
            GROUP BY c.course_id
            ORDER BY avg_score DESC, enrollments DESC
            LIMIT 5
        ";
        
        $result = $pdo->query($query);
        $courses = $result->fetchAll();
}
?>
