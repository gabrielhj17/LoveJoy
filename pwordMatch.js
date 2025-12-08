document.addEventListener('DOMContentLoaded', function() {
    var password = document.getElementById("pword") || document.getElementById("new_password");
    var passwordCheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
    var text = document.getElementById("pwordMatchText");

    if (!password || !passwordCheck || !text) return; // Exit if elements don't exist

    text.style.visibility = "hidden";

    function checkPasswordMatch() {
        if(password.value == passwordCheck.value && passwordCheck.value !== "") {
            text.style.visibility = "visible";
            text.style.color = "green";
            text.innerHTML = "Passwords match";
        } else if (passwordCheck.value !== "") {
            text.style.visibility = "visible";
            text.style.color = "red";
            text.innerHTML = "Passwords do not match";
        } else {
            text.style.visibility = "hidden";
        }
    }

    password.addEventListener("keyup", checkPasswordMatch);
    passwordCheck.addEventListener("keyup", checkPasswordMatch);
});