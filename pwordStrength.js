document.addEventListener('DOMContentLoaded', function() {
    // Get form elemnts, works for register and reset password
    var password = document.getElementById("pword") || document.getElementById("new_password");
    var letter = document.getElementById("letter");
    var capital = document.getElementById("capital");
    var number = document.getElementById("number");
    var special = document.getElementById("special");
    var length = document.getElementById("length");
    var message = document.getElementById("message");

    if (!password || !letter || !capital || !number || !special || !length) {
        return; // Exit if elements don't exist
    }

    // When the user starts to type something inside the password field
    password.addEventListener("keyup", function() {
        // Show the message box when typing
        if (message) {
            message.style.display = "block";
        }

        // Validate lowercase letters
        var lowerCaseLetters = /[a-z]/g;
        if (password.value.match(lowerCaseLetters)) {
            letter.classList.remove("invalid");
            letter.classList.add("valid");
        } else {
            letter.classList.remove("valid");
            letter.classList.add("invalid");
        }

        // Validate capital letters
        var upperCaseLetters = /[A-Z]/g;
        if (password.value.match(upperCaseLetters)) {
            capital.classList.remove("invalid");
            capital.classList.add("valid");
        } else {
            capital.classList.remove("valid");
            capital.classList.add("invalid");
        }

        // Validate numbers
        var numbers = /[0-9]/g;
        if (password.value.match(numbers)) {
            number.classList.remove("invalid");
            number.classList.add("valid");
        } else {
            number.classList.remove("valid");
            number.classList.add("invalid");
        }

        // Validate special characters
        var specialChars = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g;
        if (password.value.match(specialChars)) {
            special.classList.remove("invalid");
            special.classList.add("valid");
        } else {
            special.classList.remove("valid");
            special.classList.add("invalid");
        }

        // Validate length
        if (password.value.length >= 8) {
            length.classList.remove("invalid");
            length.classList.add("valid");
        } else {
            length.classList.remove("valid");
            length.classList.add("invalid");
        }
    });
});