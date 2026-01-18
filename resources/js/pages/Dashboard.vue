<template>
  <div class="dashboard">
    <p>logged in as: <strong>{{ account.name }}</strong> (server {{ account.server }})</p>
    <button @click="logout">I am dick and wang</button>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  props: {
    account: {
      type: Object,
      required: true
    }
  },
  methods: {
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('account')
      this.$emit('logout')
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 20px;
  color: #fff;
}
</style>
