<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get medical staff with their availability
$staffQuery = "SELECT u.*, r.role_name, ms.availability_status, ms.specialization, ms.license_number
               FROM users u 
               JOIN roles r ON u.role_id = r.role_id 
               JOIN medical_staff ms ON u.user_id = ms.user_id
               WHERE r.role_name IN ('Doctor', 'Nurse')
               ORDER BY r.role_name, ms.availability_status, u.last_name";
$staff = $conn->query($staffQuery)->fetch_all(MYSQLI_ASSOC);

// Get staff schedules
$scheduleQuery = "SELECT mss.*, ms.user_id 
                 FROM medical_staff_schedule mss
                 JOIN medical_staff ms ON mss.staff_id = ms.staff_id
                 ORDER BY mss.day_of_week, mss.start_time";
$schedules = $conn->query($scheduleQuery)->fetch_all(MYSQLI_ASSOC);

// Organize schedules by user_id
$staffSchedules = [];
foreach ($schedules as $schedule) {
    $staffSchedules[$schedule['user_id']][] = $schedule;
}

// Pass the role to be used in the header
$role = $_SESSION['role_name'];

require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="../staff/styles/staff.css">
<div class="staff-availability">
    <div class="section-header">
        <h2>Medical Staff Availability</h2>
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
            // Fetch and display schedule
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

<?php require_once '../../includes/footer.php'; ?>