(() => {
    const container = document.getElementById("allImages");
    const editModal = document.getElementById("EditionPhotoModal");
    const deleteModal = document.getElementById("DeletePhotoModal");
    const photoForm = document.getElementById("photoForm");
    const titleInput = document.getElementById("NamePhotoInput");
    const fileInput = document.getElementById("ImageInput");
    const editTitle = document.getElementById("EditionPhotoModalLabel");
    const editError = document.getElementById("photo-error");
    const deleteTitle = document.getElementById("delete-photo-title");
    const deleteImage = document.getElementById("delete-photo-image");
    const deleteError = document.getElementById("delete-photo-error");

    let editedId = null;
    let deletedId = null;

    const imageUrl = (slug) => "/images/" + encodeURIComponent(slug);

    function showError(box, message) {
        box.textContent = message;
        box.classList.toggle("d-none", message === "");
    }

    function iconButton(icon, label, target, onClick) {
        const button = buildElement("button", "btn btn-outline-light");
        button.type = "button";
        button.setAttribute("aria-label", label);
        button.dataset.bsToggle = "modal";
        button.dataset.bsTarget = target;
        button.appendChild(buildElement("i", "bi " + icon));
        button.addEventListener("click", onClick);
        return button;
    }

    function renderPictures(pictures) {
        container.replaceChildren();

        if (pictures.length === 0) {
            container.appendChild(buildElement("p", "text-center p-3", "Aucune photo pour le moment."));
            return;
        }

        pictures.forEach((picture) => {
            const image = buildElement("img", "rounded w-100");
            image.src = imageUrl(picture.slug);
            image.alt = picture.title;

            const actions = buildElement("div", "action-image-buttons");
            actions.dataset.show = "admin";
            actions.append(
                iconButton("bi-pencil-square", "Modifier la photo " + picture.title, "#EditionPhotoModal", () => openEdition(picture)),
                iconButton("bi-trash", "Supprimer la photo " + picture.title, "#DeletePhotoModal", () => openDeletion(picture)),
            );

            const card = buildElement("div", "image-card text-white");
            card.append(image, buildElement("p", "titre-image", picture.title), actions);

            const column = buildElement("div", "col p-3");
            column.appendChild(card);
            container.appendChild(column);
        });

        // Les boutons créés ici doivent aussi être masqués pour les non-administrateurs
        showAndHideElementsForRoles();
    }

    async function loadPictures() {
        try {
            renderPictures(await apiFetch("picture"));
        } catch (error) {
            container.replaceChildren(buildElement("p", "text-center p-3", "Impossible de charger la galerie pour le moment."));
        }
    }

    function openEdition(picture = null) {
        editedId = picture ? picture.id : null;
        titleInput.value = picture ? picture.title : "";
        fileInput.value = picture ? picture.slug : "";
        editTitle.textContent = picture ? "Modifier la photo" : "Ajouter une photo";
        showError(editError, "");
    }

    function openDeletion(picture) {
        deletedId = picture.id;
        deleteTitle.textContent = picture.title;
        deleteImage.src = imageUrl(picture.slug);
        deleteImage.alt = picture.title;
        showError(deleteError, "");
    }

    document.getElementById("btn-add-photo").addEventListener("click", () => openEdition());

    photoForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const title = titleInput.value.trim();
        const slug = fileInput.value.trim();
        if (title === "" || slug === "") {
            showError(editError, "Le titre et le nom du fichier image sont obligatoires.");
            return;
        }

        try {
            if (editedId === null) {
                await apiFetch("picture", "POST", { title, slug });
            } else {
                await apiFetch("picture/" + editedId, "PUT", { title, slug });
            }
            bootstrap.Modal.getOrCreateInstance(editModal).hide();
            await loadPictures();
        } catch (error) {
            showError(editError, error.message);
        }
    });

    document.getElementById("btn-confirm-delete-photo").addEventListener("click", async () => {
        try {
            await apiFetch("picture/" + deletedId, "DELETE");
            bootstrap.Modal.getOrCreateInstance(deleteModal).hide();
            await loadPictures();
        } catch (error) {
            showError(deleteError, error.message);
        }
    });

    loadPictures();
})();
