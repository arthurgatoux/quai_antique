(() => {
    const form = document.getElementById("reservationForm");
    const inputNom = document.getElementById("NomInput");
    const inputPrenom = document.getElementById("PrenomInput");
    const inputAllergie = document.getElementById("AllergieInput");
    const inputConvives = document.getElementById("NbConvivesInput");
    const inputDate = document.getElementById("DateInput");
    const radioMidi = document.getElementById("midiRadio");
    const radioSoir = document.getElementById("soirRadio");
    const selectHour = document.getElementById("selectHour");
    const errorBox = document.getElementById("reservation-error");
    const btnReserver = document.getElementById("btn-reserver");
    const btnAnnuler = document.getElementById("btn-annuler");
    const cancelModal = document.getElementById("CancelResaModal");
    const cancelSummary = document.getElementById("cancel-resa-summary");
    const cancelError = document.getElementById("cancel-resa-error");

    const HOURS = {
        midi: ["12:00", "12:15", "12:30", "12:45", "13:00", "13:15", "13:30"],
        soir: ["19:30", "19:45", "20:00", "20:15", "20:30", "20:45", "21:00"],
    };

    // Présent dans l'URL (/reserver?id=3) quand on modifie une réservation existante
    const bookingId = new URLSearchParams(window.location.search).get("id");

    const currentService = () => (radioMidi.checked ? "midi" : "soir");

    function showError(box, message) {
        box.textContent = message;
        box.classList.toggle("d-none", message === "");
    }

    function fillHours(selected = null) {
        selectHour.replaceChildren(
            ...HOURS[currentService()].map((hour) => {
                const option = buildElement("option", "", hour);
                option.value = hour;
                return option;
            }),
        );
        if (selected && HOURS[currentService()].includes(selected)) {
            selectHour.value = selected;
        }
    }

    function todayIso() {
        const now = new Date();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");
        return now.getFullYear() + "-" + month + "-" + day;
    }

    async function init() {
        inputDate.min = todayIso();
        fillHours();
        radioMidi.addEventListener("change", () => fillHours());
        radioSoir.addEventListener("change", () => fillHours());

        try {
            const user = await apiFetch("account/me");
            inputNom.value = user.lastName ?? "";
            inputPrenom.value = user.firstName ?? "";

            if (bookingId === null) {
                // Nouvelle réservation : on propose les habitudes du client
                inputAllergie.value = user.allergy ?? "";
                inputConvives.value = user.guestNumber ?? "";
                return;
            }

            const booking = await apiFetch("booking/" + encodeURIComponent(bookingId));
            inputAllergie.value = booking.allergy ?? "";
            inputConvives.value = booking.guestNumber;
            inputDate.value = booking.orderDate;
            (booking.orderHour < "15:00" ? radioMidi : radioSoir).checked = true;
            fillHours(booking.orderHour);

            btnReserver.textContent = "Modifier la réservation";
            btnAnnuler.classList.remove("d-none");
            cancelSummary.textContent =
                booking.orderDate.split("-").reverse().join("/") + " à " + booking.orderHour +
                " pour " + booking.guestNumber + (booking.guestNumber > 1 ? " personnes" : " personne");
        } catch (error) {
            showError(
                errorBox,
                error.status === 404 ? "Cette réservation est introuvable." : "Impossible de charger la page pour le moment.",
            );
            btnReserver.disabled = true;
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const guestNumber = Number(inputConvives.value);
        if (!Number.isInteger(guestNumber) || guestNumber < 1 || guestNumber > 50) {
            showError(errorBox, "Indiquez un nombre de convives entre 1 et 50.");
            return;
        }
        if (inputDate.value === "" || inputDate.value < todayIso()) {
            showError(errorBox, "Choisissez une date à partir d'aujourd'hui.");
            return;
        }

        const booking = {
            guestNumber,
            orderDate: inputDate.value,
            orderHour: selectHour.value,
            allergy: inputAllergie.value.trim() || null,
        };

        try {
            if (bookingId === null) {
                await apiFetch("booking", "POST", booking);
            } else {
                await apiFetch("booking/" + encodeURIComponent(bookingId), "PUT", booking);
            }
            window.location.href = "/allResa";
        } catch (error) {
            showError(errorBox, error.message);
        }
    });

    document.getElementById("btn-confirm-cancel").addEventListener("click", async () => {
        try {
            await apiFetch("booking/" + encodeURIComponent(bookingId), "DELETE");
            bootstrap.Modal.getOrCreateInstance(cancelModal).hide();
            window.location.href = "/allResa";
        } catch (error) {
            showError(cancelError, "L'annulation a échoué. Réessayez.");
        }
    });

    init();
})();
