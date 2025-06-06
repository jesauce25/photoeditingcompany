# Project Management System

A project management system for a company with three user roles: Superadmin (CEO), Admin (Project Manager), and Artist (Graphic Artist).

## Features

- Role-based authentication and authorization
- Different interfaces for different user roles
- Project and task management capabilities

## User Roles and Access

1. **Superadmin (CEO)**

   - Username: `superadmin`
   - Password: `superadmin123`
   - Has access to the admin panel with all features including user management

2. **Admin (Project Manager)**

   - Username: `admin`
   - Password: `admin123`
   - Has access to the admin panel without user management features

3. **Artist (Graphic Artist)**
   - Username: `artist`
   - Password: `artist123`
   - Has access to the artist panel with task management features

## Project Structure

- `/admin` - Admin and Superadmin interface
- `/artist` - Artist interface
- `/includes` - Common includes like database connection and authentication functions
- `/assets` - Static assets (CSS, JS, images)
- `/plugins` - Third-party plugins and libraries

## Database Setup

1. Import the `db_projectms.sql` file to create the database and tables
2. Run the `db_update.sql` script to add test users (if needed)

## Installation and Setup

1. Clone the repository to your web server document root
2. Create a MySQL database named `db_projectms`
3. Import the database dump from `db_projectms.sql`
4. Configure database connection in `includes/db_connection.php` if needed
5. Access the application through your web browser
#   p h o t o e d i t i n g c o m p a n y 1  
 