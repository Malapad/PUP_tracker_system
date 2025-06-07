document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.custom-navbar .nav-link');
    const contentArea = document.getElementById('content-area');

    // Function to handle tab clicks
    function handleTabClick(event) {
        // Prevent default link behavior for nav links (not for icon links)
        if (!event.currentTarget.classList.contains('icon-link')) {
            event.preventDefault();
        }


        // Remove 'active' class from all main nav links
        navLinks.forEach(link => {
            // Only remove 'active' from navigation tabs, not icons
            if (!link.classList.contains('icon-link')) {
                link.classList.remove('active');
            }
        });

        // Add 'active' class to the clicked link, if it's a main nav link
        if (!event.currentTarget.classList.contains('icon-link')) {
            event.currentTarget.classList.add('active');
        }


        // Get the data-tab attribute to determine which content to load
        const tab = event.currentTarget.getAttribute('data-tab');

        // Simulate loading content based on the tab clicked
        // In a real application, you would make an AJAX request here
        // to load content from a PHP file or a database.
        let newContent = '';
        switch (tab) {
            case 'home':
                newContent = '<h3>Welcome Home!</h3><p>This is the home page content.</p>';
                break;
            case 'violations':
                newContent = '<h3>Violations Log</h3><p>Details about student violations.</p>';
                break;
            case 'student-sanction':
                newContent = '<h3>Student Sanction Management</h3><p>Manage student sanctions here.</p>';
                break;
            case 'user-management':
                newContent = '<h3>User Account Management</h3><p>Manage user accounts and roles.</p>';
                break;
            default:
                // If it's an icon link or a tab without content to load, do nothing or handle differently
                if (tab) { // Only update if data-tab exists (i.e., it's a main nav link)
                    newContent = '<p>Content for this tab is not yet available.</p>';
                } else {
                    return; // Do not update content area for icon clicks
                }
        }
        contentArea.innerHTML = newContent;
    }

    // Add click event listener to each nav link
    navLinks.forEach(link => {
        link.addEventListener('click', handleTabClick);
    });

    // Optionally, set the initial active tab and load its content
    // Find the link that is initially marked as 'active' (e.g., "Home")
    const initialActiveLink = document.querySelector('.custom-navbar .nav-link.active:not(.icon-link)');
    if (initialActiveLink) {
        // Trigger the click handler to load content for the initially active tab
        initialActiveLink.click();
    }
});