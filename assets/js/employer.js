// Employer Dashboard JavaScript
document.addEventListener('DOMContentLoaded', async function () {
    console.log('WorkConnect PH - Employer Dashboard Loaded');

    // ** Authentication Check **
    const isAuthenticated = await Utils.requireRole('employer');
    if (!isAuthenticated) return;
    // ** END Authentication Check **

    // Initialize page manager (using the utility defined in config.js)
    const pageManager = new Utils.PageManager('content');

    // Register pages
    const companyPage = document.getElementById('companyPage');
    const postjobPage = document.getElementById('postjobPage');
    const applicationsPage = document.getElementById('applicationsPage');
    const postedPage = document.getElementById('postedPage');

    pageManager.registerPage('company', companyPage);
    pageManager.registerPage('postjob', postjobPage);
    pageManager.registerPage('applications', applicationsPage);
    pageManager.registerPage('posted', postedPage);

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
            if (pageId === 'posted') {
                loadPostedJobs();
            } else if (pageId === 'applications') {
                loadApplicationsByEmployer();
            }
        });
    });

    // Notification system
    const notifBell = document.getElementById('notifBell');
    const notifModal = Utils.modalManager.register('notifModal');
    const notifBadge = document.getElementById('notifBadge');

    if (notifBell && notifModal) {
        notifBell.addEventListener('click', () => {
            notifModal.open();
        });
    }

    // Mark all as read
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', () => {
            if (notifBadge) {
                notifBadge.textContent = '0';
                notifBadge.style.display = 'none';
            }
            showNotification('All notifications marked as read', 'info');
            notifModal.close(); 
        });
    }

    // Company profile form (remains simulated for now as API is not defined)
    const companyForm = document.getElementById('companyForm');
    const companyMessage = document.getElementById('companyMessage');

    if (companyForm) {
        companyForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            // In a real application, you'd integrate the company profile form here.
            showNotification('Company profile update simulated. API integration pending.', 'info');
        });
    }

    // Logo upload (remains simulated)
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    // ... (existing logo upload logic)


    // --- JOB POSTING LOGIC (Integrated with API) ---
    const postJobForm = document.getElementById('postJobForm');
    const jobMessage = document.getElementById('jobMessage');

    if (postJobForm) {
        postJobForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Note: experience_level is hardcoded as 'mid' for API compatibility
            const jobData = {
                job_title: document.getElementById('jobTitle').value,
                job_description: document.getElementById('jobDesc').value,
                requirements: document.getElementById('jobReq').value,
                // Extracting min/max salary from a range string (e.g., "40,000 - 60,000")
                salary_min: parseFloat(document.getElementById('jobSalary').value.split('-')[0].replace(/[^0-9.]/g, '')),
                salary_max: parseFloat(document.getElementById('jobSalary').value.split('-')[1]?.replace(/[^0-9.]/g, '') || 0),
                location: document.getElementById('jobLocation').value,
                job_type: document.getElementById('jobType').value,
                experience_level: 'mid' 
            };

            // Validation (using IDs from the HTML)
            if (!jobData.job_title || !jobData.job_description || !jobData.requirements ||
                !jobData.salary_min || !jobData.location || !jobData.job_type) {
                Utils.showMessage(jobMessage, 'Please fill in all required fields', 'error');
                return;
            }

            Utils.showMessage(jobMessage, '🔄 Posting job...', 'info');

            try {
                // Call the new API endpoint
                const response = await apiCall('jobs.php?action=post_job', jobData);

                if (response.success) {
                    Utils.showMessage(jobMessage, '✅ Job posted successfully! Awaiting admin approval (status: published).', 'success');
                    postJobForm.reset();
                    loadPostedJobs(); // Refresh the posted jobs list
                } else {
                    Utils.showMessage(jobMessage, `❌ Failed to post job: ${response.message}`, 'error');
                }
            } catch (error) {
                console.error('Error posting job:', error);
                Utils.showMessage(jobMessage, '❌ Network error while posting job.', 'error');
            }
        });
    }

    // --- POSTED JOBS LOGIC ---
    async function loadPostedJobs() {
        const jobsList = document.querySelector('#postedPage .jobs-list');
        jobsList.innerHTML = '<div class="text-center text-muted p-8">Loading your job postings...</div>';

        try {
            const response = await apiCall('jobs.php?action=employer_posted', {}, 'GET');

            if (response.success) {
                displayPostedJobs(response.data);
            } else {
                jobsList.innerHTML = `<div class="empty-state">
                    <i class="bi bi-x-octagon"></i><p>Failed to load jobs: ${response.message}</p>
                </div>`;
            }
        } catch (error) {
            jobsList.innerHTML = '<div class="empty-state"><i class="bi bi-cloud-slash"></i><p>Network error loading posted jobs.</p></div>';
            console.error('Error loading posted jobs:', error);
        }
    }
    
    function displayPostedJobs(jobs) {
        const jobsList = document.querySelector('#postedPage .jobs-list');
        if (!jobs || jobs.length === 0) {
             jobsList.innerHTML = `<div class="empty-state">
                <i class="bi bi-briefcase"></i>
                <p>No jobs posted yet.</p>
            </div>`;
            return;
        }

        jobsList.innerHTML = jobs.map(job => {
            const statusClass = job.status === 'published' ? 'active' : 'pending';
            const applicationsText = `View Applications (${job.applications_count || 0})`;
            return `
                <div class="job-item" data-job-id="${job.job_id}">
                    <div class="job-header">
                        <h4>${Utils.escapeHTML(job.job_title)}</h4>
                        <span class="job-status ${statusClass}">${Utils.escapeHTML(job.status)}</span>
                    </div>
                    <div class="job-details">
                        <span><i class="bi bi-geo-alt"></i> ${Utils.escapeHTML(job.location)}</span>
                        <span><i class="bi bi-cash"></i> ${Utils.formatCurrency(job.salary_min)} - ${Utils.formatCurrency(job.salary_max)}</span>
                        <span><i class="bi bi-clock"></i> ${Utils.getJobTypeLabel(job.job_type)}</span>
                    </div>
                    <div class="job-actions">
                        <button class="btn btn-primary btn-sm view-applications-btn">${applicationsText}</button>
                        <button class="btn btn-secondary btn-sm">Edit</button>
                        <button class="btn btn-danger btn-sm delete-job-btn">Delete</button>
                    </div>
                </div>
            `;
        }).join('');
        
        // Re-attach listeners for dynamically generated buttons
        jobsList.querySelectorAll('.delete-job-btn').forEach(btn => {
            btn.addEventListener('click', handleDeleteJob);
        });
        jobsList.querySelectorAll('.view-applications-btn').forEach(btn => {
            btn.addEventListener('click', handleViewApplicationsClick);
        });
    }
    
    // Placeholder function for handling job deletion
    function handleDeleteJob(e) {
        const jobItem = e.target.closest('.job-item');
        const jobId = jobItem.getAttribute('data-job-id');
        if (window.confirm(`Are you sure you want to delete job ID ${jobId}?`)) {
            // Placeholder API call for DELETE request
            showNotification(`Job ${jobId} deletion simulated.`, 'error');
            jobItem.remove();
            // loadPostedJobs(); // Reload after API success
        }
    }
    
    // Handle switching to the applications page filtered by job ID
    function handleViewApplicationsClick(e) {
        const jobId = e.target.closest('.job-item').getAttribute('data-job-id');
        pageManager.showPage('applications');
        // Update sidebar active state manually
        document.querySelector('[data-page="applications"]').classList.add('active-link');
        document.querySelector('[data-page="posted"]').classList.remove('active-link');
        loadApplicationsByEmployer(jobId);
    }
    
    // --- APPLICATIONS VIEW LOGIC ---
    async function loadApplicationsByEmployer(jobId = null) {
        const container = document.querySelector('#applicationsPage .applications-container');
        container.innerHTML = '<div class="text-center text-muted p-8">Loading job applications...</div>';
        
        try {
            // Note: Fetching all employer applications for UI side filtering
            const response = await apiCall(CONFIG.ENDPOINTS.APPLICATIONS.EMPLOYER_APPLICATIONS, {}, 'GET');

            if (response.success) {
                displayEmployerApplications(response.data, jobId);
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
    
    function displayEmployerApplications(applications, filterJobId) {
        const container = document.querySelector('#applicationsPage .applications-container');
        
        if (!applications || applications.length === 0) {
             container.innerHTML = `<div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No applications received yet.</p>
            </div>`;
            return;
        }

        // Group applications by job title
        const grouped = applications.reduce((acc, app) => {
            // Apply filter if job ID is provided
            if (filterJobId && app.job_id != filterJobId) return acc;
            
            if (!acc[app.job_title]) {
                acc[app.job_title] = { title: app.job_title, apps: [] };
            }
            acc[app.job_title].apps.push(app);
            return acc;
        }, {});
        
        const folderHTML = Object.values(grouped).map(group => {
            const applicants = group.apps.map(app => `
                <div class="applicant" data-application-id="${app.application_id}">
                    <div class="app-left">
                        <div class="app-name">${Utils.escapeHTML(app.first_name)} ${Utils.escapeHTML(app.last_name)}</div>
                        <div class="app-email">${Utils.escapeHTML(app.email)}</div>
                        <div class="status ${app.status}">${Utils.getStatusLabel(app.status)}</div>
                    </div>
                    <div class="app-actions">
                        <button class="btn btn-primary btn-sm">View Resume</button>
                        <button class="btn btn-secondary btn-sm update-status-btn" data-status="${app.status}">Update Status</button>
                    </div>
                </div>
            `).join('');

            return `
                <div class="folder">
                    <div class="folder-head">
                        <div class="folder-title">${Utils.escapeHTML(group.title)}</div>
                        <div class="folder-count">${group.apps.length} applications</div>
                        <i class="bi bi-chevron-down" style="transition: transform 0.3s;"></i>
                    </div>
                    <div class="folder-body" style="display: none;">
                        ${applicants}
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = folderHTML;
        
        // Re-attach folder toggles
        document.querySelectorAll('.folder-head').forEach(head => {
            head.addEventListener('click', handleFolderToggle);
        });
        
        // Re-attach update status listeners
        document.querySelectorAll('.update-status-btn').forEach(btn => {
            btn.addEventListener('click', handleUpdateStatusClick);
        });
        
        // If a filter was applied, automatically expand the first folder
        if (filterJobId && Object.keys(grouped).length > 0) {
            const firstFolderHead = container.querySelector('.folder-head');
            if (firstFolderHead) {
                firstFolderHead.click(); // Simulate click to expand
            }
        }
    }
    
    function handleFolderToggle() {
        const folderBody = this.nextElementSibling;
        const isVisible = folderBody.style.display === 'block';

        folderBody.style.display = isVisible ? 'none' : 'block';

        const icon = this.querySelector('.bi-chevron-down');
        if (icon) {
            icon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }
    
    function handleUpdateStatusClick(e) {
        const appElement = e.target.closest('.applicant');
        const appId = appElement.getAttribute('data-application-id');
        const currentStatus = e.target.getAttribute('data-status');
        
        // Simulating a status update modal using window.prompt (since custom modals are complex)
        const newStatus = window.prompt(`Update Application ID ${appId} (Current: ${currentStatus}). Enter new status (e.g., submitted, under_review, shortlisted, interview, rejected, accepted):`);
        
        if (newStatus && Object.keys(CONFIG.APPLICATION_STATUS).includes(newStatus.toLowerCase())) {
            // Perform API call to update application status
            updateApplicationStatus(appId, currentStatus, newStatus.toLowerCase());
        } else if (newStatus !== null) {
            showNotification('Invalid status entered. Please use one of the predefined statuses.', 'error');
        }
    }
    
    async function updateApplicationStatus(appId, oldStatus, newStatus) {
        const data = {
            status: newStatus,
            old_status: oldStatus,
            notes: `Status changed from ${oldStatus} to ${newStatus} by employer.`
        };
        
        try {
            const response = await apiCall(`applications.php?action=update&id=${appId}`, data, 'PUT');
            
            if (response.success) {
                showNotification(`Application ${appId} status updated to ${Utils.getStatusLabel(newStatus)}`, 'success');
                // Reload the application list to show the change
                loadApplicationsByEmployer();
            } else {
                showNotification(`Failed to update status: ${response.message}`, 'error');
            }
        } catch (error) {
            showNotification('Network error during status update.', 'error');
        }
    }

    // Initialize with company profile page
    pageManager.showPage('company');
    document.querySelector('[data-page="company"]').classList.add('active-link');
    
    // Logout functionality: update link to use PHP logout script
    const logoutLink = document.querySelector('.sidebar .logout a');
    if (logoutLink) {
        logoutLink.href = 'logout.php';
    }

    console.log('Employer dashboard initialized successfully');
});
