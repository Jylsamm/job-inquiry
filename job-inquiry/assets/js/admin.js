// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', async function () {
    console.log('WorkConnect PH - Admin Dashboard Loaded');

    // ** REPLACED Authentication Check with API Call **
    const isAuthenticated = await Utils.requireRole('admin');
    if (!isAuthenticated) return;
    // ** END REPLACEMENT **

    // Initialize page manager (using the utility defined in config.js)
    const pageManager = new Utils.PageManager('content');

    // Register pages
    const usersPage = document.getElementById('usersPage');
    const jobsPage = document.getElementById('jobsPage');
    const reportsPage = document.getElementById('reportsPage');

    pageManager.registerPage('users', usersPage);
    pageManager.registerPage('jobs', jobsPage);
    pageManager.registerPage('reports', reportsPage);

    // Sidebar navigation
    const sidebarItems = document.querySelectorAll('#sidebarMenu li[data-page]');
    sidebarItems.forEach(item => {
        item.addEventListener('click', () => {
            // Update active states
            sidebarItems.forEach(i => i.classList.remove('active-link'));
            item.classList.add('active-link');

            // Show corresponding page
            const pageId = item.getAttribute('data-page');
            pageManager.showPage(pageId);

            // Initialize charts if reports page
            if (pageId === 'reports') {
                initializeCharts();
            }
        });
    });

    // Search functionality
    const userSearch = document.getElementById('userSearch');
    const jobSearch = document.getElementById('jobSearch');

    if (userSearch) {
        const debouncedSearch = Utils.debounce(function (e) {
            const query = e.target.value.toLowerCase();
            filterTable('usersTable', query);
        }, 300);

        userSearch.addEventListener('input', debouncedSearch);
    }

    if (jobSearch) {
        const debouncedSearch = Utils.debounce(function (e) {
            const query = e.target.value.toLowerCase();
            filterTable('jobsTable', query);
        }, 300);

        jobSearch.addEventListener('input', debouncedSearch);
    }

    // Role filter
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', function () {
            const role = this.value;
            filterTableByRole('usersTable', role);
        });
    }

    // Table filtering functions
    function filterTable(tableId, query) {
        const table = document.getElementById(tableId);
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        }
    }

    function filterTableByRole(tableId, role) {
        const table = document.getElementById(tableId);
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const roleCell = row.querySelector('.badge.employer, .badge.jobseeker');

            if (!role || (roleCell && roleCell.classList.contains(role))) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    // Action buttons
    document.addEventListener('click', function (e) {
        // User actions
        if (e.target.classList.contains('btn-danger') && e.target.textContent.includes('Block')) {
            const row = e.target.closest('tr');
            const userName = row.cells[1].textContent;

            // ** REPLACED alert() with showNotification() and modal logic **
            if (confirm(`Are you sure you want to block ${userName}?`)) {
                // Simulate blocking user
                e.target.textContent = 'Unblock';
                e.target.classList.remove('btn-danger');
                e.target.classList.add('btn-primary');

                const statusCell = row.cells[4];
                statusCell.innerHTML = '<span class="badge pending">Blocked</span>';

                showNotification(`User ${userName} has been blocked`, 'info');
            }
        } else if (e.target.classList.contains('btn-primary') && e.target.textContent.includes('Unblock')) {
            const row = e.target.closest('tr');
            const userName = row.cells[1].textContent;

            if (confirm(`Are you sure you want to unblock ${userName}?`)) {
                // Simulate unblocking user
                e.target.textContent = 'Block';
                e.target.classList.remove('btn-primary');
                e.target.classList.add('btn-danger');

                const statusCell = row.cells[4];
                statusCell.innerHTML = '<span class="badge active">Active</span>';

                showNotification(`User ${userName} has been unblocked`, 'success');
            }
        }

        // Job actions
        if (e.target.classList.contains('btn-primary') && e.target.textContent.includes('Approve')) {
            const row = e.target.closest('tr');
            const jobTitle = row.cells[1].textContent;

            if (confirm(`Approve job posting: ${jobTitle}?`)) {
                // Simulate approval
                const statusCell = row.cells[4];
                statusCell.innerHTML = '<span class="badge active">Active</span>';

                const actionCell = row.cells[5];
                actionCell.innerHTML = `
                    <button class="btn btn-secondary btn-sm">View</button>
                    <button class="btn btn-danger btn-sm">Close</button>
                `;

                showNotification(`Job "${jobTitle}" has been approved`, 'success');
            }
        } else if (e.target.classList.contains('btn-danger') && e.target.textContent.includes('Reject')) {
            const row = e.target.closest('tr');
            const jobTitle = row.cells[1].textContent;

            if (confirm(`Reject job posting: ${jobTitle}?`)) {
                // Simulate rejection
                row.remove();
                showNotification(`Job "${jobTitle}" has been rejected`, 'error');

                // Update pending jobs count
                updatePendingJobsCount();
            }
        }
    });

    // Update pending jobs count
    function updatePendingJobsCount() {
        const pendingJobs = document.querySelectorAll('.badge.pending').length;
        const pendingCard = document.querySelector('.kpi-card h3');
        if (pendingCard) {
            pendingCard.textContent = pendingJobs;
        }
    }

    // Initialize charts
    function initializeCharts() {
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart');
        if (userGrowthCtx) {
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Total Users',
                        data: [120, 135, 148, 152, 160, 156],
                        borderColor: '#1abc9c',
                        backgroundColor: 'rgba(26, 188, 156, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Job Postings Chart
        const jobPostingsCtx = document.getElementById('jobPostingsChart');
        if (jobPostingsCtx) {
            new Chart(jobPostingsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Job Postings',
                        data: [45, 52, 48, 67, 62, 70],
                        backgroundColor: '#3498db',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // Initialize with users page
    pageManager.showPage('users');

    console.log('Admin dashboard initialized successfully');
});
