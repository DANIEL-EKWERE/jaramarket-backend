<template>
    <div>
        <h2>Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td>{{ user.id }}</td>
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <button @click="editUser(user.id)">Edit</button>
                        <button @click="toggleUserStatus(user.id)">{{ user.active ? 'Deactivate' : 'Activate' }}</button>
                        <button @click="deleteUser(user.id)">Delete</button>
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
            users: []
        };
    },
    mounted() {
        this.fetchUsers();
    },
    methods: {
        fetchUsers() {
            axios.get('/api/users')
                .then(response => {
                    this.users = response.data;
                });
        },
        editUser(userId) {
            // Logic to edit user
        },
        toggleUserStatus(userId) {
            // Logic to toggle user status
        },
        deleteUser(userId) {
            // Logic to delete user
        }
    }
}
</script>