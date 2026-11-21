(() => {
    const form = document.getElementById("accountForm");
    const inputNom = document.getElementById("NomInput");
    const inputPrenom = document.getElementById("PrenomInput");
    const inputAllergie = document.getElementById("AllergieInput");
    const inputConvives = document.getElementById("NbConvivesInput");
    const message = document.getElementById("account-message");

    function showMessage(text, isError) {
        message.textContent = text;
        message.classList.remove("d-none", "text-danger", "text-success");
        message.classList.add(isError ? "text-danger" : "text-success");
    }

    async function loadAccount() {
        try {
            const user = await apiFetch("account/me");
            inputNom.value = user.lastName ?? "";
            inputPrenom.value = user.firstName ?? "";
            inputAllergie.value = user.allergy ?? "";
            inputConvives.value = user.guestNumber ?? "";
        } catch (error) {
            showMessage("Impossible de charger vos informations.", true);
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const lastName = inputNom.value.trim();
        const firstName = inputPrenom.value.trim();
        const guestNumber = inputConvives.value === "" ? null : Number(inputConvives.value);

        if (lastName === "" || firstName === "") {
            showMessage("Le nom et le prénom sont obligatoires.", true);
            return;
        }
        if (guestNumber !== null && (!Number.isInteger(guestNumber) || guestNumber < 1 || guestNumber > 50)) {
            showMessage("Le nombre de convives doit être un entier entre 1 et 50.", true);
            return;
        }

        try {
            await apiFetch("account/edit", "PUT", {
                firstName,
                lastName,
                allergy: inputAllergie.value.trim() || null,
                guestNumber,
            });
            showMessage("Vos informations ont bien été modifiées.", false);
        } catch (error) {
            showMessage("La modification a échoué. Réessayez.", true);
        }
    });

    loadAccount();
})();
