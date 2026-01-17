<template>
    <div class="dashboard">
        <h1>Monser Dashboard</h1>
        <p>Welcome to the dashboard</p>
    </div>
</template>

<script setup>
// Vue 3 Composition API
</script>

<style lang="scss" scoped>
.dashboard {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #eee;
    font-family: "JetBrains Mono", monospace;

    h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        background: linear-gradient(90deg, #00d9ff, #00ff88);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    p {
        opacity: 0.7;
    }
}
</style>
