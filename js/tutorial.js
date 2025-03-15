class TutorialSystem {
    constructor(userRole = 'all') {
        this.currentStep = 0;
        this.tutorials = [];
        this.overlay = null;
        this.modal = null;
        this.userRole = userRole;
    }

    async init() {
        const response = await fetch(`/api/tutorials/get-user-tutorials.php?role=${this.userRole}`);
        this.tutorials = await response.json();
        
        // Only show tutorial if there are incomplete tutorials
        if (this.tutorials.length > 0) {
            this.createTutorialOverlay();
            this.showCurrentStep();
        }
    }

    createTutorialOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'tutorial-overlay';
        
        this.modal = document.createElement('div');
        this.modal.className = 'tutorial-modal';
        
        this.overlay.appendChild(this.modal);
        document.body.appendChild(this.overlay);
    }

    showCurrentStep() {
        const tutorial = this.tutorials[this.currentStep];
        
        this.modal.innerHTML = `
            <div class="tutorial-header">
                <h3>${tutorial.title}</h3>
                <span class="tutorial-role">${tutorial.user_role === 'all' ? 'General' : tutorial.user_role}</span>
            </div>
            <div class="tutorial-content">${tutorial.content}</div>
            <div class="tutorial-progress">
                ${this.createProgressDots()}
            </div>
            <div class="tutorial-buttons">
                ${!tutorial.is_required ? 
                    '<button class="tutorial-button tutorial-skip">Skip Tutorial</button>' : 
                    ''
                }
                <button class="tutorial-button tutorial-next">
                    ${this.currentStep === this.tutorials.length - 1 ? 'Finish' : 'Next'}
                </button>
            </div>
        `;

        this.highlightElement(tutorial.target_element);
        this.attachEventListeners();
    }

    createProgressDots() {
        return this.tutorials
            .map((_, index) => `
                <div class="progress-dot ${index === this.currentStep ? 'active' : ''}">
                </div>
            `)
            .join('');
    }

    highlightElement(selector) {
        const previousHighlight = document.querySelector('.tutorial-highlight');
        if (previousHighlight) {
            previousHighlight.classList.remove('tutorial-highlight');
        }

        if (selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.classList.add('tutorial-highlight');
            }
        }
    }

    attachEventListeners() {
        const skipButton = this.modal.querySelector('.tutorial-skip');
        const nextButton = this.modal.querySelector('.tutorial-next');

        if (skipButton) {
            skipButton.addEventListener('click', () => this.endTutorial());
        }
        nextButton.addEventListener('click', () => this.nextStep());
    }

    async nextStep() {
        // Mark current tutorial as completed before moving to next
        await this.endTutorial();
        
        // If this was the last tutorial, the overlay will already be removed
        if (this.currentStep < this.tutorials.length - 1) {
            this.currentStep++;
            this.showCurrentStep();
        }
    }

    async endTutorial() {
        const currentTutorial = this.tutorials[this.currentStep];
        
        try {
            await fetch('/api/tutorials/mark-completed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tutorial_id: currentTutorial.id
                })
            });

            // Remove completed tutorial from the list
            this.tutorials.splice(this.currentStep, 1);
            
            // If there are more tutorials, show the next one
            if (this.tutorials.length > 0) {
                this.currentStep = Math.min(this.currentStep, this.tutorials.length - 1);
                this.showCurrentStep();
            } else {
                // All tutorials completed
                this.overlay.remove();
            }
        } catch (error) {
            console.error('Failed to mark tutorial as completed:', error);
        }
    }
}

// Initialize tutorial system when document is ready
document.addEventListener('DOMContentLoaded', () => {
    const tutorial = new TutorialSystem();
    tutorial.init();
});
