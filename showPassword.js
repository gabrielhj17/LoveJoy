function showPassword() {
  var pword = document.getElementById("pword") || document.getElementById("new_password");
  var pwordcheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
  
  if (pword) {
    if (pword.type === "password") {
      pword.type = "text";
      if (pwordcheck) {
        pwordcheck.type = "text";
      }
    } else {
      pword.type = "password";
      if (pwordcheck) {
        pwordcheck.type = "password";
      }
    }
  }
}