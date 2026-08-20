<template>
  <div class="bg-white rounded-lg shadow-md p-6 max-w-md mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
      Vue.js 3 Component
    </h2>
    <p class="text-gray-600 mb-4">
      This is a Vue.js 3 component successfully integrated with Laravel {{ laravelVersion }}!
    </p>
    <div class="mb-4">
      <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
        Enter a message:
      </label>
      <input
        id="message"
        v-model="message"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        placeholder="Type something..."
      />
    </div>
    <div class="mb-4">
      <p class="text-sm text-gray-600">
        You typed: <span class="font-semibold text-blue-600">{{ message || 'Nothing yet...' }}</span>
      </p>
    </div>
    <button
      @click="incrementCounter"
      class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200"
    >
      Click me! ({{ counter }})
    </button>
    <div class="mt-4 text-center">
      <span class="text-xs text-gray-500">
        Vue.js {{ vueVersion }} + Laravel {{ laravelVersion }} + Vite
      </span>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'ExampleComponent',
  setup() {
    const message = ref('')
    const counter = ref(0)
    const vueVersion = '3.x'
    const laravelVersion = '12'

    const incrementCounter = () => {
      counter.value++
    }

    return {
      message,
      counter,
      vueVersion,
      laravelVersion,
      incrementCounter
    }
  }
}
</script>

<style scoped>
/* Component-specific styles can go here */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
