export const MANILA_TIME_ZONE = 'Asia/Manila'

const createFormatter = (options = {}) => {
  return new Intl.DateTimeFormat('en-PH', {
    timeZone: MANILA_TIME_ZONE,
    ...options,
  })
}

export const formatManilaTime = (value, options = {}) => {
  if (!value) return '—'

  return createFormatter({
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  }).format(new Date(value))
}

export const formatManilaDateTime = (value, options = {}) => {
  if (!value) return '—'

  return createFormatter({
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  }).format(new Date(value))
}
