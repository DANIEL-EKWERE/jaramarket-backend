<template>
    <div>
        <h2>Add Food Item</h2>
        <form @submit.prevent="addFood">
            <input type="text" v-model="newFood.name" placeholder="Food Name" required />
            <textarea v-model="newFood.description" placeholder="Description"></textarea>
            <h3>Ingredients</h3>
            <div v-for="(ingredient, index) in newFood.ingredients" :key="index">
                <input type="text" v-model="ingredient.name" placeholder="Ingredient Name" required />
                <input type="text" v-model="ingredient.quantity" placeholder="Quantity" required />
                <button @click="removeIngredient(index)">Remove</button>
            </div>
            <button @click="addIngredient">Add Ingredient</button>
            <h3>Preparation Steps</h3>
            <div v-for="(step, index) in newFood.steps" :key="index">
                <textarea v-model="step.description" placeholder="Step Description" required></textarea>
                <button @click="removeStep(index)">Remove</button>
            </div>
            <button @click="addStep">Add Step</button>
            <button type="submit">Add Food</button>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            newFood: {
                name: '',
                description: '',
                ingredients: [],
                steps: []
            }
        };
    },
    methods: {
        addFood() {
            axios.post('/api/foods', this.newFood)
                .then(response => {
                    alert('Food added successfully');
                    this.resetForm();
                });
        },
        addIngredient() {
            this.newFood.ingredients.push({ name: '', quantity: '' });
        },
        removeIngredient(index) {
            this.newFood.ingredients.splice(index, 1);
        },
        addStep() {
            this.newFood.steps.push({ description: '' });
        },
        removeStep(index) {
            this.newFood.steps.splice(index, 1);
        },
        resetForm() {
            this.newFood = { name: '', description: '', ingredients: [], steps: [] };
        }
    }
}
</script>