(() => {
    const container = document.getElementById("allReservations");

    // "2030-05-12" -> "12/05/2030"
    const formatDate = (isoDate) => isoDate.split("-").reverse().join("/");

    function reservationRow(booking, isAdmin) {
        const parts = [
            formatDate(booking.orderDate),
            booking.orderHour,
            booking.guestNumber + (booking.guestNumber > 1 ? " personnes" : " personne"),
            booking.allergy ? booking.allergy : "Pas d’allergie",
        ];
        if (isAdmin) {
            parts.unshift(booking.client.firstName + " " + booking.client.lastName);
        }

        // Un client ouvre sa réservation pour la modifier ; l'administrateur consulte la liste
        const row = buildElement("a");
        if (!isAdmin) {
            row.href = "/reserver?id=" + booking.id;
        }
        parts.forEach((part, index) => {
            if (index > 0) {
                row.append(" | ");
            }
            row.appendChild(buildElement("span", "", part));
        });
        return row;
    }

    async function loadReservations() {
        try {
            const bookings = await apiFetch("booking");
            const isAdmin = getRole() === "admin";

            if (bookings.length === 0) {
                container.replaceChildren(buildElement("p", "", "Vous n'avez aucune réservation pour le moment."));
                return;
            }
            container.replaceChildren(...bookings.map((booking) => reservationRow(booking, isAdmin)));
        } catch (error) {
            container.replaceChildren(buildElement("p", "", "Impossible de charger les réservations pour le moment."));
        }
    }

    loadReservations();
})();
