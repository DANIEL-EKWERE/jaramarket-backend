<template>
    <div>
        <h2>Website/App Settings</h2>
        <form @submit.prevent="saveSettings">
            <div v-for="(value, key) in settings" :key="key">
                <label :for="key">{{ key }}</label>
                <input type="text" :id="key" v-model="settings[key]" />
            </div>
            <button type="submit">Save Settings</button>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            settings: {}
        };
    },
    mounted() {
        this.fetchSettings();
    },
    methods: {
        fetchSettings() {
            axios.get('/api/settings')
                .then(response => {
                    this.settings = response.data;
                });
        },
        saveSettings() {
            axios.post('/api/settings', this.settings)
                .then(response => {
                    alert('Settings saved successfully');
                });
        }
    }
}
</script>