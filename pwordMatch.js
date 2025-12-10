document.addEventListener('DOMContentLoaded', function() {
    console.log("pwordMatch.js loaded");
    
    var password = document.getElementById("pword") || document.getElementById("new_password");
    var passwordCheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
    var text = document.getElementById("pwordMatchText");

    console.log("pwordMatch - password:", password);
    console.log("pwordMatch - passwordCheck:", passwordCheck);
    console.log("pwordMatch - text:", text);

    if (!password || !passwordCheck || !text) {
        console.log("pwordMatch.js: Some elements not found, exiting gracefully");
        return; // Exit if elements don't exist
    }

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
    
    console.log("pwordMatch.js: Event listeners attached successfully");
});