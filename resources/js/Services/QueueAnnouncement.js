const DEFAULT_STORAGE_KEY = 'queue-announcement:last-announced'
const announcementQueue = []
let isSpeaking = false
let speakWatchdog = null
const SPEAK_TIMEOUT_MS = 15000
let hasSpeechUnlockListener = false

const enableSpeechOnInteraction = () => {
  if (typeof window === 'undefined' || hasSpeechUnlockListener) {
    return
  }

  hasSpeechUnlockListener = true

  const unlock = () => {
    try {
      window.speechSynthesis?.resume()
    } catch {
      return
    } finally {
      window.removeEventListener('pointerdown', unlock, true)
      window.removeEventListener('keydown', unlock, true)
      hasSpeechUnlockListener = false
      playNextAnnouncement()
    }
  }

  window.addEventListener('pointerdown', unlock, true)
  window.addEventListener('keydown', unlock, true)
}

const playNextAnnouncement = () => {
  if (isSpeaking || announcementQueue.length === 0) {
    return
  }

  if (
    typeof window === 'undefined'
    || !('speechSynthesis' in window)
    || typeof window.SpeechSynthesisUtterance === 'undefined'
  ) {
    announcementQueue.length = 0
    isSpeaking = false
    return
  }

  const nextMessage = announcementQueue[0]
  if (!nextMessage) {
    isSpeaking = false
    return
  }

  isSpeaking = true
  let hasStartedSpeaking = false

  const speech = new window.SpeechSynthesisUtterance(nextMessage)
  speech.lang = 'en-US'
  speech.rate = 1
  speech.pitch = 1

  const finishCurrent = () => {
    if (speakWatchdog) {
      window.clearTimeout(speakWatchdog)
      speakWatchdog = null
    }

    isSpeaking = false
    announcementQueue.shift()
    playNextAnnouncement()
  }

  const releaseForRetry = () => {
    if (speakWatchdog) {
      window.clearTimeout(speakWatchdog)
      speakWatchdog = null
    }

    isSpeaking = false
    enableSpeechOnInteraction()
  }

  speech.onstart = () => {
    hasStartedSpeaking = true
  }
  speech.onend = () => {
    finishCurrent()
  }
  speech.onerror = () => {
    if (!hasStartedSpeaking) {
      releaseForRetry()
      return
    }

    finishCurrent()
  }

  speakWatchdog = window.setTimeout(() => {
    if (!hasStartedSpeaking) {
      releaseForRetry()
      return
    }

    try {
      window.speechSynthesis.cancel()
    } catch {
      finishCurrent()
      return
    }

    finishCurrent()
  }, SPEAK_TIMEOUT_MS)

  try {
    window.speechSynthesis.resume()
    window.speechSynthesis.speak(speech)
  } catch {
    releaseForRetry()
  }
}

export function announceQueue(queueNumber, windowNumber) {
  if (
    typeof window === 'undefined'
    || !('speechSynthesis' in window)
    || typeof window.SpeechSynthesisUtterance === 'undefined'
  ) {
    return false
  }

  const normalizedQueueNumber = `${queueNumber || ''}`.trim()
  const normalizedWindowNumber = `${windowNumber || ''}`.trim()

  if (!normalizedQueueNumber || !normalizedWindowNumber) {
    return false
  }

  const message = `Now serving, Queue number ${normalizedQueueNumber}. Please proceed to Window ${normalizedWindowNumber}.`
  enableSpeechOnInteraction()
  announcementQueue.push(message)
  playNextAnnouncement()

  return true
}

export function getLastAnnouncedQueues(storageKey = DEFAULT_STORAGE_KEY) {
  if (typeof window === 'undefined' || !window.sessionStorage) {
    return {}
  }

  try {
    const rawValue = window.sessionStorage.getItem(storageKey)
    if (!rawValue) return {}

    const parsedValue = JSON.parse(rawValue)
    return parsedValue && typeof parsedValue === 'object' ? parsedValue : {}
  } catch {
    return {}
  }
}

export function setLastAnnouncedQueues(queueMap, storageKey = DEFAULT_STORAGE_KEY) {
  if (typeof window === 'undefined' || !window.sessionStorage) {
    return
  }

  try {
    window.sessionStorage.setItem(storageKey, JSON.stringify(queueMap || {}))
  } catch {
    return
  }
}
