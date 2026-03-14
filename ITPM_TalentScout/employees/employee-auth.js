(function () {
  var ACCOUNT_KEY = "tsEmployeeAccount";
  var ACCOUNT_CREATED_KEY = "tsEmployeeAccountCreated";
  var LOGGED_IN_KEY = "tsEmployeeLoggedIn";

  function isLoggedIn() {
    return localStorage.getItem(LOGGED_IN_KEY) === "true";
  }

  function updateNavbarActions() {
    var navActionsList = document.querySelectorAll(".nav-actions");
    var loggedIn = isLoggedIn();

    navActionsList.forEach(function (navActions) {
      var outlineBtn = navActions.querySelector(".btn-outline");
      var primaryBtn = navActions.querySelector(".btn-primary");

      if (!outlineBtn || !primaryBtn) {
        return;
      }

      var defaultLoginHref = outlineBtn.getAttribute("href") || "./login.html";
      var defaultSignupHref = primaryBtn.getAttribute("href") || "./signup.html";

      if (loggedIn) {
        var resumeHref = defaultSignupHref.replace(/signup\.html(?:#.*)?$/, "modules/resume-builder/");
        if (resumeHref === defaultSignupHref) {
          resumeHref = "./modules/resume-builder/";
        }

        primaryBtn.textContent = "Build Resume";
        primaryBtn.setAttribute("href", resumeHref);

        outlineBtn.textContent = "Logout";
        outlineBtn.setAttribute("href", "#");
        outlineBtn.onclick = function (event) {
          event.preventDefault();
          localStorage.removeItem(LOGGED_IN_KEY);
          window.location.reload();
        };
      } else {
        primaryBtn.textContent = "Get Started";
        primaryBtn.setAttribute("href", defaultSignupHref);

        outlineBtn.textContent = "Login";
        outlineBtn.setAttribute("href", defaultLoginHref);
        outlineBtn.onclick = null;
      }
    });
  }

  function handleSignupForm() {
    if (!/\/employees\/signup\.html$/i.test(window.location.pathname)) {
      return;
    }

    var form = document.querySelector("form.auth-form");
    if (!form) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var firstName = (document.getElementById("firstName") || {}).value || "";
      var lastName = (document.getElementById("lastName") || {}).value || "";
      var email = (document.getElementById("email") || {}).value || "";
      var password = (document.getElementById("password") || {}).value || "";
      var confirmPassword = (document.getElementById("confirmPassword") || {}).value || "";

      if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
      }

      var account = {
        firstName: firstName.trim(),
        lastName: lastName.trim(),
        email: email.trim().toLowerCase(),
        password: password,
      };

      localStorage.setItem(ACCOUNT_KEY, JSON.stringify(account));
      localStorage.setItem(ACCOUNT_CREATED_KEY, "true");
      localStorage.setItem(LOGGED_IN_KEY, "false");

      alert("Account created successfully. Please login.");
      window.location.href = "./login.html";
    });
  }

  function handleLoginForm() {
    if (!/\/employees\/login\.html$/i.test(window.location.pathname)) {
      return;
    }

    var form = document.querySelector("form.auth-form");
    if (!form) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var hasAccount = localStorage.getItem(ACCOUNT_CREATED_KEY) === "true";
      if (!hasAccount) {
        alert("Please create an account first.");
        window.location.href = "./signup.html";
        return;
      }

      var emailInput = (document.getElementById("email") || {}).value || "";
      var passwordInput = (document.getElementById("password") || {}).value || "";
      var email = emailInput.trim().toLowerCase();
      var password = passwordInput;

      var storedRaw = localStorage.getItem(ACCOUNT_KEY);
      if (!storedRaw) {
        alert("Account data is missing. Please sign up again.");
        window.location.href = "./signup.html";
        return;
      }

      var storedAccount;
      try {
        storedAccount = JSON.parse(storedRaw);
      } catch (error) {
        alert("Account data is invalid. Please sign up again.");
        window.location.href = "./signup.html";
        return;
      }

      if (email !== storedAccount.email || password !== storedAccount.password) {
        alert("Invalid email or password.");
        return;
      }

      localStorage.setItem(LOGGED_IN_KEY, "true");
      alert("Login successful.");
      window.location.href = "./index.html";
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    updateNavbarActions();
    handleSignupForm();
    handleLoginForm();
  });
})();
