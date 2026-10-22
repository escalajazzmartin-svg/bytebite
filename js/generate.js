
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recipeForm');
    const ingredientSelection = document.getElementById('ingredientSelection');
    const selectedCount = document.getElementById('selectedIngredientCount');
    const clearButton = document.getElementById('clearIngredients');
    const generateButton = document.getElementById('generateButton');
    const results = document.getElementById('recipeResults');

    if (!form || !results) return;

    const ingredientInputs = ingredientSelection
        ? [...ingredientSelection.querySelectorAll(
            'input[name="ingredients[]"]'
        )]
        : [];

    function updateIngredientCount() {
        const count = ingredientInputs.filter(input => input.checked).length;

        if (selectedCount) {
            selectedCount.textContent = count === 0
                ? 'Select the ingredients you want to use.'
                : `${count} ingredient${count === 1 ? '' : 's'} selected.`;
        }
    }

    ingredientInputs.forEach(input => {
        input.addEventListener('change', updateIngredientCount);
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            ingredientInputs.forEach(input => {
                input.checked = false;
            });

            updateIngredientCount();
        });
    }

    function createElement(tag, text, className = '') {
        const element = document.createElement(tag);

        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }

        if (className) {
            element.className = className;
        }

        return element;
    }

    function showMessage(message) {
        results.replaceChildren(
            createElement('p', message, 'helper-text')
        );
    }

    function formatNumber(value) {
        if (value === undefined || value === null || value === '') {
            return '—';
        }

        return String(value);
    }

    function displayRecipes(recipes) {
        results.replaceChildren();

        if (!Array.isArray(recipes) || recipes.length === 0) {
            showMessage('No recipes were returned. Please try again.');
            return;
        }

        recipes.forEach(recipe => {
            const card = createElement('section', undefined, 'preference-section section');

            card.appendChild(
                createElement('h3', recipe.title || 'Untitled Recipe')
            );

            if (recipe.description) {
                card.appendChild(
                    createElement('p', recipe.description, 'helper-text')
                );
            }

            const details = createElement(
                'p',
                `${formatNumber(recipe.cooking_time_minutes)} minutes · ` +
                `${formatNumber(recipe.servings)} servings`,
                'helper-text'
            );
            card.appendChild(details);

            card.appendChild(createElement('h4', 'Ingredients'));

            const ingredientsList = document.createElement('ul');

            (Array.isArray(recipe.ingredients) ? recipe.ingredients : [])
                .forEach(ingredient => {
                    const quantity = formatNumber(ingredient.quantity);
                    const unit = ingredient.unit || '';
                    const name = ingredient.name || 'Ingredient';
                    const optional = ingredient.optional ? ' (optional)' : '';

                    ingredientsList.appendChild(
                        createElement(
                            'li',
                            `${quantity} ${unit} ${name}${optional}`.trim()
                        )
                    );
                });

            card.appendChild(ingredientsList);

            card.appendChild(createElement('h4', 'Instructions'));

            const instructionsList = document.createElement('ol');

            (Array.isArray(recipe.instructions) ? recipe.instructions : [])
                .forEach(step => {
                    instructionsList.appendChild(
                        createElement('li', step)
                    );
                });

            card.appendChild(instructionsList);

            const nutrition = recipe.nutrition || {};

            card.appendChild(createElement('h4', 'Estimated Nutrition'));

            card.appendChild(
                createElement(
                    'p',
                    `${formatNumber(nutrition.calories_kcal)} kcal · ` +
                    `Protein ${formatNumber(nutrition.protein_g)} g · ` +
                    `Carbs ${formatNumber(nutrition.carbs_g)} g · ` +
                    `Fat ${formatNumber(nutrition.fats_g)} g`,
                    'helper-text'
                )
            );

            results.appendChild(card);
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const selectedIngredients = ingredientInputs
            .filter(input => input.checked)
            .map(input => input.value);

        if (selectedIngredients.length === 0) {
            showMessage('Please select at least one ingredient.');
            return;
        }

        const selectedAppliances = [
            ...form.querySelectorAll('input[name="appliances[]"]:checked')
        ].map(input => input.value);

        const payload = {
            ingredients: selectedIngredients,
            meal_type: form.elements.meal_type.value,
            diet_preference: form.elements.diet_preference.value,
            cooking_time: form.elements.cooking_time.value,
            servings: Number(form.elements.servings.value),
            appliances: selectedAppliances
        };

        if (generateButton) {
            generateButton.disabled = true;

            const buttonText = generateButton.querySelector('span');
            if (buttonText) buttonText.textContent = 'Generating Recipes...';
        }

        showMessage('ByteBite is preparing your recipes. This may take a moment.');

        try {
            const response = await fetch('../api/generate-recipe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            let data;

            try {
                data = await response.json();
            } catch {
                throw new Error(
                    'The server returned an invalid response. Check your PHP configuration.'
                );
            }

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Recipe generation failed.'
                );
            }

            displayRecipes(data.data?.recipes || []);
        } catch (error) {
            showMessage(
                error.message || 'Unable to generate recipes. Please try again.'
            );
        } finally {
            if (generateButton) {
                generateButton.disabled = false;

                const buttonText = generateButton.querySelector('span');
                if (buttonText) buttonText.textContent = 'Generate Recipes';
            }
        }
    });

    updateIngredientCount();
});
