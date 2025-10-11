// Home page functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeHomePage();
});

function initializeHomePage() {
    // Initialize search form
    const searchForm = document.getElementById('jobSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearchSubmit);
    }

    // Initialize save job buttons
    initializeSaveJobButtons();

    // Initialize modal for guest users
    initializeGuestModal();
}

function handleSearchSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }

    window.location.href = `job-search.php?${params.toString()}`;
}

function initializeSaveJobButtons() {
    const saveButtons = document.querySelectorAll('.save-job');

    saveButtons.forEach(button => {
        button.addEventListener('click', function () {
            const jobId = this.getAttribute('data-job-id');
            saveJob(jobId, this);
        });
    });
}

async function saveJob(jobId, button) {
    try {
        const response = await apiCall(CONFIG.ENDPOINTS.JOBS.SAVE, { job_id: jobId });

        if (response.success) {
            // Update button state
            button.innerHTML = '<i class="bi bi-bookmark-check"></i> Saved';
            button.classList.add('saved');
            button.disabled = true;
        }
    } catch (error) {
        console.error('Error saving job:', error);
    }
}

function initializeGuestModal() {
    const modal = document.getElementById('modal');
    const closeButtons = document.querySelectorAll('.close-modal');

    if (modal) {
        // Close modal when clicking overlay or close button
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.setAttribute('aria-hidden', 'true');
            }
        });

        closeButtons.forEach(button => {
            button.addEventListener('click', function () {
                modal.setAttribute('aria-hidden', 'true');
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    }
}

// Featured jobs functionality
async function loadFeaturedJobs() {
    try {
        const response = await apiCall(CONFIG.ENDPOINTS.JOBS.FEATURED, {}, 'GET');

        if (response.success) {
            displayFeaturedJobs(response.data);
        }
    } catch (error) {
        console.error('Error loading featured jobs:', error);
    }
}

function displayFeaturedJobs(jobs) {
    const container = document.getElementById('featuredJobs');

    if (!container || !jobs.length) return;

    container.innerHTML = jobs.map(job => `
        <div class="card job-card">
            <div class="job-header">
                <h3>${Utils.escapeHTML(job.job_title)}</h3>
                <span class="company">${Utils.escapeHTML(job.company_name)}</span>
            </div>
            <div class="job-details">
                <p><i class="bi bi-geo-alt"></i> ${Utils.escapeHTML(job.location)}</p>
                <p><i class="bi bi-cash"></i> ${Utils.formatCurrency(job.salary_min)} - ${Utils.formatCurrency(job.salary_max)}</p>
                <p><i class="bi bi-clock"></i> ${Utils.getJobTypeLabel(job.job_type)}</p>
            </div>
            <div class="job-actions">
                <a href="job-details.php?id=${job.job_id}" class="btn btn-primary">View Details</a>
                ${isJobSeeker() ? `
                    <button class="btn btn-secondary save-job" data-job-id="${job.job_id}">
                        <i class="bi bi-bookmark"></i> Save
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');

    // Re-initialize save buttons for dynamically loaded content
    initializeSaveJobButtons();
}

// Helper function to check if user is job seeker
function isJobSeeker() {
    // This would typically check session or make an API call
    // For now, we'll assume the PHP template handles this
    return document.querySelector('.save-job') !== null;
}

// Load featured jobs on page load if container exists
if (document.getElementById('featuredJobs')) {
    loadFeaturedJobs();
}