import { ref, onMounted, onBeforeUnmount } from 'vue'

export function usePolling(fn, interval = 5000) {
  const running = ref(false)
  let timer = null

  const start = async () => {
    if (running.value) return
    running.value = true
    await tick()
  }

  const stop = () => {
    running.value = false
    if (timer) clearTimeout(timer)
  }

  const tick = async () => {
    if (!running.value) return
    try {
      await fn()
    } finally {
      timer = setTimeout(tick, interval)
    }
  }

  onMounted(start)
  onBeforeUnmount(stop)

  return { start, stop, running }
}
