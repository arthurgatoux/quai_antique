(() => {
    const form = document.getElementById("passwordForm");
    const inputPassword = document.getElementById("PasswordInput");
    const inputConfirm = document.getElementById("ValidatePasswordInput");
    const message = document.getElementById("password-message");

    // Même règle que sur la page d'inscription
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/;

    function setValidity(input, isValid) {
        input.classList.toggle("is-valid", isValid);
        input.classList.toggle("is-invalid", !isValid);
        return isValid;
    }

    function showMessage(text, isError) {
        message.textContent = text;
        message.classList.remove("d-none", "text-danger", "text-success");
        message.classList.add(isError ? "text-danger" : "text-success");
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const passwordOk = setValidity(inputPassword, passwordRegex.test(inputPassword.value));
        const confirmOk = setValidity(inputConfirm, inputPassword.value === inputConfirm.value);
        if (!passwordOk || !confirmOk) {
            return;
        }

        try {
            await apiFetch("account/edit", "PUT", { password: inputPassword.value });
            form.reset();
            inputPassword.classList.remove("is-valid");
            inputConfirm.classList.remove("is-valid");
            showMessage("Votre mot de passe a bien été modifié.", false);
        } catch (error) {
            showMessage("La modification a échoué. Réessayez.", true);
        }
    });
})();
