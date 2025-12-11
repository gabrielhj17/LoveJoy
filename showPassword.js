function showPassword() {
  // Get the password fields, works for register, login and reset
  var pword = document.getElementById("pword") || document.getElementById("new_password");
  var pwordcheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
  // Check password field exists
  if (pword) {
    if (pword.type === "password") {
      // Make it visible
      pword.type = "text";
      if (pwordcheck) {
        pwordcheck.type = "text";
      }
    } else {
      // Make it invisible
      pword.type = "password";
      if (pwordcheck) {
        pwordcheck.type = "password";
      }
    }
  }
}