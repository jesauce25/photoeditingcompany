<?php
include("includes/header.php");
?>

<style>
  /* Center the login container */
  body {
    position: relative;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .login-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1;
    width: 90%;
    max-width: 400px;
  }

  /* Make sure background elements cover the viewport */
  .background,
  .floating-shapes,
  .black-covers {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
</style>

<body>
  <div class="background"></div>
  <div class="floating-shapes"></div>
  <div class="black-covers"></div>

  <div class="login-container">
    <h2>FORGOT PASSWORD</h2>
    <div id="message" class="alert" style="display: none;"></div>
    <form id="forgotPasswordForm">
      <div class="input-group">
        <input type="email" placeholder="Enter your email" id="email" name="email" required />
      </div>

      <button type="submit">
        <div class="text">
          <span class="letter">S</span>
          <span class="letter">U</span>
          <span class="letter">B</span>
          <span class="letter">M</span>
          <span class="letter">I</span>
          <span class="letter">T</span>
        </div>
      </button>

      <div class="forgot-password-container">
        <a href="login.php" id="backToLogin">Back to Login</a>
      </div>
    </form>
  </div>

  <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const email = document.getElementById('email').value;
      const messageDiv = document.getElementById('message');

      // Reset message
      messageDiv.textContent = '';
      messageDiv.className = 'alert';
      messageDiv.style.display = 'none';

      // Form validation
      if (!email) {
        showMessage('Please enter your email address.', 'error');
        return;
      }

      // Disable submit button to prevent multiple submissions
      const submitButton = document.querySelector('button[type="submit"]');
      submitButton.disabled = true;

      // Send AJAX request
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'send-password-reset.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      xhr.onload = function () {
        submitButton.disabled = false;

        if (xhr.status === 200) {
          const response = xhr.responseText;

          if (response.includes('sent')) {
            showMessage('Password reset link has been sent to your email. Please check your inbox.', 'success');
            document.getElementById('email').value = '';
          } else {
            showMessage(response, 'error');
          }
        } else {
          showMessage('An error occurred. Please try again later.', 'error');
        }
      };

      xhr.onerror = function () {
        submitButton.disabled = false;
        showMessage('An error occurred. Please try again later.', 'error');
      };

      xhr.send('email=' + encodeURIComponent(email));
    });

    function showMessage(message, type) {
      const messageDiv = document.getElementById('message');
      messageDiv.textContent = message;
      messageDiv.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-danger');
      messageDiv.style.display = 'block';
    }



  </script>



  <?php
  include("includes/footer.php");
  ?>