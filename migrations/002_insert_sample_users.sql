-- Insert sample users with hashed passwords (password is 'password123' for all users)
INSERT INTO users (email, password_hash, name, role) VALUES
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin'),
('client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Client User', 'client'),
('contractor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Contractor User', 'contractor');

-- Mark all sample users as email verified
UPDATE users SET email_verified = 1 WHERE email IN ('admin@example.com', 'client@example.com', 'contractor@example.com');