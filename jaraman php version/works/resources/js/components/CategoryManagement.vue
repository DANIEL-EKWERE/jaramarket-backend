<template>
    <div>
        <h2>Categories</h2>
        <form @submit.prevent="addCategory">
            <input type="text" v-model="newCategory.name" placeholder="Category Name" required />
            <textarea v-model="newCategory.description" placeholder="Description"></textarea>
            <button type="submit">Add Category</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="category in categories" :key="category.id">
                    <td>{{ category.id }}</td>
                    <td>{{ category.name }}</td>
                    <td>{{ category.description }}</td>
                    <td>
                        <button @click="editCategory(category)">Edit</button>
                        <button @click="deleteCategory(category.id)">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    data() {
        return {
            categories: [],
            newCategory: {
                name: '',
                description: ''
            }
        };
    },
    mounted() {
        this.fetchCategories();
    },
    methods: {
        fetchCategories() {
            axios.get('/api/categories')
                .then(response => {
                    this.categories = response.data;
                });
        },
        addCategory() {
            axios.post('/api/categories', this.newCategory)
                .then(response => {
                    this.categories.push(response.data);
                    this.newCategory.name = '';
                    this.newCategory.description = '';
                });
        },
        editCategory(category) {
            // Logic to edit category
        },
        deleteCategory(categoryId) {
            axios.delete(`/api/categories/${categoryId}`)
                .then(response => {
                    this.categories = this.categories.filter(c => c.id !== categoryId);
                });
        }
    }
}
</script>