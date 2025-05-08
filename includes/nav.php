<!-- Enhanced Navbar with Improved Positioning -->
<nav class="navbar">
    <div class="nav-container">
        <!-- Hamburger Icon for Mobile -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <!-- Logo -->
        <div class="nav-logo-container">
            <a href="project-status.php" class="nav-logo text-stretched-small">RAFAELITSERVICES</a>
        </div>
        <div class="nav-logo-container">
            <!-- Navigation Links -->
            <ul class="nav-links" id="navLinks">
                <li><a href="project-status.php" class="nav-link">Project Status</a></li>
                <li><a href="login.php" class="nav-link">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Enhanced Navbar Styling */
    .navbar {
        background-color: rgba(20, 20, 20, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 10;
        transition: all 0.3s ease;
        height: 64px;
        /* Fixed height for consistency */
    }

    /* Navbar scroll effect - add this class with JS */
    .navbar-scrolled {
        background-color: rgba(10, 10, 10, 0.95);
        height: 56px;
        /* Slightly smaller when scrolled */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
    }

    .nav-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        height: 100%;
    }

    .nav-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;

    }

    .nav-logo-container:nth-child(3) {
        display: flex;
        justify-content: center;
        align-items: center;


    }

    .nav-logo {
        color: #ffb22e;
        font-size: 1.2rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        /* Stretched text effect */
        text-decoration: none;
        position: relative;
        padding: 5px 0;
        transition: all 0.3s ease;
    }

    .text-stretched-small {
        letter-spacing: 0.1em;
    }

    .nav-logo:hover {
        color: #fff;
    }

    /* Animated underline effect for logo */
    .nav-logo::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: #ffb22e;
        transition: width 0.3s ease;
    }

    .nav-logo:hover::after {
        width: 100%;
    }

    .nav-links {
        display: flex;
        list-style-type: none;
        margin: 0;
        padding: 0;
        height: 100%;
    }

    .nav-links li {
        margin: 0;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .nav-link {
        color: #f7f7f7;
        text-decoration: none;
        font-weight: 500;
        padding: 0 15px;
        height: 100%;
        display: flex;
        align-items: center;
        position: relative;
        transition: color 0.3s;
    }

    .nav-link:hover {
        color: #ffb22e;
    }

    /* Animated underline for nav links */
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 3px;
        background-color: #ffb22e;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .nav-link:hover::after {
        width: 100%;
    }

    /* Active link state */
    .nav-link.active {
        color: #ffb22e;
    }

    .nav-link.active::after {
        width: 100%;
        background-color: #ffb22e;
    }

    .nav-toggle {
        display: none;
        background: none;
        border: none;
        padding: 10px;
        cursor: pointer;
        position: relative;
        z-index: 10;
        width: 40px;
        height: 40px;
    }

    /* Modern hamburger icon */
    .hamburger-line {
        display: block;
        width: 24px;
        height: 2px;
        margin: 5px 0;
        background-color: #ffb22e;
        transition: all 0.3s ease;
    }

    /* Hamburger animation */
    .nav-toggle.active .hamburger-line:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .nav-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
    }

    .nav-toggle.active .hamburger-line:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    /* Enhanced mobile responsive styles */
    @media (max-width: 768px) {
        .nav-toggle {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .nav-logo-container {
            flex-grow: 1;
            justify-content: center;
        }

        .nav-links {
            display: none;
            width: 100%;
            flex-direction: column;
            background-color: rgba(15, 15, 15, 0.97);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            position: absolute;
            top: 64px;
            /* Match navbar height */
            left: 0;
            padding: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            visibility: hidden;
        }

        .nav-links.active {
            display: flex;
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }

        .nav-links li {
            width: 100%;
            height: auto;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-link {
            width: 100%;
            padding: 20px 0;
            justify-content: center;
        }

        .nav-link::after {
            bottom: 10px;
        }
    }

    /* Add space at the top of the page to account for fixed navbar */
    body {
        padding-top: 64px;
        /* Same as navbar height */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle mobile menu
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');

        if (navToggle) {
            navToggle.addEventListener('click', function (event) {
                event.stopPropagation();
                navToggle.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
        }

        // Close menu when clicking outside
        document.addEventListener('click', function (event) {
            if (navLinks.classList.contains('active') &&
                !navToggle.contains(event.target) &&
                !navLinks.contains(event.target)) {
                navLinks.classList.remove('active');
                navToggle.classList.remove('active');
            }
        });

        // Set active link based on current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinkElements = document.querySelectorAll('.nav-link');

        navLinkElements.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'project-status.php')) {
                link.classList.add('active');
            }
        });

        // Scroll effect for navbar
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 30) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    });
</script>