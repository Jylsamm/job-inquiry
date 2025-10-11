// Home Page JavaScript for XAMPP
document.addEventListener('DOMContentLoaded', function () {
    console.log('WorkConnect PH - Home Page Loaded');

    // Check login status
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const panelLinks = document.querySelectorAll('.panel-link');
    const modal = document.getElementById('modal');
    const closeModal = document.querySelector('.close-modal');
    const viewDetailsBtns = document.querySelectorAll('.view-details');
    const jobSearch = document.getElementById('jobSearch');

    // Setup navigation based on login status
    function setupNavigation() {
        if (isLoggedIn) {
            loginBtn.style.display = 'none';
            logoutBtn.style.display = 'inline-block';
            panelLinks.forEach(link => link.style.display = 'block');
        } else {
            loginBtn.style.display = 'inline-block';
            logoutBtn.style.display = 'none';
            panelLinks.forEach(link => link.style.display = 'none');
        }
    }

    // Setup modal functionality
    function setupModal() {
        if (!modal) return;

        function openModal() {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModalFunc() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }

        // View details buttons
        viewDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!isLoggedIn) {
                    openModal();
                } else {
                    // Redirect to job details or show details
                    const jobCard = this.closest('.card');
                    const jobTitle = jobCard.querySelector('h4').textContent;
                    alert(`Viewing details for: ${jobTitle}`);
                }
            });
        });

        // Close modal events
        if (closeModal) {
            closeModal.addEventListener('click', closeModalFunc);
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModalFunc();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModalFunc();
            }
        });
    }

    // Setup search functionality
    function setupSearch() {
        if (!jobSearch) return;

        const debouncedSearch = Utils.debounce(function (e) {
            const query = e.target.value.trim();
            console.log('Searching for:', query);

            if (query.length >= 2) {
                // Simulate search - replace with actual API call
                simulateSearch(query);
            }
        }, 300);

        jobSearch.addEventListener('input', debouncedSearch);
    }

    // Simulate search results
    function simulateSearch(query) {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (text.includes(query.toLowerCase())) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Logout functionality
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            localStorage.removeItem('loggedIn');
            localStorage.removeItem('userRole');
            localStorage.removeItem('userId');
            window.location.href = 'home.html';
        });
    }

    // Initialize everything
    setupNavigation();
    setupModal();
    setupSearch();

    console.log('Home page initialized successfully');
});