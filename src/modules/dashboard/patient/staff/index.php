<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has staff role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get medical staff with their availability
$staffQuery = "SELECT u.*, r.role_name, 
               CASE 
                 WHEN d.user_id IS NOT NULL THEN d.availability_status 
                 WHEN n.user_id IS NOT NULL THEN n.availability_status 
                 ELSE 'Off-duty'
               END as availability_status,
               CASE 
                 WHEN d.user_id IS NOT NULL THEN d.specialization
                 WHEN n.user_id IS NOT NULL THEN n.specialization
                 ELSE ''
               END as specialization,
               CASE 
                 WHEN d.user_id IS NOT NULL THEN d.license_number
                 WHEN n.user_id IS NOT NULL THEN n.license_number
                 ELSE ''
               END as license_number
               FROM users u 
               JOIN roles r ON u.role_id = r.role_id 
               LEFT JOIN doctors d ON u.user_id = d.user_id
               LEFT JOIN nurses n ON u.user_id = n.user_id
               WHERE r.role_name IN ('Doctor', 'Nurse')
               ORDER BY r.role_name, 
               CASE 
                 WHEN d.user_id IS NOT NULL THEN d.availability_status 
                 WHEN n.user_id IS NOT NULL THEN n.availability_status 
                 ELSE 'Off-duty'
               END, u.last_name";
$staff = $conn->query($staffQuery)->fetch_all(MYSQLI_ASSOC);

// Get staff schedules
$scheduleQuery = "SELECT ms.*, ms.user_id 
                 FROM medical_schedule ms
                 JOIN users u ON ms.user_id = u.user_id
                 JOIN roles r ON u.role_id = r.role_id
                 WHERE r.role_name IN ('Doctor', 'Nurse')
                 ORDER BY ms.day_of_week, ms.start_time";
$schedules = $conn->query($scheduleQuery)->fetch_all(MYSQLI_ASSOC);

// Organize schedules by user_id
$staffSchedules = [];
foreach ($schedules as $schedule) {
    $staffSchedules[$schedule['user_id']][] = $schedule;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Staff - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/staff.css">
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="staff-availability">
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Our Medical Staff</h1>
                <p>Meet our team of healthcare professionals dedicated to providing excellent care and service.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/medical-team.svg" alt="Medical Team" onerror="this.src='/medical/assets/img/default-banner.png'">
            </div>
        </div>
        
        <div class="section-header">
            <h2 class="section-title">Medical Staff Availability</h2>
            <div class="header-actions">
                <div class="filter">
                    <div class="search-box">
                        <input type="text" id="staffSearch" placeholder="Search staff...">
                        <i class="fas fa-search"></i>
                    </div>
                    <select id="availabilityFilter">
                        <option value="">All Status</option>
                        <option value="Available">Available</option>
                        <option value="Busy">Busy</option>
                        <option value="Off-duty">Off-duty</option>
                    </select>
                    <select id="roleFilter">
                        <option value="">All Staff</option>
                        <option value="Doctor">Doctors</option>
                        <option value="Nurse">Nurses</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="staff-grid">
            <?php if (count($staff) > 0): ?>
                <?php foreach ($staff as $member): ?>
                <div class="staff-card" 
                    data-role="<?= strtolower($member['role_name']) ?>"
                    data-status="<?= strtolower($member['availability_status']) ?>">
                    <div class="staff-header">
                        <img src="<?= !empty($member['profile_image']) ? htmlspecialchars($member['profile_image']) : '/medical/assets/img/user-icon.png' ?>" 
                            alt="Profile" class="staff-avatar">
                        <span class="status-indicator <?= strtolower($member['availability_status']) ?>"></span>
                    </div>
                    <div class="staff-info">
                        <h3 class="staff-name"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h3>
                        <span class="staff-role"><?= $member['role_name'] ?></span>
                        <p class="staff-specialization"><?= htmlspecialchars($member['specialization']) ?></p>
                        <span class="status-badge <?= strtolower($member['availability_status']) ?>">
                            <?= $member['availability_status'] ?>
                        </span>
                    </div>
                    <div class="staff-schedule">
                        <h4>Schedule Today</h4>
                        <?php 
                        $today = date('l');
                        $todaySchedule = array_filter($staffSchedules[$member['user_id']] ?? [], function($sch) use ($today) {
                            return $sch['day_of_week'] === $today;
                        });
                        if (!empty($todaySchedule)): 
                            foreach ($todaySchedule as $schedule):
                        ?>
                            <div class="schedule-time">
                                <i class="far fa-clock"></i>
                                <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                            </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <div class="no-schedule">Not scheduled for today</div>
                        <?php endif; ?>
                    </div>
                    <button class="view-schedule-btn" data-userid="<?= $member['user_id'] ?>">
                        View Full Schedule
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <h4 class="empty-title">No Staff Found</h4>
                    <p class="empty-description">There are currently no medical staff members in the system.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Schedule Modal -->
        <div class="modal" id="scheduleModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Weekly Schedule</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="weekly-schedule">
                        <!-- Schedule will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const staffSearch = document.getElementById('staffSearch');
        const availabilityFilter = document.getElementById('availabilityFilter');
        const roleFilter = document.getElementById('roleFilter');
        const staffCards = document.querySelectorAll('.staff-card');
        const modal = document.getElementById('scheduleModal');
        const closeModal = modal.querySelector('.modal-close');

        function filterStaff() {
            const searchTerm = staffSearch.value.toLowerCase();
            const availabilityValue = availabilityFilter.value.toLowerCase();
            const roleValue = roleFilter.value.toLowerCase();

            staffCards.forEach(card => {
                const name = card.querySelector('.staff-name').textContent.toLowerCase();
                const status = card.dataset.status;
                const role = card.dataset.role;

                const matchesSearch = name.includes(searchTerm);
                const matchesAvailability = !availabilityValue || status === availabilityValue;
                const matchesRole = !roleValue || role === roleValue;

                card.style.display = matchesSearch && matchesAvailability && matchesRole ? '' : 'none';
            });
        }

        staffSearch.addEventListener('input', filterStaff);
        availabilityFilter.addEventListener('change', filterStaff);
        roleFilter.addEventListener('change', filterStaff);

        // Schedule modal
        document.querySelectorAll('.view-schedule-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userid;
                // Fetch the schedule data
                const weeklySchedule = document.querySelector('.weekly-schedule');
                weeklySchedule.innerHTML = '<div class="loading">Loading schedule...</div>';
                
                // In a real application, you would fetch this data from the server
                // For now, we'll just display a simple placeholder
                setTimeout(() => {
                    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    let scheduleHTML = '';
                    
                    days.forEach(day => {
                        scheduleHTML += `
                            <div class="schedule-day">
                                <div class="schedule-day-header">
                                    <span>${day}</span>
                                </div>
                                ${Math.random() > 0.3 ? `
                                <div class="schedule-slot">
                                    <div class="schedule-time">9:00 AM - 12:00 PM</div>
                                    <div class="schedule-status ${Math.random() > 0.5 ? 'status-free' : 'status-booked'}">
                                        ${Math.random() > 0.5 ? 'Available' : 'Booked'}
                                    </div>
                                </div>
                                <div class="schedule-slot">
                                    <div class="schedule-time">1:00 PM - 5:00 PM</div>
                                    <div class="schedule-status ${Math.random() > 0.5 ? 'status-free' : 'status-booked'}">
                                        ${Math.random() > 0.5 ? 'Available' : 'Booked'}
                                    </div>
                                </div>
                                ` : `<div class="no-schedule">Not scheduled for this day</div>`}
                            </div>
                        `;
                    });
                    
                    weeklySchedule.innerHTML = scheduleHTML;
                }, 500);
                
                modal.classList.add('active');
            });
        });

        closeModal.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    </script>

</body>
</html>