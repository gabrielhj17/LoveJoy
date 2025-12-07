function showPassword() {
  var pword = document.getElementById("pword");
  var pwordcheck = document.getElementById("pwordcheck");
  if (pword.type === "password") {
    pword.type = "text";
    pwordcheck.type = "text";
  } else {
    pword.type = "password";
    pwordcheck.type = "password";
  }
}