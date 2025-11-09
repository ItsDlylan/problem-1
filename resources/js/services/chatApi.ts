import type {
  ChatResponse,
  ConfirmAppointmentRequest,
  ConfirmAppointmentResponse,
} from '@/types/chat';

/**
 * Get CSRF token from cookies.
 */
function getCsrfToken(): string | null {
  const name = 'XSRF-TOKEN';
  const cookies = document.cookie.split(';');
  for (let cookie of cookies) {
    const [key, value] = cookie.trim().split('=');
    if (key === name) {
      return decodeURIComponent(value);
    }
  }
  return null;
}

/**
 * Send a chat message to the backend.
 */
export async function sendChatMessage(
  message: string,
): Promise<ChatResponse> {
  const csrfToken = getCsrfToken();
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  if (csrfToken) {
    headers['X-XSRF-TOKEN'] = csrfToken;
  }

  const response = await fetch('/chat/message', {
    method: 'POST',
    headers,
    credentials: 'same-origin',
    body: JSON.stringify({ message }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: 'Failed to send message',
    }));
    throw new Error(error.message || 'Failed to send message');
  }

  return response.json();
}

/**
 * Confirm and create an appointment from extracted chat details.
 */
export async function confirmAppointment(
  details: ConfirmAppointmentRequest,
): Promise<ConfirmAppointmentResponse> {
  const csrfToken = getCsrfToken();
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  if (csrfToken) {
    headers['X-XSRF-TOKEN'] = csrfToken;
  }

  const response = await fetch('/chat/appointment/confirm', {
    method: 'POST',
    headers,
    credentials: 'same-origin',
    body: JSON.stringify(details),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: 'Failed to confirm appointment',
    }));
    throw new Error(error.message || 'Failed to confirm appointment');
  }

  return response.json();
}

