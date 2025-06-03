const signOutButton = document.getElementById("signOutBtn");
        if (signOutButton) {
            signOutButton.addEventListener("click", function(event) {
                event.preventDefault(); 
                window.location.href = this.href;
            });
        }