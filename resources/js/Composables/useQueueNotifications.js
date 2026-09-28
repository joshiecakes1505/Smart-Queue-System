import { onBeforeUnmount, onMounted, ref } from 'vue'

/**
 * Turns the queue-status payload the public tracker already polls into
 * one-shot "approaching" / "called" notifications (modal + short sound).
 *
 * It adds no polling and no new endpoint: the page calls notify() with the
 * same response it already renders. Deduplication uses identifiers the API
 * already returns:
 *
 *   called      -> start_time  (stamped once when a cashier calls the ticket)
 *   approaching -> created_at  (reinstating a skipped ticket moves it, so a
 *                               ticket that comes back can approach again)
 */

const SOUND_PREF_KEY = 'queue-notify:sound'
const SEEN_KEY = 'queue-notify:seen'
const SEEN_LIMIT = 40

const SOUND_SRC = {
  approaching: '/sounds/queue-approaching.wav',
  called: '/sounds/queue-called.wav',
}

// localStorage throws in private mode / with site data blocked. Never let
// storage break the tracker: fall back to in-memory behaviour instead.
const safeGet = (key) => {
  try {
    return window.localStorage.getItem(key)
  } catch {
    return null
  }
}

const safeSet = (key, value) => {
  try {
    window.localStorage.setItem(key, value)
  } catch {
    /* preference simply won't persist */
  }
}

const readSeen = () => {
  try {
    const parsed = JSON.parse(safeGet(SEEN_KEY) || '[]')
    return Array.isArray(parsed) ? parsed.filter((key) => typeof key === 'string') : []
  } catch {
    return []
  }
}

export function useQueueNotifications({ approachingThreshold = 2 } = {}) {
  const soundEnabled = ref(safeGet(SOUND_PREF_KEY) !== 'off')
  const soundBlocked = ref(false)
  const activeEvent = ref(null)

  const queued = []
  const seen = new Set(readSeen())
  const players = {}
  let hasUserGesture = false

  const rememberSeen = (key) => {
    seen.add(key)

    // Keep the stored list bounded. Keys carry a timestamp, so evicting the
    // oldest can never permanently suppress a genuinely new event.
    const trimmed = Array.from(seen).slice(-SEEN_LIMIT)
    seen.clear()
    trimmed.forEach((item) => seen.add(item))
    safeSet(SEEN_KEY, JSON.stringify(trimmed))
  }

  const player = (kind) => {
    if (!players[kind]) {
      const audio = new Audio(SOUND_SRC[kind])
      audio.preload = 'auto'
      players[kind] = audio
    }

    return players[kind]
  }

  const playSound = (kind) => {
    if (!soundEnabled.value) return

    try {
      const audio = player(kind)
      audio.currentTime = 0

      const attempt = audio.play()

      if (attempt && typeof attempt.catch === 'function') {
        attempt.catch((playError) => {
          // Autoplay policy, missing file, or a muted device. The modal has
          // already been shown, so this is never fatal.
          soundBlocked.value = true
          console.warn('Queue notification sound could not play:', playError?.message || playError)
        })
      }
    } catch (soundError) {
      soundBlocked.value = true
      console.warn('Queue notification sound unavailable:', soundError?.message || soundError)
    }
  }

  /**
   * Called from a real user gesture, so the browser lets us start audio.
   * Plays one ding as confirmation that sound now works.
   */
  const enableSound = () => {
    hasUserGesture = true
    soundEnabled.value = true
    soundBlocked.value = false
    safeSet(SOUND_PREF_KEY, 'on')
    playSound('approaching')
  }

  const toggleSound = () => {
    if (soundEnabled.value) {
      soundEnabled.value = false
      safeSet(SOUND_PREF_KEY, 'off')
      return
    }

    enableSound()
  }

  const describe = (queue) => {
    if (!queue || !queue.queue_number) return null

    if (queue.status === 'called') {
      return {
        kind: 'called',
        key: `called|${queue.queue_number}|${queue.start_time || ''}`,
        queueNumber: queue.queue_number,
        window: queue.cashier_window || null,
        serviceCategory: queue.service_category || null,
        clientType: queue.client_type || null,
        ahead: null,
      }
    }

    const ahead = queue.queues_ahead

    if (
      queue.status === 'waiting' &&
      typeof ahead === 'number' &&
      ahead >= 0 &&
      ahead <= approachingThreshold
    ) {
      return {
        kind: 'approaching',
        key: `approaching|${queue.queue_number}|${queue.created_at || ''}`,
        queueNumber: queue.queue_number,
        window: null,
        serviceCategory: queue.service_category || null,
        clientType: queue.client_type || null,
        ahead,
      }
    }

    return null
  }

  const present = (event) => {
    if (!activeEvent.value) {
      activeEvent.value = event
      return
    }

    // Being called outranks an approaching notice the client hasn't dismissed.
    if (event.kind === 'called' && activeEvent.value.kind === 'approaching') {
      activeEvent.value = event
      return
    }

    queued.push(event)
  }

  /**
   * Feed each poll response through here. Safe to call on every tick.
   */
  const notify = (queue) => {
    try {
      const event = describe(queue)

      if (!event || seen.has(event.key)) return

      rememberSeen(event.key)
      playSound(event.kind)
      present(event)
    } catch (notifyError) {
      // Notifications are additive: never let them break the status tracker.
      console.warn('Queue notification skipped:', notifyError?.message || notifyError)
    }
  }

  const dismiss = () => {
    activeEvent.value = queued.shift() || null
  }

  // Any tap or keypress gives the page permission to play audio, so most
  // clients never need the explicit "Enable sound" control.
  const markGesture = () => {
    if (hasUserGesture) return

    hasUserGesture = true
    soundBlocked.value = false
  }

  onMounted(() => {
    window.addEventListener('pointerdown', markGesture, { capture: true, once: true })
    window.addEventListener('keydown', markGesture, { capture: true, once: true })
  })

  onBeforeUnmount(() => {
    window.removeEventListener('pointerdown', markGesture, { capture: true })
    window.removeEventListener('keydown', markGesture, { capture: true })
  })

  return {
    activeEvent,
    soundEnabled,
    soundBlocked,
    notify,
    dismiss,
    toggleSound,
    enableSound,
  }
}
