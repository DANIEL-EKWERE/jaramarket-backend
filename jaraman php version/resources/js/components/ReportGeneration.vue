<template>
    <div>
        <h2>Generate Reports</h2>
        <button @click="generateOrderReport">Generate Order Report</button>
        <button @click="generatePaymentReport">Generate Payment Report</button>
        <div v-if="reportData">
            <h3>Report Data</h3>
            <pre>{{ reportData }}</pre>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            reportData: null
        };
    },
    methods: {
        generateOrderReport() {
            axios.get('/api/reports/orders')
                .then(response => {
                    this.reportData = response.data;
                });
        },
        generatePaymentReport() {
            axios.get('/api/reports/payments')
                .then(response => {
                    this.reportData = response.data;
                });
        }
    }
}
</script>