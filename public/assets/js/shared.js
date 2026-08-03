document.addEventListener("DOMContentLoaded", () => {
  // Set current year
  const yearEl = document.getElementById("currentYear");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const loginForm = document.getElementById("loginForm");
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");
  const errorMessage = document.getElementById("errorMessage");
  const submitBtn = document.getElementById("submitBtn");
  const togglePassword = document.getElementById("togglePassword");
  const eyeIcon = document.getElementById("eyeIcon");
  const eyeOffIcon = document.getElementById("eyeOffIcon");

  // Toggle Password
  if (togglePassword) {
    togglePassword.addEventListener("click", () => {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      if (type === "text") {
        eyeIcon.style.display = "none";
        eyeOffIcon.style.display = "block";
      } else {
        eyeIcon.style.display = "block";
        eyeOffIcon.style.display = "none";
      }
    });
  }

  // Clear errors on input
  const clearError = () => {
    errorMessage.textContent = "";
    usernameInput.classList.remove("has-error");
    passwordInput.classList.remove("has-error");
  };
  if (usernameInput) usernameInput.addEventListener("input", clearError);
  if (passwordInput) passwordInput.addEventListener("input", clearError);

  // Form Submit
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      clearError();

      const username = usernameInput.value.trim();
      const password = passwordInput.value;

      if (!username || !password) {
        errorMessage.textContent = "يرجى إدخال اسم المستخدم وكلمة المرور.";
        usernameInput.classList.add("has-error");
        passwordInput.classList.add("has-error");
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = "جارٍ تسجيل الدخول...";

      try {
        const data = await apiPost("/auth/login", {
          national_id: username,
          password: password,
        });

        if (data.success) {
          window.location.href = data.redirect;
          return;
        }

        errorMessage.textContent = data.message || "اسم المستخدم أو كلمة المرور غير صحيحة.";
        usernameInput.classList.add("has-error");
        passwordInput.classList.add("has-error");
      } catch (err) {
        errorMessage.textContent = "تعذّر الاتصال بالخادم. حاول مرة أخرى.";
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "تسجيل الدخول";
      }
    });
  }
});
