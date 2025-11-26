# 🔐 Authentication Guide

The Git Webhook Manager now includes a complete authentication system to secure your deployments.

## 🚀 Quick Setup

### 1. Run Seeder to Create Users

```bash
php artisan db:seed --class=UserSeeder
```

This will create 2 default users:

**Admin User:**
- Email: `admin@gitwebhook.local`
- Password: `password`

**Demo User:**
- Email: `demo@gitwebhook.local`
- Password: `password`

### 2. Access the Application

Visit `http://localhost:8000` and you'll be redirected to login page.

## 📝 Features

### ✅ Login System
- Beautiful Bootstrap 5 login page
- Remember me functionality
- Email & password authentication
- Session management
- CSRF protection

### ✅ Registration System
- User registration with validation
- Password confirmation
- Automatic login after registration
- Email uniqueness check

### ✅ Protected Routes
All webhook and deployment routes require authentication:
- Dashboard
- Webhooks (CRUD operations)
- Deployments (View & Trigger)
- SSH Key Management

### ✅ Public Routes
The webhook API endpoint remains public for Git providers:
- `POST /webhook/{webhook}/{token}` - For GitHub/GitLab webhooks

## 🎨 User Interface

### Sidebar User Info
The sidebar displays:
- User's full name
- User's email
- Logout button

### Auth Pages
- Modern gradient background
- Bootstrap 5 cards
- Icon-based inputs
- Validation messages
- Responsive design

## 👤 Creating New Users

### Via Database Seeder
Create your own seeder:

```php
User::create([
    'name' => 'Your Name',
    'email' => 'your@email.com',
    'password' => Hash::make('your-password'),
]);
```

### Via Tinker
```bash
php artisan tinker

User::create([
    'name' => 'New User',
    'email' => 'newuser@example.com',
    'password' => Hash::make('password123')
]);
```

## 🔒 Security Features

1. **Password Hashing** - BCrypt hashing with Laravel's Hash facade
2. **CSRF Protection** - All forms protected with CSRF tokens
3. **Session Security** - Secure session management
4. **Remember Me** - Optional persistent login
5. **Route Protection** - Middleware-based authentication
6. **Validation** - Server-side validation for all inputs

## 🛠️ Customization

### Change Default Redirect

Edit controllers to change redirect after login:

```php
return redirect()->intended(route('your-route'));
```

## 📱 Session Management

Sessions are stored in the database (default configuration).

To check active sessions:
```bash
php artisan tinker
DB::table('sessions')->get();
```

To clear all sessions:
```bash
php artisan tinker
DB::table('sessions')->truncate();
```

## 🐛 Troubleshooting

### "CSRF token mismatch"
```bash
php artisan cache:clear
php artisan config:clear
```

### "Session store not set"
Check your `.env`:
```env
SESSION_DRIVER=database
```

Then:
```bash
php artisan migrate
```

### Forgot Password?
Currently, password reset is not implemented. To reset manually:

```bash
php artisan tinker

$user = User::where('email', 'admin@gitwebhook.local')->first();
$user->password = Hash::make('new-password');
$user->save();
```

## 📚 Files Created

```
app/Http/Controllers/Auth/
└── LoginController.php       # Login logic

resources/views/auth/
└── login.blade.php           # Login page

database/seeders/
└── UserSeeder.php            # Default users seeder
```

## 🔗 Routes

**Public Routes:**
- `GET /login` - Show login form
- `POST /login` - Process login

**Protected Routes:**
- `POST /logout` - Logout (requires auth)
- `GET /` - Dashboard (requires auth)
- All webhook routes (requires auth)
- All deployment routes (requires auth)

## 💡 Best Practices

1. **Change Default Passwords** - Update default user passwords immediately
2. **Use Strong Passwords** - Enforce strong password policies
3. **Regular Audits** - Review user accounts regularly
4. **Session Timeouts** - Configure appropriate session lifetimes
5. **HTTPS in Production** - Always use HTTPS for authentication

## 🎯 Next Steps

1. Run the seeder: `php artisan db:seed --class=UserSeeder`
2. Visit: `http://localhost:8000`
3. Login with: `admin@gitwebhook.local` / `password`
4. Change the default password
5. Start managing your webhooks!

---

**Security Note:** Always change default passwords in production environments!
