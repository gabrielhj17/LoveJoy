document.addEventListener('DOMContentLoaded', function() {
    var password = document.getElementById("pword");
    var passwordCheck = document.getElementById("pwordcheck");
    var text = document.getElementById("pwordMatchText");

    text.style.visibility = "hidden";

    passwordCheck.addEventListener("keyup", function(event){
        if(password.value == passwordCheck.value) {
            text.style.display = "none";
       } else if (passwordCheck.value !== "") {
            text.style.visibility = "visible";
            text.style.color = "red";
        } else {
            text.style.visibility = "hidden";
        }
    })

    password.addEventListener("keyup", checkPasswordMatch);
    passwordCheck.addEventListener("keyup", checkPasswordMatch);
});