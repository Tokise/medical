document.addEventListener('DOMContentLoaded', function() {
    // Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    const themeIcon = document.getElementById('theme-icon');
    
    // Set initial theme based on system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        htmlElement.classList.add('dark');
        themeIcon.classList.replace('fa-moon', 'fa-sun');
    }
    
    themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        htmlElement.classList.toggle('dark');
        if (htmlElement.classList.contains('dark')) {
            themeIcon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            themeIcon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('theme', 'light');
        }
    });

    // Mobile Sidebar Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Handle Dropdowns
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('button');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        toggle?.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Close all other dropdowns
            dropdowns.forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.querySelector('.dropdown-menu')?.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            menu?.classList.toggle('show');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            dropdowns.forEach(dropdown => {
                dropdown.querySelector('.dropdown-menu')?.classList.remove('show');
            });
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar?.classList.remove('show');
            dropdowns.forEach(dropdown => {
                dropdown.querySelector('.dropdown-menu')?.classList.remove('show');
            });
        }
    });

    // Demo Tutorial
    const startDemoBtn = document.getElementById('start-demo');
    const skipDemoBtn = document.getElementById('skip-demo');
    const demoModal = document.getElementById('demo-modal');
    
    if (startDemoBtn && demoModal) {
        startDemoBtn.addEventListener('click', function() {
            demoModal.style.display = 'none';
            startTutorial();
        });
    }
    
    if (skipDemoBtn && demoModal) {
        skipDemoBtn.addEventListener('click', function() {
            demoModal.style.display = 'none';
            updateDemoState(true);
        });
    }
});

function startTutorial() {
    const userRole = document.body.dataset.userRole;
    const steps = demoSteps[userRole] || [];
    
    const tour = introJs();
    tour.setOptions({
        steps: steps,
        showProgress: true,
        showBullets: false,
        hideNext: true,
        hidePrev: true,
        exitOnOverlayClick: false,
        exitOnEsc: false,
        doneLabel: 'Finish Tour'
    });
    
    tour.start().oncomplete(function() {
        updateDemoState(true);
    });
}

function updateDemoState(completed) {
    fetch('/medical/api/update_demo_state.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            completed: completed
        })
    })
    .then(response => response.json())
    .catch(error => console.error('Error updating demo state:', error));
}

// Show mobile menu toggle on smaller screens
function handleResize() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    if (window.innerWidth <= 768) {
        menuToggle.style.display = 'block';
    } else {
        menuToggle.style.display = 'none';
        document.querySelector('.sidebar').classList.remove('show');
    }
}

window.addEventListener('resize', handleResize);
handleResize();