// Job Seeker Dashboard JavaScript
document.addEventListener('DOMContentLoaded', async function () {
    console.log('WorkConnect PH - Job Seeker Dashboard Loaded');

    // ** Authentication Check **
    const isAuthenticated = await Utils.requireRole('job_seeker');
    if (!isAuthenticated) return;
    // ** END Authentication Check **

    // Initialize page manager (using the utility defined in config.js)
    const pageManager = new Utils.PageManager('content');

    // Register pages
    const profilePage = document.getElementById('profilePage');
    const jobSearchPage = document.getElementById('jobSearchPage');
    const applicationsPage = document.getElementById('applicationsPage');

    pageManager.registerPage('profile', profilePage);
    pageManager.registerPage('jobsearch', jobSearchPage);
    pageManager.registerPage('applications', applicationsPage);

    // Sidebar navigation
    const sidebarItems = document.querySelectorAll('#sidebarMenu li[data-page]');
    sidebarItems.forEach(item => {
        item.addEventListener('click', () => {
            // Update active states
            sidebarItems.forEach(i => i.classList.remove('active-link'));
            item.classList.add('active-link');

            const pageId = item.getAttribute('data-page');
            pageManager.showPage(pageId);
            
            // ** Dynamic Content Loading **
            if (pageId === 'jobsearch') {
                loadAllJobs();
            } else if (pageId === 'applications') {
                loadMyApplications();
            }
        });
    });

    // Notification bell (requires 'notificationModal' in HTML, which I added in previous step)
    const notificationBell = document.getElementById('notificationBell');
    const notificationModal = Utils.modalManager.register('notificationModal');

    if (notificationBell && notificationModal) {
        notificationBell.addEventListener('click', () => {
            notificationModal.open();
        });
    }

    // Apply modal
    const applyModal = Utils.modalManager.register('applyModal');

    // --- JOB SEARCH & APPLICATION LOGIC ---

    async function loadAllJobs(filters = {}) {
        const jobsListContainer = document.getElementById('jobsList');
        jobsListContainer.innerHTML = '<div class="text-center text-muted p-8">Loading jobs...</div>';

        try {
            const params = new URLSearchParams(filters).toString();
            const endpoint = `${CONFIG.ENDPOINTS.JOBS.SEARCH}&${params}`;
            
            // Note: Since apiCall is a global function defined in config.js (which uses API_BASE), 
            // we should only pass the part of the URL *after* API_BASE, or use relative path if possible.
            // Adjusting API call structure for clarity using the full endpoint structure:
            const response = await apiCall(endpoint, {}, 'GET'); 

            if (response.success) {
                displayJobs(response.data);
            } else {
                jobsListContainer.innerHTML = `<div class="empty-state">
                    <i class="bi bi-x-octagon"></i><p>${response.message}</p>
                </div>`;
            }
        } catch (error) {
            jobsListContainer.innerHTML = '<div class="empty-state"><i class="bi bi-cloud-slash"></i><p>Failed to load jobs due to network error.</p></div>';
            console.error('Error loading jobs:', error);
        }
    }
    
    function displayJobs(jobs) {
        const jobsListContainer = document.getElementById('jobsList');
        if (!jobs || jobs.length === 0) {
             jobsListContainer.innerHTML = `<div class="empty-state">
                <i class="bi bi-search"></i>
                <p>No jobs found matching your criteria.</p>
            </div>`;
            return;
        }

        jobsListContainer.innerHTML = jobs.map(job => `
            <div class="job-card">
                <div class="job-info">
                    <h4>${Utils.escapeHTML(job.job_title)}</h4>
                    <p>${Utils.escapeHTML(job.company_name)} • ${Utils.escapeHTML(job.location)} • ${Utils.getJobTypeLabel(job.job_type)}</p>
                    <p class="salary">${Utils.formatCurrency(job.salary_min)} - ${Utils.formatCurrency(job.salary_max)} / month</p>
                    <p class="requirements">Category: ${Utils.escapeHTML(job.category_name)}</p>
                </div>
                <button class="btn btn-primary apply-btn" data-job-id="${job.job_id}" data-job-title="${Utils.escapeHTML(job.job_title)}">Apply</button>
            </div>
        `).join('');
        
        // Re-attach apply button listeners
        const newApplyButtons = jobsListContainer.querySelectorAll('.apply-btn');
        newApplyButtons.forEach(btn => btn.addEventListener('click', handleApplyClick));
    }
    
    function handleApplyClick() {
        const jobId = this.getAttribute('data-job-id');
        const jobTitle = this.getAttribute('data-job-title');

        document.getElementById('applyJobId').value = jobId;
        document.getElementById('applyJobTitle').value = jobTitle;
        document.getElementById('applyModalTitle').textContent = `Apply for ${jobTitle}`;

        applyModal.open();
    }
    
    // Apply form submission
    const applyForm = document.getElementById('applyForm');
    if (applyForm) {
        applyForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const jobId = document.getElementById('applyJobId').value;
            const resumeFile = document.getElementById('applyResume').value; 
            const coverLetter = document.getElementById('coverLetter').value;

            if (!resumeFile) {
                showNotification('Please choose a resume to apply.', 'error');
                return;
            }
            
            // Note: The form lacks fields for salary and availability, using placeholders
            const applicationData = {
                job_id: jobId,
                cover_letter: coverLetter,
                expected_salary: 0, 
                availability_date: new Date().toISOString().split('T')[0] 
            };
            
            const response = await apiCall(CONFIG.ENDPOINTS.JOBS.APPLY, applicationData);

            if (response.success) {
                showNotification('Application submitted successfully!', 'success');
                applyModal.close();
                applyForm.reset();

                const applyBtn = document.querySelector(`[data-job-id="${jobId}"]`);
                if (applyBtn) {
                    applyBtn.textContent = 'Applied';
                    applyBtn.disabled = true;
                    applyBtn.classList.remove('btn-primary');
                    applyBtn.classList.add('btn-secondary');
                }
                // Reload applications list if currently viewing it
                if (applicationsPage.classList.contains('active')) {
                    loadMyApplications();
                }
            } else {
                showNotification(`Failed to submit application: ${response.message}`, 'error');
            }
        });
    }

    // --- APPLICATION STATUS LOGIC ---
    
    async function loadMyApplications() {
        const container = document.querySelector('.applications-container');
        container.innerHTML = '<div class="text-center text-muted p-8">Loading applications...</div>';
        
        try {
            const response = await apiCall(CONFIG.ENDPOINTS.APPLICATIONS.MY_APPLICATIONS, {}, 'GET');

            if (response.success) {
                displayApplications(response.data);
                animateStatusBars();
            } else {
                container.innerHTML = `<div class="empty-state">
                    <i class="bi bi-x-octagon"></i><p>Failed to load applications: ${response.message}</p>
                </div>`;
            }
        } catch (error) {
            container.innerHTML = '<div class="empty-state"><i class="bi bi-cloud-slash"></i><p>Network error loading applications.</p></div>';
            console.error('Error loading applications:', error);
        }
    }
    
    function displayApplications(applications) {
        const container = document.querySelector('.applications-container');
        if (!applications || applications.length === 0) {
             container.innerHTML = `<div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>You haven't submitted any applications yet.</p>
            </div>`;
            return;
        }

        container.innerHTML = applications.map(app => {
            const statusLabel = Utils.getStatusLabel(app.status);
            const statusClass = app.status;
            let progress = 0;
            let note = 'Application submitted.';
            
            if (statusClass === 'submitted') progress = 30;
            if (statusClass === 'under_review') { progress = 50; note = 'Application under review by employer.'; }
            if (statusClass === 'shortlisted') { progress = 75; note = 'You have been shortlisted!'; }
            if (statusClass === 'interview') { progress = 100; note = 'Interview scheduled. Check your messages.'; }
            if (statusClass === 'rejected') { progress = 100; note = 'Application was rejected.'; }
            if (statusClass === 'accepted') { progress = 100; note = 'Congratulations! You received an offer.'; }
            
            return `
                <div class="status-card ${statusClass}">
                    <div class="status-header">
                        <h4>${Utils.escapeHTML(app.job_title)} - ${Utils.escapeHTML(app.company_name)}</h4>
                        <span class="status-badge ${statusClass}">${statusLabel}</span>
                    </div>
                    <div class="status-bar" data-progress="${progress}">
                        <span>${progress}% Complete</span>
                    </div>
                    <p class="status-note">${note}</p>
                    <p class="status-note text-xs text-muted">Applied on: ${Utils.formatDate(app.applied_at)}</p>
                </div>
            `;
        }).join('');
        
    }


    // --- PROFILE & FILE UPLOAD LOGIC ---
    
    // Profile form (using existing simulation logic)
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        // Populate CSRF token from meta tag for forms
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfInput = document.getElementById('csrf_token_profile');
        if (csrfMeta && csrfInput) csrfInput.value = csrfMeta.getAttribute('content');

        profileForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const firstName = document.getElementById('profileFirstName').value.trim();
            const lastName = document.getElementById('profileLastName').value.trim();
            const email = document.getElementById('profileEmail').value.trim();
            const skills = document.getElementById('profileSkills').value.trim();
            const csrfToken = document.getElementById('csrf_token_profile').value;

            // Basic validation
            if (!firstName || !lastName || !email) {
                showNotification('Please fill in your name and email.', 'error');
                return;
            }

            // Upload files first if present (profile picture and resume)
            try {
                // Profile picture
                const profileFileInput = document.getElementById('profileInput');
                if (profileFileInput && profileFileInput.files && profileFileInput.files.length > 0) {
                    const fd = new FormData();
                    fd.append('profile_picture', profileFileInput.files[0]);
                    fd.append('csrf_token', csrfToken);
                    fd.append('action', 'upload_profile_picture');
                    // Prefer modern apiService if available, otherwise fallback to fetch
                    if (window.apiService && typeof window.apiService.upload === 'function') {
                        await window.apiService.upload('upload.php?action=upload_profile_picture', fd);
                    } else {
                        // Legacy fallback: multipart POST to api/upload.php?action=upload_profile_picture
                        const res = await fetch('api/upload.php?action=upload_profile_picture', {
                            method: 'POST',
                            credentials: 'include',
                            body: fd
                        });
                        const j = await res.json();
                        if (!res.ok || !j.success) throw new Error(j.message || 'Upload failed');
                    }
                }

                // Resume
                const resumeInput = document.getElementById('resumeUpload');
                if (resumeInput && resumeInput.files && resumeInput.files.length > 0) {
                    const fd2 = new FormData();
                    fd2.append('resume', resumeInput.files[0]);
                    fd2.append('csrf_token', csrfToken);
                    fd2.append('action', 'upload_resume');
                    // There is currently no dedicated resume upload handler in API/upload.php; keep this as placeholder
                    // await apiService.upload('upload.php?action=upload_resume', fd2);
                }
            } catch (err) {
                console.error('File upload error:', err);
                showNotification('File upload failed. Profile not saved.', 'error');
                return;
            }

            // Now save profile via API (PUT)
            const payload = {
                csrf_token: csrfToken,
                first_name: firstName,
                last_name: lastName,
                email: email,
                skills: skills
            };

            try {
                showLoading();
                const response = await apiCall('profiles.php?action=save_job_seeker', payload, 'PUT');
                hideLoading();
                if (response.success) {
                    showNotification('Profile updated successfully!', 'success');
                } else {
                    showNotification(response.message || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                hideLoading();
                console.error('Profile save error:', err);
                showNotification('Network error while saving profile.', 'error');
            }
        });
    }

    // File upload handling (profile picture & resume) remains simulated.

    // Job search functionality remains simulated (searchJobs filters UI, not API yet)
    const jobSearchInput = document.getElementById('jobSearchInput');
    if (jobSearchInput) {
        const debouncedSearch = Utils.debounce(function (e) {
            const query = e.target.value.trim();
            // Note: This currently only filters the UI cards. To filter the API, 
            // you would call loadAllJobs({ q: query }) here.
            if (query.length >= 2) {
                 // loadAllJobs({ keyword: query }); // Use this for API filtering
                 searchJobs(query); // Keeping existing UI filter for simplicity
            } else {
                resetJobSearch();
            }
        }, 300);

        jobSearchInput.addEventListener('input', debouncedSearch);
    }
    
    // UI Filtering (retained from original JS)
    function searchJobs(query) {
        const jobCards = document.querySelectorAll('.job-card');
        // ... (existing searchJobs logic)
    }

    function resetJobSearch() {
        // ... (existing resetJobSearch logic)
    }

    // Animate status bars (adjusted for new status-bar rendering)
    function animateStatusBars() {
        const statusBars = document.querySelectorAll('.status-bar');
        statusBars.forEach(bar => {
            // Get progress and status for dynamic styling
            const progress = bar.getAttribute('data-progress');
            const statusClass = bar.closest('.status-card').classList.contains('pending') ? 'pending' : 
                              (bar.closest('.status-card').classList.contains('shortlisted') ? 'shortlisted' : 'submitted');

            // Find the correct color based on status
            let color = 'var(--info)';
            if (statusClass === 'pending' || statusClass === 'under_review') color = 'var(--warning)';
            if (statusClass === 'shortlisted' || statusClass === 'accepted' || statusClass === 'interview') color = 'var(--success)';
            if (statusClass === 'rejected') color = 'var(--danger)';

            // Clear existing content and create the inner bar element
            bar.style.overflow = 'hidden';
            bar.innerHTML = '<div style="height: 100%; border-radius: inherit; transition: width 0.8s ease-out;"></div>';
            const progressBar = bar.querySelector('div');
            progressBar.style.backgroundColor = color;
            
            // Animate width
            setTimeout(() => {
                progressBar.style.width = progress + '%';
            }, 100);
        });
    }

    // Initialize with profile page (and load its content if applicable)
    pageManager.showPage('profile');
    
    // Load job data on initialization of Job Search
    // NOTE: We rely on click event on sidebar now, but running once for setup might be good practice if default page is not profile.
    // For now, let's keep it clean and rely on sidebar click.

    // Logout functionality: update link to use PHP logout script
    const logoutLink = document.querySelector('.sidebar .logout a');
    if (logoutLink) {
        logoutLink.href = 'logout.php';
    }

    console.log('Job seeker dashboard initialized successfully');
});
