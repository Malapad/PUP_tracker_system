function selectRole(role) {
  if (role === 'student') {
    window.location.href = '../student-page/student_login.php';
  } else if (role === 'admin') {
    window.location.href = '../admin-login/admin_login.php';
  }
}
