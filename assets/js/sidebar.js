// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('show');
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar);

    // Dropdown functionality
    document.querySelectorAll('.nav-parent').forEach(parent => {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownId = this.getAttribute('data-dropdown');
            const dropdown = document.getElementById(dropdownId);
            const isOpen = dropdown.classList.contains('open');
            
            // Close all dropdowns
            document.querySelectorAll('.nav-dropdown').forEach(dd => {
                dd.classList.remove('open');
            });
            document.querySelectorAll('.nav-parent').forEach(p => {
                p.classList.remove('open');
            });
            
            // Toggle current dropdown if it wasn't open
            if (!isOpen) {
                dropdown.classList.add('open');
                this.classList.add('open');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-parent') && !e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown').forEach(dd => {
                dd.classList.remove('open');
            });
            document.querySelectorAll('.nav-parent').forEach(p => {
                p.classList.remove('open');
            });
        }
    });

    // Close sidebar on window resize if mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }
    });
});