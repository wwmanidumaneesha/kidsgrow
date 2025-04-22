<?php
session_start();
$errorMessage = $_SESSION['error_message'] ?? '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KidsGrow - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}body{min-height:100vh;background:#f4f7ff;display:flex}.container{display:flex;width:100%}.left-section{flex:1;background:linear-gradient(135deg,#0061ff,#60efff);padding:40px;display:flex;flex-direction:column;justify-content:space-between;color:#fff;position:relative;overflow:hidden}.left-section::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.1}.brand{position:relative;z-index:1}.brand h1{font-size:24px;font-weight:600;display:flex;align-items:center;gap:10px}.brand h1 i{font-size:28px}.hero-content{position:relative;z-index:1;max-width:500px;margin:auto;text-align:center}.hero-content h2{font-size:36px;margin-bottom:20px}.hero-content p{font-size:18px;opacity:0.9;line-height:1.6}.copyright{position:relative;z-index:1;font-size:14px;opacity:0.8;text-align:center}.right-section{flex:1;display:flex;align-items:center;justify-content:center;padding:40px}.login-container{width:100%;max-width:400px;background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1)}.login-header{text-align:center;margin-bottom:30px}.login-header h2{color:#333;font-size:28px;margin-bottom:10px}.login-header p{color:#666;font-size:16px}.form-group{margin-bottom:25px}.form-group label{display:block;margin-bottom:8px;color:#333;font-weight:500}.form-group input{width:100%;padding:12px 15px;border:2px solid #e1e1e1;border-radius:10px;font-size:16px;transition:all 0.3s ease}.form-group input:focus{border-color:#0061ff;outline:none;box-shadow:0 0 0 3px rgba(0,97,255,0.1)}.password-container{position:relative}.toggle-password{position:absolute;right:15px;top:50%;transform:translateY(-50%);background:none;border:none;color:#666;cursor:pointer;font-size:18px}.login-button{width:100%;padding:14px;background:#0061ff;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;transition:background 0.3s ease}.login-button:hover{background:#0052d6}.forgot-password{text-align:center;margin-top:20px}.forgot-password a{color:#0061ff;text-decoration:none;font-size:14px;font-weight:500}.forgot-password a:hover{text-decoration:underline}.popup{position:fixed;top:20px;right:20px;padding:15px 25px;border-radius:10px;color:#fff;font-size:14px;opacity:0;transform:translateY(-20px);transition:all 0.3s ease}.error-popup{background:#ff4d4d}.success-popup{background:#00c853}.popup.show{opacity:1;transform:translateY(0)}
  </style>
</head>
<body>
  <div class="container">
    <div class="left-section">
      <div class="brand">
        <h1><i class="fas fa-child"></i> KidsGrow</h1>
      </div>
      <div class="hero-content">
        <h2>Welcome to KidsGrow!</h2>
        <p>Empowering healthy childhoods with accurate records and personalized care. Join us in safeguarding the future, one child at a time.</p>
      </div>
      <div class="copyright">
        <p>&copy; 2025 KidsGrow Educational Solutions. All Rights Reserved.</p>
      </div>
    </div>
    <div class="right-section">
      <div class="login-container">
        <div class="login-header">
          <h2>Sign In</h2>
          <p>Access your KidsGrow account</p>
        </div>
        <form id="login-form" method="post" action="user_auth">
          <input type="hidden" name="action" value="sign_in">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-container">
              <input type="password" id="password" name="password" placeholder="Enter your password" required>
              <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="login-button">Sign In</button>
          <div class="forgot-password">
            <a href="#">Forgot Password?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div id="errorPopup" class="popup error-popup"><?php echo htmlspecialchars($errorMessage); ?></div>
  <div id="successPopup" class="popup success-popup"><?php echo htmlspecialchars($successMessage); ?></div>
  <script>
    function togglePasswordVisibility(){
      const passwordInput=document.getElementById('password'),
            toggleIcon=document.querySelector('.toggle-password i');
      if(passwordInput.type==='password'){
        passwordInput.type='text';
        toggleIcon.className='fas fa-eye-slash';
      } else {
        passwordInput.type='password';
        toggleIcon.className='fas fa-eye';
      }
    }
    function showPopup(message,type){
      const popup=document.getElementById(type==='error'?'errorPopup':'successPopup');
      popup.textContent=message;
      popup.classList.add('show');
      setTimeout(()=>popup.classList.remove('show'),3000);
    }
    document.addEventListener('DOMContentLoaded',function(){
      const errorMsg=<?php echo json_encode($errorMessage); ?>,
            successMsg=<?php echo json_encode($successMessage); ?>;
      if(errorMsg) showPopup(errorMsg,'error');
      if(successMsg) showPopup(successMsg,'success');
    });
  </script>
</body>
</html>
