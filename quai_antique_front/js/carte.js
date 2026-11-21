(() => {
    const menusContainer = document.getElementById("allMenus");
    const categoriesContainer = document.getElementById("allCategories");

    const formatPrice = (price) => price + " €";

    function menuCard(menu, highlighted) {
        const body = buildElement("div", "card-body d-flex flex-column");
        body.append(
            buildElement("h3", "card-title h5 text-primary", menu.title),
            buildElement("p", "card-text text-justify", menu.description),
            buildElement("p", "card-text fw-bold fs-4 mt-auto", formatPrice(menu.price)),
        );

        const card = buildElement("div", "card h-100 text-center" + (highlighted ? " border-primary" : ""));
        card.appendChild(body);

        const column = buildElement("div", "col");
        column.appendChild(card);
        return column;
    }

    function foodItem(food, dark) {
        const text = buildElement("div");
        text.appendChild(buildElement("div", "fw-bold", food.title));
        if (food.description) {
            text.appendChild(buildElement("div", "text-justify", food.description));
        }

        const item = buildElement(
            "li",
            "list-group-item d-flex justify-content-between align-items-start" + (dark ? " bg-black text-white" : ""),
        );
        item.append(text, buildElement("span", "badge bg-primary rounded-pill ms-3", formatPrice(food.price)));
        return item;
    }

    function categorySection(category, dark) {
        const list = buildElement("ul", "list-group list-group-flush");
        category.foods.forEach((food) => list.appendChild(foodItem(food, dark)));

        const container = buildElement("div", "container py-5 px-4");
        container.append(buildElement("h2", "text-center text-primary mb-4", category.title), list);

        const section = buildElement("section", dark ? "bg-black text-white" : "");
        section.appendChild(container);
        return section;
    }

    async function loadCarte() {
        try {
            const [menus, categories] = await Promise.all([apiFetch("menu"), apiFetch("category")]);

            menusContainer.replaceChildren(
                ...menus.map((menu, index) => menuCard(menu, menus.length === 3 && index === 1)),
            );

            // Seules les catégories qui contiennent des plats sont affichées, en alternant les fonds
            categoriesContainer.replaceChildren(
                ...categories
                    .filter((category) => category.foods.length > 0)
                    .map((category, index) => categorySection(category, index % 2 === 0)),
            );
        } catch (error) {
            menusContainer.replaceChildren(buildElement("p", "text-center", "Impossible de charger la carte pour le moment."));
        }
    }

    loadCarte();
})();
