<template>
    <div>
        <h2>Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in orders" :key="order.id">
                    <td>{{ order.id }}</td>
                    <td>{{ order.user_id }}</td>
                    <td>{{ order.total }}</td>
                    <td>{{ order.status }}</td>
                    <td>
                        <button @click="updateOrderStatus(order.id)">Update</button>
                        <button @click="deleteOrder(order.id)">Delete</button>
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
            orders: []
        };
    },
    mounted() {
        this.fetchOrders();
    },
    methods: {
        fetchOrders() {
            axios.get('/api/orders')
                .then(response => {
                    this.orders = response.data;
                });
        },
        updateOrderStatus(orderId) {
            // Logic to update order status
        },
        deleteOrder(orderId) {
            // Logic to delete order
        }
    }
}
</script>