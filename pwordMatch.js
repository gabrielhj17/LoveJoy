document.addEventListener('DOMContentLoaded', function() {
    // Get form elements - works for register and reset page
    var password = document.getElementById("pword") || document.getElementById("new_password");
    var passwordCheck = document.getElementById("pwordcheck") || document.getElementById("confirm_password");
    var text = document.getElementById("pwordMatchText");

    if (!password || !passwordCheck || !text) {
        console.log("pwordMatch.js: Some elements not found, exiting gracefully");
        return; // Exit if elements don't exist
    }

    function checkPasswordMatch() {
        if(password.value == passwordCheck.value && passwordCheck.value !== "") {
            // Both passwords match AND confirm field isn't empty
            text.style.visibility = "visible";
            text.style.color = "green";
            text.innerHTML = "Passwords match";
        } else if (passwordCheck.value !== "") {
            // Passwords don't match AND confirm field has content
            text.style.visibility = "visible";
            text.style.color = "red";
            text.innerHTML = "Passwords do not match";
        } 
    }
    // Update on every keypress
    password.addEventListener("keyup", checkPasswordMatch);
    passwordCheck.addEventListener("keyup", checkPasswordMatch);
    
});