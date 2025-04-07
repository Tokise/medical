<?php
require_once '../../../config/db.php';
require_once '../../includes/header.php';

// Get user's role for role-specific demo content
$role = isset($_SESSION['user_id']) ? $user['role_name'] : null;

// Demo steps based on user role
$demoSteps = [
    'Admin' => [
        [
            'element' => '#dashboard',
            'title' => 'Welcome to Admin Dashboard',
            'content' => 'This is your main dashboard where you can monitor system statistics and activities.'
        ],
        [
            'element' => '#userManagement',
            'title' => 'User Management',
            'content' => 'Here you can manage all users in the system, including staff and students.'
        ],
        [
            'element' => '#reports',
            'title' => 'Reports & Statistics',
            'content' => 'Generate and view various reports about system usage and medical records.'
        ]
    ],
    'Doctor' => [
        [
            'element' => '#dashboard',
            'title' => 'Welcome to Doctor Dashboard',
            'content' => 'Monitor your appointments and patient information from here.'
        ],
        [
            'element' => '#appointments',
            'title' => 'Appointments',
            'content' => 'View and manage your upcoming appointments.'
        ],
        [
            'element' => '#prescriptions',
            'title' => 'Prescriptions',
            'content' => 'Create and manage patient prescriptions.'
        ]
    ],
    'Nurse' => [
        [
            'element' => '#dashboard',
            'title' => 'Welcome to Nurse Dashboard',
            'content' => 'Monitor patient vitals and daily activities.'
        ],
        [
            'element' => '#vitals',
            'title' => 'Vital Signs',
            'content' => 'Record and monitor patient vital signs.'
        ],
        [
            'element' => '#walkins',
            'title' => 'Walk-in Patients',
            'content' => 'Manage walk-in patients and emergencies.'
        ]
    ],
    'Teacher' => [
        [
            'element' => '#dashboard',
            'title' => 'Welcome to Teacher Dashboard',
            'content' => 'Access student medical information and records.'
        ],
        [
            'element' => '#medicalHistory',
            'title' => 'Medical History',
            'content' => 'View student medical history and records.'
        ],
        [
            'element' => '#medications',
            'title' => 'Medications',
            'content' => 'Track student medications and schedules.'
        ]
    ],
    'Student' => [
        [
            'element' => '#dashboard',
            'title' => 'Welcome to Student Dashboard',
            'content' => 'View your medical information and appointments.'
        ],
        [
            'element' => '#appointments',
            'title' => 'Appointments',
            'content' => 'Schedule and view your medical appointments.'
        ],
        [
            'element' => '#records',
            'title' => 'Medical Records',
            'content' => 'Access your medical history and records.'
        ]
    ]
];

// Get steps for current user's role
$currentSteps = isset($role) && isset($demoSteps[$role]) ? $demoSteps[$role] : [];
?>

<!-- Demo Tutorial Modal -->
<div class="modal" id="demoModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Welcome to MedMS</h2>
            <button class="close-btn" onclick="closeDemoModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Would you like to take a quick tour of the system?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeDemoModal()">Skip Tour</button>
            <button class="btn-primary" onclick="startTour()">Start Tour</button>
        </div>
    </div>
</div>

<!-- Add Intro.js for the tutorial -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/5.1.0/introjs.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/5.1.0/intro.min.js"></script>

<script>
// Demo steps for the current user
const demoSteps = <?= json_encode($currentSteps) ?>;

// Function to start the tour
function startTour() {
    closeDemoModal();
    
    const intro = introJs();
    intro.setOptions({
        steps: demoSteps,
        exitOnOverlayClick: false,
        showStepNumbers: true,
        showBullets: true,
        showProgress: true,
        disableInteraction: false
    });
    
    intro.start().oncomplete(() => {
        // Update demo state when tour is completed
        fetch('/medical/api/update_demo_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ completed: true })
        });
    });
}

// Function to show demo modal
function showDemoModal() {
    document.getElementById('demoModal').style.display = 'flex';
}

// Function to close demo modal
function closeDemoModal() {
    document.getElementById('demoModal').style.display = 'none';
}

// Show demo modal automatically if it's first login
<?php if ($showTutorial): ?>
window.addEventListener('load', showDemoModal);
<?php endif; ?>

// Manual demo trigger
window.startDemoTour = function(event) {
    event.preventDefault();
    showDemoModal();
};
</script>

<?php require_once '../../includes/footer.php'; ?>
