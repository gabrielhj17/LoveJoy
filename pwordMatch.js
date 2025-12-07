document.addEventListener('DOMContentLoaded', function() {
    var password = document.getElementById("pword");
    var passwordCheck = document.getElementById("pwordcheck");
    var text = document.getElementById("pwordMatchText");

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