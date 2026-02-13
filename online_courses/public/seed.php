<?php
require __DIR__ . '/../bootstrap.php';

seed_user('admin@example.com', 'admin123A', 'admin');
seed_user('teacher@example.com', 'teacher123A', 'teacher');
seed_user('student@example.com', 'student123A', 'student');

echo "<h3>Готово</h3>";
echo "<p>admin@example.com / admin123A</p>";
echo "<p>teacher@example.com / teacher123A</p>";
echo "<p>student@example.com / student123A</p>";
echo '<p><a href="index.php">Войти</a></p>';
