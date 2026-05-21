
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('main-wrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    const submenuTriggers = document.querySelectorAll('.submenu-trigger');

    // Desktop Toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('expanded');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restore state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('expanded');
        }
    }

    // Mobile Toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('mobile-show');
        });
    }

    // Submenu Toggle
    submenuTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const submenu = trigger.nextElementSibling;
            
            // Close other submenus
            submenuTriggers.forEach(otherTrigger => {
                if (otherTrigger !== trigger) {
                    otherTrigger.classList.remove('active');
                    if (otherTrigger.nextElementSibling) {
                        otherTrigger.nextElementSibling.classList.remove('show');
                    }
                }
            });

            // Toggle current
            trigger.classList.toggle('active');
            submenu.classList.toggle('show');
        });
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('mobile-show');
            }
        }
    });

    // Set Active Link based on current URL
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link, .submenu a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath) {
            link.classList.add('active');
            
            // If it's inside a submenu, open the submenu
            const parentSubmenu = link.closest('.submenu');
            if (parentSubmenu) {
                parentSubmenu.classList.add('show');
                parentSubmenu.previousElementSibling.classList.add('active');
            }
        }
    });
});
