# Car Rental System

A complete, modern, and responsive web application for car rental services built with PHP, MySQL, HTML5, CSS3, and JavaScript. This project is designed for university PHP & JS coursework and demonstrates best practices in web development.

## 🚀 Features

### Front Office (User Side)
- **User Authentication**: Registration, login/logout with secure password hashing
- **Home Page**: Hero section, featured cars, services, testimonials
- **Cars Catalog**: Dynamic car listing with search and advanced filters
- **Car Details**: Multiple images, specifications, and reservation form
- **Reservation System**: Date selection, automatic price calculation
- **User Dashboard**: Profile management, reservation history, booking cancellation

### Back Office (Admin Dashboard)
- **Admin Authentication**: Secure admin login system
- **Dashboard**: Statistics, charts, and quick insights
- **User Management**: View, search, and delete users
- **Car Management**: Add, edit, delete cars with image upload
- **Reservation Management**: View, update status, manage bookings
- **Reports & Analytics**: Comprehensive reports with Chart.js visualizations

### Technical Features
- **Responsive Design**: Mobile-first approach with modern UI/UX
- **Dark Mode**: Toggle between light and dark themes
- **Security**: SQL injection protection, XSS prevention, CSRF tokens
- **Performance**: Optimized queries, pagination, lazy loading
- **Accessibility**: Semantic HTML5, ARIA labels, keyboard navigation

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+ (Procedural with OOP elements)
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Styling**: Custom CSS with CSS Variables, Flexbox, Grid
- **Charts**: Chart.js for data visualization
- **Security**: Prepared statements (PDO), password hashing, input validation

## 📋 Requirements

### Server Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache 2.4+ or Nginx 1.18+
- PHP Extensions:
  - `pdo_mysql`
  - `mysqli`
  - `gd` (for image processing)
  - `fileinfo`
  - `mbstring`

### Client Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 320px (mobile)

## 🚀 Installation Guide

### 1. Download the Project
```bash
git clone https://github.com/your-username/car-rental-system.git
cd car-rental-system
```

### 2. Database Setup

#### Create Database
```sql
CREATE DATABASE car_rental_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Import Database Schema
```bash
mysql -u root -p car_rental_db < database/schema.sql
```

### 3. Configuration

#### Update Database Credentials
Edit `includes/config.php` and update the database settings:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_rental_db');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

#### Update Site Configuration (Optional)
```php
define('SITE_URL', 'http://localhost/car-rental-system');
define('SITE_NAME', 'Car Rental System');
define('ADMIN_EMAIL', 'admin@carrental.com');
```

### 4. File Permissions

Set proper permissions for uploads directory:
```bash
chmod -R 755 uploads/
chmod -R 755 includes/
```

### 5. Web Server Configuration

#### Apache (.htaccess)
The project includes `.htaccess` for URL rewriting and security headers.

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/car-rental-system;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 🔐 Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `password`

### Sample Users
- **Email**: `john.doe@email.com`
- **Password**: `password`

## 📁 Project Structure

```
car-rental-system/
├── admin/                  # Admin dashboard files
│   ├── index.php          # Admin dashboard
│   ├── users.php          # User management
│   ├── cars.php           # Car management
│   ├── reservations.php   # Reservation management
│   └── reports.php        # Reports and analytics
├── account/               # User dashboard
│   └── index.php          # User account management
├── auth/                  # Authentication files
│   ├── login.php          # User login
│   ├── register.php       # User registration
│   ├── logout.php         # Logout handler
│   └── admin-login.php    # Admin login
├── css/                   # Stylesheets
│   └── style.css          # Main stylesheet
├── js/                    # JavaScript files
│   └── script.js          # Main JavaScript file
├── includes/              # PHP includes
│   ├── config.php         # Configuration settings
│   ├── database.php       # Database connection and helper functions
│   └── functions.php      # Utility functions
├── database/              # Database files
│   └── schema.sql         # Database schema and sample data
├── uploads/               # File uploads directory
│   └── cars/              # Car images
├── images/                # Static images
├── index.php              # Home page
├── cars.php               # Cars catalog
├── car-details.php        # Car details page
└── README.md              # This file
```

## 🎨 Customization

### Branding
Update the site colors in `css/style.css` by modifying CSS variables:

```css
:root {
    --primary-color: #dc3545;    /* Main brand color */
    --secondary-color: #343a40;  /* Secondary color */
    --accent-color: #ffc107;     /* Accent color */
}
```

### Images
Replace placeholder images in the `images/` directory:
- `hero-bg.jpg` - Hero section background
- `about-image.jpg` - About section image

### Email Configuration
Update email settings in `includes/functions.php` for production use.

## 🔧 Development

### Code Standards
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Validate all user inputs
- Use prepared statements for database queries

### Testing
- Test all forms with valid and invalid data
- Verify security measures
- Test responsive design on different devices
- Check database operations and error handling

## 🚀 Deployment

### Production Checklist
1. **Security**: 
   - Change default passwords
   - Set proper file permissions
   - Enable HTTPS
   - Configure firewall rules

2. **Performance**:
   - Enable PHP OPcache
   - Configure database caching
   - Optimize images
   - Enable GZIP compression

3. **Monitoring**:
   - Set up error logging
   - Monitor database performance
   - Set up backups

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
```bash
# Check database credentials
# Verify database exists
# Check PHP MySQL extensions
```

#### File Upload Issues
```bash
# Check upload directory permissions
# Verify PHP upload limits
# Check file size limits
```

#### Session Issues
```bash
# Check session save path permissions
# Verify session configuration
# Clear browser cookies
```

## 📞 Support

For issues and questions:
1. Check this README file
2. Review code comments
3. Test with sample data
4. Check browser console for JavaScript errors

## 📄 License

This project is created for educational purposes. Feel free to use and modify according to your needs.

## 🙏 Acknowledgments

- Chart.js for data visualization
- Font Awesome for icons (if used)
- Modern CSS techniques and best practices
- PHP community and documentation

---

**Happy Coding! 🚗💨**

*Built with ❤️ for educational purposes*
