function showPassword() {
  var pword = document.getElementById("pword") || document.getElementById("new_password");
  var pwordcheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
  
  if (pword && pwordcheck) {
    if (pword.type === "password") {
      pword.type = "text";
      pwordcheck.type = "text";
    } else {
      pword.type = "password";
      pwordcheck.type = "password";
    }
  }
}