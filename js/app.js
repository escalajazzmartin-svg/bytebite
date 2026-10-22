const inventory = [];
const selectedIngredients = new Set();

let currentPage = "home";
let editingIngredientId = null;

// ==============================
// LOAD INVENTORY FROM DATABASE
// ==============================

async function loadInventoryFromDatabase() {
  try {
    const response = await apiRequest("ingredients/get.php");

    if (!response.success || !Array.isArray(response.data)) {
      throw new Error("Invalid inventory data.");
    }

    inventory.length = 0;

    response.data.forEach((item) => {
      inventory.push({
        id: Number(item.id),
        name: item.name || "",
        quantity: Number(item.quantity) || 0,
        unit: item.unit || "",
        expiry: item.expiry_date || "",
        lowStock: Number(item.low_stock) === 1,
        category: "Kitchen"
      });
    });

    renderInventory();
    updateHomeCounts();
    renderSelectedIngredients();

  } catch (error) {
    console.error("Failed to load inventory:", error);

    showToast(
      error.message || "Failed to load inventory.",
      "error"
    );
  }
}

// ==============================
// INVENTORY STATUS
// ==============================

function getIngredientStatus(item) {
  if (item.lowStock) {
    return {
      className: "status-low",
      text: "Low Stock"
    };
  }

  if (item.expiry) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const expiryDate = new Date(item.expiry);
    expiryDate.setHours(0, 0, 0, 0);

    const difference =
      expiryDate.getTime() - today.getTime();

    const days =
      Math.ceil(difference / (1000 * 60 * 60 * 24));

    if (days < 0) {
      return {
        className: "status-expired",
        text: "Expired"
      };
    }

    if (days <= 3) {
      return {
        className: "status-expiring",
        text: "Expiring Soon"
      };
    }
  }

  return {
    className: "status-good",
    text: "Available"
  };
}

// ==============================
// RENDER INVENTORY
// ==============================

function renderInventory() {
  const inventoryList =
    document.getElementById("inventoryList");

  if (!inventoryList) {
    return;
  }

  const searchInput =
    document.getElementById("inventorySearch");

  const searchTerm =
    searchInput?.value.trim().toLowerCase() || "";

  const filteredInventory =
    inventory.filter((item) => {
      return item.name
        .toLowerCase()
        .includes(searchTerm);
    });

  inventoryList.innerHTML = "";

  if (filteredInventory.length === 0) {
    inventoryList.innerHTML = `
      <div class="empty-inventory">
        <p>No ingredients found.</p>
      </div>
    `;

    return;
  }

  filteredInventory.forEach((item) => {
    const inventoryIndex =
      inventory.findIndex(
        (ingredient) => ingredient.id === item.id
      );

    const status =
      getIngredientStatus(item);

    const card =
      document.createElement("div");

    card.className = "inventory-card";

    card.innerHTML = `
      <div class="inventory-card-info">

        <h3>
          ${escapeHTML(item.name)}
        </h3>

        <p>
          ${escapeHTML(String(item.quantity))}
          ${escapeHTML(item.unit)}
          ·
          ${escapeHTML(item.category || "Kitchen")}
        </p>

        <span class="${status.className}">
          ${escapeHTML(status.text)}
        </span>

      </div>

      <button
        type="button"
        class="delete-ingredient"
        data-index="${inventoryIndex}"
        aria-label="Delete ${escapeHTML(item.name)}"
      >
        ×
      </button>
    `;

    inventoryList.appendChild(card);
  });

  setupDeleteButtons();
}

// ==============================
// DELETE BUTTONS
// ==============================

function setupDeleteButtons() {
  document
    .querySelectorAll(".delete-ingredient")
    .forEach((button) => {

      button.addEventListener("click", async () => {

        const index =
          Number(button.dataset.index);

        if (
          Number.isNaN(index) ||
          !inventory[index]
        ) {
          return;
        }

        const ingredient =
          inventory[index];

        const confirmed =
          confirm(
            `Delete "${ingredient.name}" from your inventory?`
          );

        if (!confirmed) {
          return;
        }

        await deleteIngredient(
          ingredient.id
        );
      });
    });
}

// ==============================
// ADD INGREDIENT
// ==============================

async function addIngredient() {

  const nameInput =
    document.getElementById("ingredientName");

  const quantityInput =
    document.getElementById("ingredientQuantity");

  const unitInput =
    document.getElementById("ingredientUnit");

  const expiryInput =
    document.getElementById("ingredientExpiry");

  const lowStockInput =
    document.getElementById("ingredientLowStock");


  // --------------------------------
  // CHECK ELEMENTS
  // --------------------------------

  if (
    !nameInput ||
    !quantityInput ||
    !unitInput
  ) {

    console.error(
      "Ingredient form elements were not found."
    );

    showToast(
      "Ingredient form is not available.",
      "error"
    );

    return;
  }


  // --------------------------------
  // GET VALUES
  // --------------------------------

  const name =
    nameInput.value.trim();

  const quantity =
    quantityInput.value.trim();

  const unit =
    unitInput.value.trim();

  const expiryDate =
    expiryInput
      ? expiryInput.value
      : "";

  const lowStock =
    lowStockInput &&
      lowStockInput.checked
      ? "1"
      : "0";


  // --------------------------------
  // VALIDATION
  // --------------------------------

  if (name === "") {

    showToast(
      "Please enter an ingredient name.",
      "error"
    );

    nameInput.focus();

    return;
  }


  if (
    quantity === "" ||
    !isFinite(Number(quantity)) ||
    Number(quantity) < 0
  ) {

    showToast(
      "Please enter a valid quantity.",
      "error"
    );

    quantityInput.focus();

    return;
  }


  if (unit === "") {

    showToast(
      "Please select a unit.",
      "error"
    );

    unitInput.focus();

    return;
  }


  // --------------------------------
  // CREATE FORM DATA
  // --------------------------------

  const formData =
    new FormData();

  formData.append(
    "name",
    name
  );

  formData.append(
    "quantity",
    quantity
  );

  formData.append(
    "unit",
    unit
  );

  formData.append(
    "expiry_date",
    expiryDate
  );

  formData.append(
    "low_stock",
    lowStock
  );


  // --------------------------------
  // DISABLE BUTTON
  // --------------------------------

  const submitButton =
    document.querySelector(
      "#ingredientForm .save-ingredient"
    );

  if (submitButton) {

    submitButton.disabled = true;

    submitButton.textContent =
      "Adding...";
  }


  // --------------------------------
  // SEND TO PHP
  // --------------------------------

  try {

    console.log(
      "Sending ingredient:",
      {
        name,
        quantity,
        unit,
        expiryDate,
        lowStock
      }
    );


    const response =
      await apiRequest(
        "ingredients/add.php",
        {
          method: "POST",
          body: formData
        }
      );


    console.log(
      "Add ingredient response:",
      response
    );


    if (!response.success) {

      throw new Error(
        response.message ||
        "Failed to add ingredient."
      );
    }


    // --------------------------------
    // SUCCESS
    // --------------------------------

    showToast(
      "Ingredient added successfully!",
      "success"
    );


    // Clear form

    const form =
      document.getElementById(
        "ingredientForm"
      );

    if (form) {
      form.reset();
    }


    // Close modal

    closeIngredientModal();


    // Reload database inventory

    await loadInventoryFromDatabase();


    // Update home counters

    updateHomeCounts();


  } catch (error) {

    console.error(
      "Add ingredient error:",
      error
    );


    showToast(
      error.message ||
      "Failed to add ingredient.",
      "error"
    );


  } finally {

    // Re-enable button

    if (submitButton) {

      submitButton.disabled = false;

      submitButton.textContent =
        "Add to Kitchen";
    }

  }
}

// ==============================
// DELETE INGREDIENT
// ==============================

async function deleteIngredient(id) {
  if (!id) {
    return;
  }

  try {
    const formData =
      new FormData();

    formData.append(
      "id",
      String(id)
    );

    const response =
      await apiRequest(
        "ingredients/delete.php",
        {
          method: "POST",
          body: formData
        }
      );

    if (!response.success) {
      throw new Error(
        response.message ||
        "Failed to delete ingredient."
      );
    }

    selectedIngredients.delete(
      Number(id)
    );

    showToast(
      "Ingredient deleted successfully.",
      "success"
    );

    await loadInventoryFromDatabase();

  } catch (error) {
    console.error(
      "Failed to delete ingredient:",
      error
    );

    showToast(
      error.message ||
      "Failed to delete ingredient.",
      "error"
    );
  }
}

// ==============================
// HOME COUNTS
// ==============================

function updateHomeCounts() {
  const ingredientsCount =
    document.getElementById(
      "ingredientsCount"
    );

  const expiringCount =
    document.getElementById(
      "expiringCount"
    );

  const availableCount =
    document.getElementById(
      "availableCount"
    );

  if (ingredientsCount) {
    ingredientsCount.textContent =
      inventory.length;
  }

  let expiring = 0;
  let available = 0;

  inventory.forEach((item) => {
    const status =
      getIngredientStatus(item);

    if (
      status.className ===
      "status-expiring" ||
      status.className ===
      "status-expired"
    ) {
      expiring++;
    } else {
      available++;
    }
  });

  if (expiringCount) {
    expiringCount.textContent =
      expiring;
  }

  if (availableCount) {
    availableCount.textContent =
      available;
  }
}

// ==============================
// SEARCH
// ==============================

function setupInventorySearch() {
  const searchInput =
    document.getElementById(
      "inventorySearch"
    );

  if (!searchInput) {
    return;
  }

  searchInput.addEventListener(
    "input",
    renderInventory
  );
}

// ==============================
// FILTERS
// ==============================

function setupInventoryFilters() {
  const filterButtons =
    document.querySelectorAll(
      "[data-filter]"
    );

  filterButtons.forEach((button) => {
    button.addEventListener(
      "click",
      () => {

        filterButtons.forEach(
          (item) =>
            item.classList.remove(
              "active"
            )
        );

        button.classList.add(
          "active"
        );

        const filter =
          button.dataset.filter;

        const inventoryList =
          document.getElementById(
            "inventoryList"
          );

        if (!inventoryList) {
          return;
        }

        const searchInput =
          document.getElementById(
            "inventorySearch"
          );

        const searchTerm =
          searchInput?.value
            .trim()
            .toLowerCase() || "";

        let filtered =
          inventory.filter((item) =>
            item.name
              .toLowerCase()
              .includes(searchTerm)
          );

        if (filter === "low") {
          filtered =
            filtered.filter(
              (item) => item.lowStock
            );
        }

        if (filter === "expiring") {
          filtered =
            filtered.filter((item) => {
              const status =
                getIngredientStatus(item);

              return (
                status.className ===
                "status-expiring" ||
                status.className ===
                "status-expired"
              );
            });
        }

        inventoryList.innerHTML = "";

        if (filtered.length === 0) {
          inventoryList.innerHTML = `
            <div class="empty-inventory">
              <p>No ingredients found.</p>
            </div>
          `;

          return;
        }

        filtered.forEach((item) => {

          const inventoryIndex =
            inventory.findIndex(
              (ingredient) =>
                ingredient.id === item.id
            );

          const status =
            getIngredientStatus(item);

          const card =
            document.createElement(
              "div"
            );

          card.className =
            "inventory-card";

          card.innerHTML = `
            <div class="inventory-card-info">

              <h3>
                ${escapeHTML(item.name)}
              </h3>

              <p>
                ${escapeHTML(String(item.quantity))}
                ${escapeHTML(item.unit)}
                ·
                ${escapeHTML(
            item.category ||
            "Kitchen"
          )}
              </p>

              <span class="${status.className}">
                ${escapeHTML(status.text)}
              </span>

            </div>

            <button
              type="button"
              class="delete-ingredient"
              data-index="${inventoryIndex}"
              aria-label="Delete ${escapeHTML(item.name)}"
            >
              ×
            </button>
          `;

          inventoryList.appendChild(card);
        });

        setupDeleteButtons();
      }
    );
  });
}

// ==============================
// SELECTABLE INGREDIENTS
// ==============================

function renderSelectedIngredients() {
  const container =
    document.getElementById(
      "selectedIngredients"
    );

  if (!container) {
    return;
  }

  container.innerHTML = "";

  inventory.forEach((item) => {

    if (
      !selectedIngredients.has(
        item.id
      )
    ) {
      return;
    }

    const tag =
      document.createElement(
        "span"
      );

    tag.className =
      "selected-ingredient";

    tag.innerHTML = `
      ${escapeHTML(item.name)}
      <button
        type="button"
        data-id="${item.id}"
        aria-label="Remove ${escapeHTML(item.name)}"
      >
        ×
      </button>
    `;

    container.appendChild(tag);
  });

  container
    .querySelectorAll("button")
    .forEach((button) => {

      button.addEventListener(
        "click",
        () => {

          selectedIngredients.delete(
            Number(button.dataset.id)
          );

          renderSelectedIngredients();
          renderSelectableOptions();
        }
      );
    });
}

function renderSelectableOptions() {
  const container =
    document.getElementById(
      "ingredientOptions"
    );

  if (!container) {
    return;
  }

  container.innerHTML = "";

  inventory.forEach((item) => {

    const label =
      document.createElement(
        "label"
      );

    label.className =
      "ingredient-option";

    label.innerHTML = `
      <input
        type="checkbox"
        value="${item.id}"
        ${selectedIngredients.has(
      item.id
    )
        ? "checked"
        : ""
      }
      >

      <span>
        ${escapeHTML(item.name)}
      </span>
    `;

    const checkbox =
      label.querySelector(
        "input"
      );

    checkbox.addEventListener(
      "change",
      () => {

        if (checkbox.checked) {
          selectedIngredients.add(
            item.id
          );
        } else {
          selectedIngredients.delete(
            item.id
          );
        }

        renderSelectedIngredients();
      }
    );

    container.appendChild(label);
  });
}

function setupSelectableOptions() {
  renderSelectableOptions();
}

// ==============================
// RECIPE GENERATOR
// ==============================

function setupRecipeGenerator() {
  const button =
    document.getElementById(
      "generateRecipeBtn"
    );

  if (!button) {
    return;
  }

  button.addEventListener(
    "click",
    generateRecipe
  );
}

async function generateRecipe() {
  if (
    selectedIngredients.size === 0
  ) {
    showToast(
      "Please select at least one ingredient.",
      "error"
    );
    return;
  }

  const ingredients =
    inventory
      .filter((item) =>
        selectedIngredients.has(
          item.id
        )
      )
      .map((item) => ({
        name: item.name,
        quantity: item.quantity,
        unit: item.unit
      }));

  console.log(
    "Selected ingredients:",
    ingredients
  );

  showToast(
    "Recipe generation is ready for AI integration.",
    "success"
  );
}

// ==============================
// PAGE NAVIGATION
// ==============================

function setupPageNavigation() {
  document
    .querySelectorAll("[data-page]")
    .forEach((element) => {

      element.addEventListener("click", (event) => {

        // Prevent buttons/links from doing their
        // default behavior when necessary
        event.preventDefault();

        const page = element.dataset.page;

        if (!page) {
          return;
        }

        showPage(page);
      });

    });
}

function showPage(page) {
  currentPage = page;

  // Show the selected page
  document
    .querySelectorAll(".page")
    .forEach((section) => {

      const isActive =
        section.id === `${page}Page`;

      section.classList.toggle(
        "active-page",
        isActive
      );

    });


  // Update bottom navigation
  document
    .querySelectorAll(".nav-item")
    .forEach((button) => {

      const isActive =
        button.dataset.page === page;

      button.classList.toggle(
        "active",
        isActive
      );

    });


  // Scroll back to the top
  window.scrollTo({
    top: 0,
    behavior: "smooth"
  });
}

// ==============================
// MODAL
// ==============================

function setupModal() {

  const addButton =
    document.getElementById(
      "addIngredientBtn"
    );

  const form =
    document.getElementById(
      "ingredientForm"
    );

  const modal =
    document.getElementById(
      "ingredientModal"
    );

  const closeButton =
    document.getElementById(
      "closeIngredientModal"
    );


  // --------------------------------
  // OPEN MODAL
  // --------------------------------

  if (addButton) {

    addButton.addEventListener(
      "click",
      openIngredientModal
    );

  }


  // --------------------------------
  // SUBMIT FORM
  // --------------------------------

  if (form) {

    form.addEventListener(
      "submit",
      async (event) => {

        event.preventDefault();

        await addIngredient();

      }
    );

  }


  // --------------------------------
  // CLOSE MODAL
  // --------------------------------

  if (closeButton) {

    closeButton.addEventListener(
      "click",
      closeIngredientModal
    );

  }


  // --------------------------------
  // CLICK OUTSIDE MODAL
  // --------------------------------

  if (modal) {

    modal.addEventListener(
      "click",
      (event) => {

        if (
          event.target === modal
        ) {

          closeIngredientModal();

        }

      }
    );

  }

}

function openIngredientModal() {

  const modal =
    document.getElementById(
      "ingredientModal"
    );

  if (!modal) {
    return;
  }


  const form =
    document.getElementById(
      "ingredientForm"
    );

  if (form) {
    form.reset();
  }


  modal.classList.add(
    "active"
  );

  modal.setAttribute(
    "aria-hidden",
    "false"
  );
}

function closeIngredientModal() {

  const modal =
    document.getElementById(
      "ingredientModal"
    );

  if (!modal) {
    return;
  }


  modal.classList.remove(
    "active"
  );

  modal.setAttribute(
    "aria-hidden",
    "true"
  );
}

// ==============================
// TOAST
// ==============================

function showToast(
  message,
  type = "success"
) {
  const toast =
    document.getElementById(
      "toast"
    );

  if (!toast) {
    return;
  }

  const toastMessage =
    toast.querySelector(
      ".toast-message"
    );

  if (toastMessage) {
    toastMessage.textContent =
      message;
  } else {
    toast.textContent =
      message;
  }

  toast.className =
    `toast ${type}`;

  toast.classList.add(
    "show"
  );

  clearTimeout(
    showToast.timeout
  );

  showToast.timeout =
    setTimeout(() => {
      toast.classList.remove(
        "show"
      );
    }, 3000);
}

// ==============================
// ESCAPE HTML
// ==============================

function escapeHTML(value) {
  return String(value)
    .replace(
      /&/g,
      "&amp;"
    )
    .replace(
      /</g,
      "&lt;"
    )
    .replace(
      />/g,
      "&gt;"
    )
    .replace(
      /"/g,
      "&quot;"
    )
    .replace(
      /'/g,
      "&#039;"
    );
}

// ==============================
// INITIALIZATION
// ==============================

document.addEventListener(
  "DOMContentLoaded",
  async () => {

    setupPageNavigation();

    setupInventorySearch();

    setupInventoryFilters();

    setupSelectableOptions();

    setupRecipeGenerator();

    setupModal();

    updateHomeCounts();

    renderInventory();

    renderSelectedIngredients();

    await loadInventoryFromDatabase();

    showPage("home");

  }
);