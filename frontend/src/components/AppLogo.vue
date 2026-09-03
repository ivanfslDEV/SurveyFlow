<script setup>
defineProps({ compact: Boolean, light: Boolean })
</script>

<template>
  <RouterLink class="brand" :class="{ 'brand--light': light }" to="/surveys" aria-label="SurveyFlow">
    <span class="brand__mark" aria-hidden="true">
      <span></span><span></span><span></span>
    </span>
    <span v-if="!compact" class="brand__name">Survey<span>Flow</span></span>
  </RouterLink>
</template>

<style scoped>
.brand { display: inline-flex; align-items: center; gap: .7rem; color: var(--ink); text-decoration: none; }
.brand__mark { width: 2rem; height: 2rem; display: grid; gap: 3px; padding: 5px; border-radius: 9px; background: var(--ink); transform: rotate(-3deg); }
.brand__mark span { display: block; height: 4px; border-radius: 999px; background: var(--mint); }
.brand__mark span:nth-child(2) { width: 70%; }
.brand__mark span:nth-child(3) { width: 45%; }
.brand__name { font-size: 1.13rem; font-weight: 800; letter-spacing: -.045em; }
.brand__name span { color: var(--teal); }
.brand--light { color: white; }
.brand--light .brand__mark { background: white; }
</style>
