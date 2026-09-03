import { translate } from '@/i18n'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'
const TOKEN_KEY = 'surveyflow_token'

export class ApiError extends Error {
  constructor(message, status, details = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.details = details
  }
}

function readToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export async function apiRequest(path, options = {}) {
  const { method = 'GET', body, authenticated = true, headers = {} } = options
  const token = authenticated ? readToken() : null
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
    ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
  })

  const contentType = response.headers.get('content-type') || ''
  const payload = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    if (response.status === 401 && authenticated) {
      localStorage.removeItem(TOKEN_KEY)
      window.dispatchEvent(new CustomEvent('surveyflow:session-expired'))
    }

    throw new ApiError(
      payload?.message || payload?.error || translate('errors.request'),
      response.status,
      payload,
    )
  }

  return response.status === 204 ? null : payload
}

const serverErrorKeys = {
  'Email is already in use.': 'errors.emailInUse',
  'Email is invalid.': 'errors.invalidEmail',
  'Password must contain at least 8 characters.': 'errors.passwordShort',
  'Title cannot be blank.': 'errors.titleBlank',
  'Title cannot be longer than 255 characters.': 'errors.titleLong',
  'Survey not found.': 'errors.surveyNotFound',
  'Question not found.': 'errors.questionNotFound',
  'Submission not found.': 'errors.submissionNotFound',
  'You are not allowed to access this survey.': 'errors.accessDenied',
  'Archived surveys cannot be edited.': 'errors.archivedNotEditable',
  'Survey must contain at least one question before publication.': 'errors.needsQuestion',
  'Position is already used in this survey.': 'errors.positionUsed',
  'Question positions must be unique within a survey.': 'errors.uniqueQuestionPositions',
  'Choice questions must contain at least two options.': 'errors.minChoiceOptions',
  'Option labels must be unique.': 'errors.uniqueOptionLabels',
  'Option positions must be unique.': 'errors.uniqueOptionPositions',
  'Survey is not accepting submissions.': 'errors.surveyNotAccepting',
  'Rating answer must be an integer between 1 and 5.': 'errors.invalidRating',
}

function localizeServerMessage(message) {
  const key = serverErrorKeys[message]
  return key ? translate(key) : message
}

export function getErrorMessage(error) {
  const validationErrors = error?.details?.errors

  if (validationErrors && typeof validationErrors === 'object') {
    const messages = Object.values(validationErrors).flat().filter(Boolean)
    if (messages.length) return messages.map(localizeServerMessage).join(' ')
  }

  return error?.message ? localizeServerMessage(error.message) : translate('errors.unexpected')
}

export { TOKEN_KEY }
