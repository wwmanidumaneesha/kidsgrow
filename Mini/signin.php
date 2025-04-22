<?php session_start();$errorMessage=$_SESSION['error_message']?? '';$successMessage=$_SESSION['success_message']?? '';$userEmail=$_SESSION['user_email']?? '';$userName=$_SESSION['user_name']?? '';unset($_SESSION['error_message'],$_SESSION['success_message'],$_SESSION['user_email'],$_SESSION['user_name']); ?><!doctypehtml><html lang="en"><head><meta charset="UTF-8"><meta content="width=device-width,initial-scale=1"name="viewport"><title>Ministry of Health - Sign Up</title><style>body{margin:0;font-family:Arial,sans-serif;display:flex;height:100vh;background-color:#f4f4f4}.container{display:flex;width:100%}.left-section{background:linear-gradient(to bottom,#8fc4f1,#274fb4);color:#fff;flex:1;display:flex;flex-direction:column;justify-content:space-between;align-items:center;padding:20px;position:relative}.left-section h1{position:absolute;top:20px;left:20px;font-size:20px;margin:0;display:flex;align-items:center}.left-section h1::before{content:'\2665';font-size:18px;margin-right:8px}.left-section img{width:80%;max-width:400px;margin-top:80px;margin-bottom:20px}.left-section p{margin-top:auto}.right-section{flex:1;background-color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:20px;position:relative}.form-container{width:100%;max-width:400px;box-shadow:0 4px 6px rgba(0,0,0,.1);border-radius:10px;padding:20px;background-color:#fff}.tabs{display:flex;justify-content:space-evenly;border-bottom:1px solid #ccc;margin-bottom:20px}.tabs a{text-decoration:none;color:#333;font-weight:700;padding-bottom:10px;position:relative;cursor:pointer}.tabs a.active{color:#007bff}.tabs a.active::after{content:'';position:absolute;left:0;bottom:0;height:2px;width:100%;background-color:#007bff}.tabs a:not(.active){color:#999}.form-group{margin-bottom:15px;display:flex;flex-direction:column;align-items:flex-start;position:relative}.form-group label{font-size:14px;color:#333;margin-bottom:5px}.form-group input{width:100%;padding:10px;padding-right:40px;border:1px solid #ccc;border-radius:5px;font-size:14px;box-sizing:border-box}.form-group input:focus{outline:0;border-color:#007bff}.password-container{position:relative;width:100%}.password-container input{width:100%;padding-right:40px}.password-container .toggle-password{position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:18px;color:#007bff;border:none;background:0 0;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center}.password-container .toggle-password:focus{outline:0;box-shadow:none}.form-container button{width:100%;max-width:360px;padding:10px;background-color:#007bff;border:none;color:#fff;font-size:16px;border-radius:5px;cursor:pointer;display:block;margin-top:10px}.form-container button:hover{background-color:#0056b3}.form-footer{text-align:center;margin-top:15px}.form-footer a{text-decoration:none;color:red;font-size:14px;cursor:pointer}.social-icons{position:absolute;bottom:20px;display:flex;justify-content:center;width:100%}.social-icons a{margin:0 10px;font-size:20px;color:#333;text-decoration:none}.social-icons a:hover{color:#007bff}.hidden{display:none}.error-popup{display:none;position:fixed;top:20px;left:50%;transform:translateX(-50%);background-color:#ff4d4d;color:#fff;padding:15px 20px;border-radius:5px;box-shadow:0 4px 6px rgba(0,0,0,.2);font-size:14px;z-index:1000}.error-popup.show{display:block}.success-popup{display:none;position:fixed;top:20px;left:50%;transform:translateX(-50%);background-color:#4caf50;color:#fff;padding:15px 20px;border-radius:5px;box-shadow:0 4px 6px rgba(0,0,0,.2);font-size:14px;z-index:1000}.success-popup.show{display:block}</style><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"rel="stylesheet"><script>function toggleForm(formType) {
            const signUpForm = document.getElementById('sign-up-form');
            const signInForm = document.getElementById('sign-in-form');
            const signUpTab = document.getElementById('sign-up-tab');
            const signInTab = document.getElementById('sign-in-tab');

            if (formType === 'sign-up') {
                signUpForm.classList.remove('hidden');
                signInForm.classList.add('hidden');
                signUpTab.classList.add('active');
                signInTab.classList.remove('active');
            } else {
                signInForm.classList.remove('hidden');
                signUpForm.classList.add('hidden');
                signInTab.classList.add('active');
                signUpTab.classList.remove('active');
            }
        }

        function togglePasswordVisibility(button, inputId) {
            const passwordInput = document.getElementById(inputId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                button.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const errorMessage =<?php echo json_encode($errorMessage); ?>; // PHP injects the error message
            const successMessage =<?php echo json_encode($successMessage); ?>; // PHP injects the success message

            // Handle success message
            if (successMessage) {
                const successPopup = document.querySelector(".success-popup");

                if (successPopup) {
                    successPopup.textContent = successMessage; // Display the success message
                    successPopup.classList.add("show");

                    // Remove popup and clear its content after 3 seconds
                    setTimeout(() => {
                        successPopup.classList.remove("show");
                        successPopup.textContent = ""; // Clear the content
                    }, 3000);
                }
            }

            // Handle error message
            if (errorMessage) {
                const errorPopup = document.querySelector(".error-popup");

                if (errorPopup) {
                    errorPopup.textContent = errorMessage; // Display the error message
                    errorPopup.classList.add("show");

                    // Remove popup and clear its content after 3 seconds
                    setTimeout(() => {
                        errorPopup.classList.remove("show");
                        errorPopup.textContent = ""; // Clear the content
                    }, 3000);
                }
            }
        });

    document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById('password');
    const signUpForm = document.getElementById('sign-up-form');
    const errorPopup = document.querySelector(".error-popup");

    // Function to validate password strength
    function validatePassword(password) {
        const minLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        return minLength && hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar;
    }

    // Add input event listener for visual feedback on typing
    passwordInput.addEventListener('input', function () {
        const password = passwordInput.value;

        // Change border color based on password validity while typing
        if (validatePassword(password)) {
            passwordInput.style.borderColor = "green";
        } else {
            passwordInput.style.borderColor = "red";
        }
    });

    // Prevent form submission if password is invalid and show error message
    signUpForm.addEventListener('submit', function (e) {
        const password = passwordInput.value;

        if (!validatePassword(password)) {
            e.preventDefault();

            // Show error message in the popup
            if (errorPopup) {
                errorPopup.textContent = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
                errorPopup.classList.add("show");

                // Clear the error message after 3 seconds
                setTimeout(() => {
                    errorPopup.classList.remove("show");
                    errorPopup.textContent = "";
                }, 3000);
            }
        }
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const userEmail =<?php echo json_encode($userEmail); ?>;
    const userName =<?php echo json_encode($userName); ?>;

    if (userEmail && userName) {
        console.log("Attempting to send email...");

        fetch('send_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `email=${encodeURIComponent(userEmail)}&full_name=${encodeURIComponent(userName)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log("Email Response: ", data);
        })
        .catch(error => console.error('AJAX Error:', error));
    } else {
        console.error("Email sending skipped due to missing data.");
    }
});</script></head><body><div class="container"><div class="left-section"><h1>Ministry of Health</h1><img alt="Doctor Illustration"src="https://i.ibb.co/yYpj21s/image.png"><p>Copyright © 2025 MSBK Software Solutions. All Rights Reserved.</p></div><div class="right-section"><div class="form-container"><div class="tabs"><a onclick='toggleForm("sign-up")'id="sign-up-tab"class="active">Sign Up</a> <a onclick='toggleForm("sign-in")'id="sign-in-tab">Sign In</a></div><form action="user_auth.php"id="sign-up-form"method="post"><input name="action"type="hidden"value="sign_up"><div class="form-group"><label for="fullname">Full Name</label> <input name="full_name"id="fullname"placeholder="Manidu Maneesha"></div><div class="form-group"><label for="email">Email</label> <input name="email"type="email"id="email"placeholder="manidumaneeshaww@gmail.com"></div><div class="form-group password-container"><label for="password">Password</label> <input name="password"type="password"id="password"placeholder="********"> <button type="button"class="toggle-password"onclick='togglePasswordVisibility(this,"password")'><i class="fa-eye fas"></i></button></div><button type="submit">Sign Up</button><div class="form-footer"><a onclick='toggleForm("sign-in")'>Already have an account?</a></div></form><form action="user_auth.php"id="sign-in-form"method="post"class="hidden"><input name="action"type="hidden"value="sign_in"><div class="form-group"><label for="sign-in-email">Email</label> <input name="email"type="email"id="sign-in-email"placeholder="Enter your email"></div><div class="form-group password-container"><label for="sign-in-password">Password</label> <input name="password"type="password"id="sign-in-password"placeholder="Enter your password"> <button type="button"class="toggle-password"onclick='togglePasswordVisibility(this,"sign-in-password")'><i class="fa-eye fas"></i></button></div><button type="submit">Sign In</button><div class="form-footer"><a onclick='toggleForm("sign-up")'>Don't have an account?</a></div></form></div></div></div><div class="error-popup<?php echo $errorMessage?'show':''; ?>"id="errorPopup"><?php echo htmlspecialchars($errorMessage); ?></div><div class="success-popup<?php echo $successMessage?'show':''; ?>"id="successPopup"><?php echo htmlspecialchars($successMessage); ?></div></body></html>