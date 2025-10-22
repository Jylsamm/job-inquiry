<?php
// Admin Panel - protected page
$pageTitle = 'Admin Panel';
require_once 'includes/config.php';

// Require admin role
requireRole('admin');

require_once 'includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <div class="brand">
            <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="logo">
            <h3>Admin Panel</h3>
        </div>
        <ul id="sidebarMenu">
            <li class="active-link" data-page="users"><i class="bi bi-people-fill"></i> User Management</li>
            <li data-page="jobs"><i class="bi bi-briefcase-fill"></i> Job Approval</li>
            <li data-page="reports"><i class="bi bi-graph-up"></i> Reports</li>
            <li class="logout">
                <a href="logout.php" style="color:white;text-decoration:none;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div id="content">
            <!-- Users Management Page -->
            <div id="usersPage" class="page active">
                <h2>User Management</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <h3>150</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="kpi-card">
                        <h3>45</h3>
                        <p>Employers</p>
                    </div>
                    <div class="kpi-card">
                        <h3>105</h3>
                        <p>Job Seekers</p>
                    </div>
                </div>

                <div class="card">
                    <div class="table-controls">
                        <input type="text" placeholder="Search users..." class="search-input" id="userSearch">
                        <select class="filter-select" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="employer">Employer</option>
                            <option value="jobseeker">Job Seeker</option>
                        </select>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <tr>
                                <td>1</td>
                                <td>Juan Dela Cruz</td>
                                <td>juan@email.com</td>
                                <td><span class="badge jobseeker">Job Seeker</span></td>
                                <td><span class="badge active">Active</span></td>
                                <td>
                                    <button class="btn btn-danger btn-sm">Block</button>
                                    <button class="btn btn-secondary btn-sm">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>TechCorp Inc.</td>
                                <td>hr@techcorp.com</td>
                                <td><span class="badge employer">Employer</span></td>
                                <td><span class="badge active">Active</span></td>
                                <td>
                                    <button class="btn btn-danger btn-sm">Block</button>
                                    <button class="btn btn-secondary btn-sm">View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Job Approval Page -->
            <div id="jobsPage" class="page">
                <h2>Job Posting Approval</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <h3>12</h3>
                        <p>Pending Approval</p>
                    </div>
                    <div class="kpi-card">
                        <h3>45</h3>
                        <p>Active Jobs</p>
                    </div>
                    <div class="kpi-card">
                        <h3>89</h3>
                        <p>Total Applications</p>
                    </div>
                </div>

                <div class="card">
                    <div class="table-controls">
                        <input type="text" placeholder="Search jobs..." class="search-input" id="jobSearch">
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="jobsTable">
                            <tr>
                                <td>101</td>
                                <td>Senior Web Developer</td>
                                <td>TechCorp</td>
                                <td>Manila</td>
                                <td><span class="badge pending">Pending</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm">Approve</button>
                                    <button class="btn btn-danger btn-sm">Reject</button>
                                </td>
                            </tr>
                            <tr>
                                <td>102</td>
                                <td>Graphic Designer</td>
                                <td>Creatives Inc</td>
                                <td>Cebu</td>
                                <td><span class="badge active">Active</span></td>
                                <td>
                                    <button class="btn btn-secondary btn-sm">View</button>
                                    <button class="btn btn-danger btn-sm">Close</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports Page -->
            <div id="reportsPage" class="page">
                <h2>Reports & Analytics</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <h3>156</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="kpi-card">
                        <h3>67</h3>
                        <p>Active Jobs</p>
                    </div>
                    <div class="kpi-card">
                        <h3>234</h3>
                        <p>Applications</p>
                    </div>
                </div>

                <div class="card">
                    <h3>User Growth</h3>
                    <canvas id="userGrowthChart" height="100"></canvas>
                </div>

                <div class="card">
                    <h3>Job Postings</h3>
                    <canvas id="jobPostingsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
